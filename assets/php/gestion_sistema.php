<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function responder($status, $message, $data = null) {
    $respuesta = ['status' => $status, 'message' => $message];
    if ($data !== null) $respuesta['data'] = $data;
    echo json_encode($respuesta);
    exit;
}

if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_rol'] ?? 0) !== 3) {
    http_response_code(403);
    responder('error', 'Acceso denegado.');
}

$wpConfig = __DIR__ . '/../../wp-config.php';
if (!file_exists($wpConfig)) responder('error', 'No se encontró la configuración del sistema.');

$contenido = file_get_contents($wpConfig);
preg_match("/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $contenido, $dbName);
preg_match("/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $contenido, $dbUser);
preg_match("/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $contenido, $dbPassword);
preg_match("/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $contenido, $dbHost);

try {
    $pdo = new PDO(
        'mysql:host=' . ($dbHost[1] ?? 'localhost') . ';dbname=' . ($dbName[1] ?? '') . ';charset=utf8mb4',
        $dbUser[1] ?? '',
        $dbPassword[1] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $usuarios = $pdo->query("SELECT id_usuario, nombre, email, id_rol, verificado, activo, fecha_registro FROM usuarios ORDER BY activo DESC, nombre ASC")->fetchAll();
        $tickets = $pdo->query("SELECT t.id_ticket, t.id_tecnico, t.titulo, t.estado, t.prioridad, t.categoria, t.activo, t.fecha_creacion, u.nombre AS cliente, tec.nombre AS tecnico FROM tickets t LEFT JOIN usuarios u ON u.id_usuario = t.id_usuario LEFT JOIN usuarios tec ON tec.id_usuario = t.id_tecnico ORDER BY t.id_ticket DESC")->fetchAll();
        $cola = $pdo->query("SELECT id_correo, destinatario, asunto, estado, intentos, fecha_registro, fecha_envio FROM cola_correos ORDER BY id_correo DESC LIMIT 100")->fetchAll();

        $metricas = [
            'usuarios_activos' => intval($pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetchColumn()),
            'usuarios_inactivos' => intval($pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 0")->fetchColumn()),
            'tickets_activos' => intval($pdo->query("SELECT COUNT(*) FROM tickets WHERE activo = 1 AND estado NOT IN ('Cerrado', 'Resuelto')")->fetchColumn()),
            'tickets_cerrados' => intval($pdo->query("SELECT COUNT(*) FROM tickets WHERE estado IN ('Cerrado', 'Resuelto')")->fetchColumn()),
            'correos_pendientes' => intval($pdo->query("SELECT COUNT(*) FROM cola_correos WHERE estado = 'pendiente'")->fetchColumn())
        ];

        responder('success', 'Datos cargados.', ['metricas' => $metricas, 'usuarios' => $usuarios, 'tickets' => $tickets, 'cola' => $cola]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder('error', 'Método no permitido.');

    $entrada = json_decode(file_get_contents('php://input'), true) ?: [];
    $accion = $entrada['action'] ?? '';

    if ($accion === 'toggle_usuario') {
        $id = intval($entrada['id_usuario'] ?? 0);
        $activo = intval($entrada['activo'] ?? 0) === 1 ? 1 : 0;
        if ($id <= 0) responder('error', 'Usuario inválido.');
        if ($id === intval($_SESSION['id_usuario']) && $activo === 0) responder('error', 'No puedes desactivar tu propia cuenta.');
        $stmt = $pdo->prepare("UPDATE usuarios SET activo = :activo WHERE id_usuario = :id");
        $stmt->execute(['activo' => $activo, 'id' => $id]);
        responder('success', $activo ? 'Usuario reactivado.' : 'Usuario desactivado.');
    }

    if ($accion === 'cambiar_rol') {
        $id = intval($entrada['id_usuario'] ?? 0);
        $rol = intval($entrada['id_rol'] ?? 0);
        if ($id <= 0 || !in_array($rol, [1, 2, 3], true)) responder('error', 'Datos de rol inválidos.');
        if ($id === intval($_SESSION['id_usuario']) && $rol !== 3) responder('error', 'No puedes retirar tu propio rol de administrador.');
        $stmt = $pdo->prepare("UPDATE usuarios SET id_rol = :rol WHERE id_usuario = :id");
        $stmt->execute(['rol' => $rol, 'id' => $id]);
        responder('success', 'Rol actualizado.');
    }

    if ($accion === 'guardar_usuario') {
        $id = intval($entrada['id_usuario'] ?? 0);
        $nombre = trim($entrada['nombre'] ?? '');
        $email = trim($entrada['email'] ?? '');
        $rol = intval($entrada['id_rol'] ?? 1);
        $verificado = intval($entrada['verificado'] ?? 0) === 1 ? 1 : 0;
        $passwordNueva = $entrada['password'] ?? '';
        if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($rol, [1, 2, 3], true)) responder('error', 'Completa correctamente los datos del usuario.');

        if ($id > 0) {
            if ($id === intval($_SESSION['id_usuario']) && $rol !== 3) responder('error', 'No puedes retirar tu propio rol de administrador.');
            if ($passwordNueva !== '' && strlen($passwordNueva) < 8) responder('error', 'La contraseña debe tener al menos 8 caracteres.');
            $sql = "UPDATE usuarios SET nombre = :nombre, email = :email, id_rol = :rol, verificado = :verificado";
            $params = ['nombre' => $nombre, 'email' => $email, 'rol' => $rol, 'verificado' => $verificado, 'id' => $id];
            if ($passwordNueva !== '') { $sql .= ", password = :password"; $params['password'] = password_hash($passwordNueva, PASSWORD_DEFAULT); }
            $sql .= " WHERE id_usuario = :id";
            $pdo->prepare($sql)->execute($params);
            responder('success', 'Usuario actualizado.');
        }

        if (strlen($passwordNueva) < 8) responder('error', 'La contraseña debe tener al menos 8 caracteres.');
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, verificado, activo, id_rol, fecha_registro) VALUES (:nombre, :email, :password, :verificado, 1, :rol, NOW())");
        $stmt->execute(['nombre' => $nombre, 'email' => $email, 'password' => password_hash($passwordNueva, PASSWORD_DEFAULT), 'verificado' => $verificado, 'rol' => $rol]);
        responder('success', 'Usuario creado.');
    }

    if ($accion === 'toggle_ticket') {
        $id = intval($entrada['id_ticket'] ?? 0);
        $activo = intval($entrada['activo'] ?? 0) === 1 ? 1 : 0;
        if ($id <= 0) responder('error', 'Ticket inválido.');
        $stmt = $pdo->prepare("UPDATE tickets SET activo = :activo, fecha_actualizacion = NOW() WHERE id_ticket = :id");
        $stmt->execute(['activo' => $activo, 'id' => $id]);
        responder('success', $activo ? 'Ticket reactivado.' : 'Ticket desactivado.');
    }

    if ($accion === 'guardar_ticket') {
        $id = intval($entrada['id_ticket'] ?? 0);
        $estado = $entrada['estado'] ?? '';
        $prioridad = $entrada['prioridad'] ?? '';
        $categoria = trim($entrada['categoria'] ?? '');
        $idTecnico = intval($entrada['id_tecnico'] ?? 0);
        if ($id <= 0 || !in_array($estado, ['Abierto', 'En Proceso', 'Resuelto', 'Cerrado'], true) || !in_array($prioridad, ['Baja', 'Media', 'Alta', 'Crítica'], true)) responder('error', 'Datos del ticket inválidos.');
        $stmt = $pdo->prepare("UPDATE tickets SET estado = :estado, prioridad = :prioridad, categoria = :categoria, id_tecnico = :tecnico, fecha_actualizacion = NOW() WHERE id_ticket = :id");
        $stmt->bindValue(':estado', $estado);
        $stmt->bindValue(':prioridad', $prioridad);
        $stmt->bindValue(':categoria', $categoria);
        $stmt->bindValue(':tecnico', $idTecnico > 0 ? $idTecnico : null, $idTecnico > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        responder('success', 'Ticket actualizado.');
    }

    responder('error', 'Acción no válida.');
} catch (PDOException $e) {
    error_log('Gestion sistema: ' . $e->getMessage());
    responder('error', 'No fue posible consultar la administración. Verifica que la migración esté aplicada.');
}
