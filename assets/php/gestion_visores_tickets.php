<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

const ROL_ADMINISTRADOR = 3;
const ROL_ADMIN_CONTEO = 4;
const ROL_VISOR_TICKETS = 5;

function mensajeDiagnosticoVisores(PDOException $error, string $etapa): string
{
    $codigoMotor = isset($error->errorInfo[1]) ? intval($error->errorInfo[1]) : 0;

    if ($codigoMotor === 1146) {
        return 'La tabla de permisos de visores no existe en la base de datos usada por el portal.';
    }
    if (in_array($codigoMotor, [1044, 1045, 1142], true)) {
        return 'El usuario de base de datos del portal no tiene permisos suficientes para administrar los visores.';
    }
    if ($codigoMotor === 1054) {
        return 'La estructura de la base de datos no contiene una columna requerida por el visor de tickets.';
    }

    $codigo = $codigoMotor > 0
        ? 'SQLSTATE ' . $error->getCode() . ', MySQL ' . $codigoMotor
        : 'SQLSTATE ' . $error->getCode();

    return 'No fue posible administrar los visores durante ' . $etapa . '. Código: ' . $codigo . '.';
}

function responderVisores(string $status, string $message, $data = null, int $httpStatus = 200): void
{
    http_response_code($httpStatus);
    $respuesta = ['status' => $status, 'message' => $message];
    if ($data !== null) {
        $respuesta['data'] = $data;
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function conectarVisores(): PDO
{
    $wpConfig = __DIR__ . '/../../wp-config.php';
    if (!file_exists($wpConfig)) {
        responderVisores('error', 'No se encontró la configuración del sistema.', null, 500);
    }

    $contenido = file_get_contents($wpConfig);
    preg_match("/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $contenido, $dbName);
    preg_match("/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $contenido, $dbUser);
    preg_match("/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $contenido, $dbPassword);
    preg_match("/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $contenido, $dbHost);

    return new PDO(
        'mysql:host=' . ($dbHost[1] ?? 'localhost') . ';dbname=' . ($dbName[1] ?? '') . ';charset=utf8mb4',
        $dbUser[1] ?? '',
        $dbPassword[1] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

$idUsuarioSesion = intval($_SESSION['id_usuario'] ?? 0);
$rolSesion = intval($_SESSION['id_rol'] ?? 0);
if ($idUsuarioSesion <= 0) {
    responderVisores('session_expired', 'Debes iniciar sesión.', null, 401);
}

$etapa = 'la conexión con la base de datos';
try {
    $pdo = conectarVisores();

    $etapa = 'el procesamiento de la solicitud';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $accion = trim((string) ($_GET['action'] ?? ''));

        if ($rolSesion === ROL_ADMINISTRADOR && $accion === 'admin') {
            $etapa = 'la consulta de usuarios visores';
            $visores = $pdo->query(
                "SELECT id_usuario, nombre, email, activo
                 FROM usuarios
                 WHERE id_rol = 5
                 ORDER BY activo DESC, nombre ASC"
            )->fetchAll();
            $etapa = 'la consulta de permisos de visores';
            $relaciones = $pdo->query(
                "SELECT id_visor, id_cliente
                 FROM visores_tickets_clientes
                 ORDER BY id_visor, id_cliente"
            )->fetchAll();

            $clientesPorVisor = [];
            foreach ($relaciones as $relacion) {
                $idVisor = intval($relacion['id_visor']);
                if (!isset($clientesPorVisor[$idVisor])) {
                    $clientesPorVisor[$idVisor] = [];
                }
                $clientesPorVisor[$idVisor][] = intval($relacion['id_cliente']);
            }

            foreach ($visores as &$visor) {
                $idVisor = intval($visor['id_usuario']);
                $visor['id_usuario'] = $idVisor;
                $visor['activo'] = intval($visor['activo']);
                $visor['client_ids'] = $clientesPorVisor[$idVisor] ?? [];
            }
            unset($visor);

            responderVisores('success', 'Permisos de visores cargados.', ['viewers' => $visores]);
        }

        if ($rolSesion === ROL_VISOR_TICKETS && $accion === 'viewer') {
            $etapa = 'la consulta de clientes autorizados';
            $stmtClientes = $pdo->prepare(
                "SELECT u.id_usuario, u.nombre, u.email, u.id_rol, u.verificado, u.activo,
                        u.codigo_empresa, u.fecha_registro
                 FROM visores_tickets_clientes permiso
                 INNER JOIN usuarios u ON u.id_usuario = permiso.id_cliente
                 WHERE permiso.id_visor = :id_visor
                   AND u.id_rol = 1
                   AND u.activo = 1
                 ORDER BY u.nombre ASC"
            );
            $stmtClientes->execute(['id_visor' => $idUsuarioSesion]);
            $clientes = $stmtClientes->fetchAll();

            $etapa = 'la consulta de tickets autorizados';
            $stmtTickets = $pdo->prepare(
                "SELECT t.id_ticket, t.id_usuario, t.id_tecnico, t.titulo, t.descripcion,
                        t.estado, t.prioridad, t.categoria, t.otra_categoria,
                        t.observacion_proceso, t.observacion_cierre, t.fecha_creacion,
                        t.fecha_actualizacion, t.activo,
                        cliente.nombre AS cliente_nombre, cliente.email AS cliente_email,
                        tecnico.nombre AS nombre_tecnico
                 FROM visores_tickets_clientes permiso
                 INNER JOIN tickets t ON t.id_usuario = permiso.id_cliente
                 INNER JOIN usuarios cliente ON cliente.id_usuario = t.id_usuario
                 LEFT JOIN usuarios tecnico ON tecnico.id_usuario = t.id_tecnico
                 WHERE permiso.id_visor = :id_visor
                   AND cliente.id_rol = 1
                   AND cliente.activo = 1
                   AND t.activo = 1
                   AND t.estado IN ('Abierto', 'En Proceso', 'Resuelto', 'Cerrado')
                 ORDER BY t.id_ticket DESC"
            );
            $stmtTickets->execute(['id_visor' => $idUsuarioSesion]);

            responderVisores('success', 'Tickets autorizados cargados.', [
                'viewer_name' => $_SESSION['nombre_usuario'] ?? 'Visor de tickets',
                'clients' => $clientes,
                'tickets' => $stmtTickets->fetchAll(),
            ]);
        }

        responderVisores('error', 'Acceso denegado.', null, 403);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $rolSesion !== ROL_ADMINISTRADOR) {
        responderVisores('error', 'Acceso denegado.', null, 403);
    }

    $entrada = json_decode(file_get_contents('php://input'), true) ?: [];
    $accion = trim((string) ($entrada['action'] ?? ''));
    $idVisor = intval($entrada['id_viewer'] ?? 0);
    if ($idVisor <= 0 || $idVisor === $idUsuarioSesion) {
        responderVisores('error', 'El usuario visor seleccionado no es válido.');
    }

    if ($accion === 'save_access') {
        $idsClientes = array_values(array_unique(array_filter(
            array_map('intval', is_array($entrada['client_ids'] ?? null) ? $entrada['client_ids'] : []),
            static fn (int $id): bool => $id > 0 && $id !== $idVisor
        )));
        if (count($idsClientes) === 0) {
            responderVisores('error', 'Selecciona al menos un cliente para el visor.');
        }

        $etapa = 'el guardado de permisos del visor';
        $pdo->beginTransaction();
        $stmtVisor = $pdo->prepare(
            "SELECT id_usuario, id_rol, activo FROM usuarios WHERE id_usuario = :id FOR UPDATE"
        );
        $stmtVisor->execute(['id' => $idVisor]);
        $visor = $stmtVisor->fetch();
        if (!$visor || intval($visor['activo']) !== 1 || !in_array(intval($visor['id_rol']), [ROL_ADMIN_CONTEO, ROL_VISOR_TICKETS], true)) {
            $pdo->rollBack();
            responderVisores('error', 'La cuenta debe estar activa y ser administradora de conteo o visor combinado.');
        }

        $marcadores = implode(',', array_fill(0, count($idsClientes), '?'));
        $stmtClientes = $pdo->prepare(
            "SELECT id_usuario FROM usuarios
             WHERE id_usuario IN ($marcadores) AND id_rol = 1 AND activo = 1"
        );
        $stmtClientes->execute($idsClientes);
        $clientesValidos = array_map('intval', $stmtClientes->fetchAll(PDO::FETCH_COLUMN));
        sort($clientesValidos);
        $clientesEsperados = $idsClientes;
        sort($clientesEsperados);
        if ($clientesValidos !== $clientesEsperados) {
            $pdo->rollBack();
            responderVisores('error', 'Uno de los clientes seleccionados no está disponible.');
        }

        $pdo->prepare(
            "UPDATE usuarios SET id_rol = :rol, conteo_center_slug = NULL WHERE id_usuario = :id"
        )->execute(['rol' => ROL_VISOR_TICKETS, 'id' => $idVisor]);
        $pdo->prepare("DELETE FROM visores_tickets_clientes WHERE id_visor = :id")
            ->execute(['id' => $idVisor]);
        $insertar = $pdo->prepare(
            "INSERT INTO visores_tickets_clientes
             (id_visor, id_cliente, id_asignado_por, fecha_asignacion)
             VALUES (:visor, :cliente, :administrador, NOW())"
        );
        foreach ($idsClientes as $idCliente) {
            $insertar->execute([
                'visor' => $idVisor,
                'cliente' => $idCliente,
                'administrador' => $idUsuarioSesion,
            ]);
        }
        $pdo->commit();
        responderVisores('success', 'Permisos guardados. El visor debe volver a iniciar sesión.');
    }

    if ($accion === 'revoke_access') {
        $etapa = 'la revocación de permisos del visor';
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM visores_tickets_clientes WHERE id_visor = :id")
            ->execute(['id' => $idVisor]);
        $stmt = $pdo->prepare(
            "UPDATE usuarios SET id_rol = :rol WHERE id_usuario = :id AND id_rol = :rol_visor"
        );
        $stmt->execute([
            'rol' => ROL_ADMIN_CONTEO,
            'id' => $idVisor,
            'rol_visor' => ROL_VISOR_TICKETS,
        ]);
        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            responderVisores('error', 'La cuenta seleccionada ya no es un visor de tickets.');
        }
        $pdo->commit();
        responderVisores('success', 'Acceso de visor revocado. La cuenta conservará únicamente la administración de conteo.');
    }

    responderVisores('error', 'Acción no válida.');
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Gestion visores tickets: ' . $e->getMessage());
    $mensaje = $rolSesion === ROL_ADMINISTRADOR
        ? mensajeDiagnosticoVisores($e, $etapa)
        : 'No fue posible administrar los visores.';
    responderVisores(
        'error',
        $mensaje,
        null,
        500
    );
}
