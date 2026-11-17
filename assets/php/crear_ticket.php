<?php
require_once __DIR__ . '/correo.php';
session_start();
header('Content-Type: application/json');

// 1. Protección: clientes crean para sí mismos; administradores pueden crear
// tickets para un cliente seleccionado.
$id_usuario_sesion = intval($_SESSION['id_usuario'] ?? 0);
$id_rol_sesion = intval($_SESSION['id_rol'] ?? 0);
$es_administrador = $id_rol_sesion === 3;
$es_cliente = $id_rol_sesion === 1;

if ($id_usuario_sesion <= 0 || (!$es_cliente && !$es_administrador)) {
    echo json_encode(['status' => 'session_expired', 'message' => 'Acceso denegado. Requiere autenticación activa.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $es_administrador
        ? intval($_POST['id_usuario_cliente'] ?? $_POST['id_usuario'] ?? 0)
        : $id_usuario_sesion;
    $titulo = preg_replace('/[\r\n]+/', ' ', trim($_POST['titulo'] ?? ''));
    $prioridad = trim($_POST['prioridad'] ?? 'Media');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $otra_categoria = trim($_POST['otra_categoria'] ?? '');
    $estado = 'Abierto';

    $categorias_permitidas = ['CCTV', 'Control de acceso', 'Panel de incendios', 'Otro'];
    $prioridades_permitidas = ['Baja', 'Media', 'Alta', 'Crítica'];

    if (
        $id_usuario <= 0 ||
        mb_strlen($titulo) < 4 ||
        mb_strlen($titulo) > 160 ||
        mb_strlen($descripcion) < 10 ||
        !in_array($categoria, $categorias_permitidas, true) ||
        !in_array($prioridad, $prioridades_permitidas, true)
    ) {
        echo json_encode(['status' => 'error', 'message' => 'El título, la categoría y la descripción técnica son mandatorios.']);
        exit;
    }

    if ($categoria === 'Otro') {
        $otra_categoria = preg_replace('/\s+/', ' ', $otra_categoria);

        if (empty($otra_categoria) || mb_strlen($otra_categoria) > 120) {
            echo json_encode(['status' => 'error', 'message' => 'Debes especificar la categoría del ticket (máximo 120 caracteres).']);
            exit;
        }
    } else {
        $otra_categoria = null;
    }

    // 2. Extraer parámetros desde wp-config.php de Bluehost
    $wp_config_path = __DIR__ . '/../../wp-config.php';

    if (file_exists($wp_config_path)) {
        $config_content = file_get_contents($wp_config_path);
        preg_match("/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*)['\"]\s*\);/", $config_content, $db_name);
        preg_match("/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*)['\"]\s*\);/", $config_content, $db_user);
        preg_match("/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*)['\"]\s*\);/", $config_content, $db_password);
        preg_match("/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*)['\"]\s*\);/", $config_content, $db_host);

        $database = $db_name[1] ?? '';
        $username = $db_user[1] ?? '';
        $password = $db_password[1] ?? '';
        $host     = $db_host[1] ?? 'localhost';
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Fallo de infraestructura: wp-config inalcanzable.']);
        exit;
    }

    try {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        if ($es_cliente) {
            $stmt_rol_actual = $pdo->prepare(
                "SELECT id_rol, activo FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1"
            );
            $stmt_rol_actual->execute([':id_usuario' => $id_usuario_sesion]);
            $rol_actual = $stmt_rol_actual->fetch();
            if (!$rol_actual || intval($rol_actual['id_rol']) !== 1 || intval($rol_actual['activo']) !== 1) {
                echo json_encode(['status' => 'error', 'message' => 'Tu perfil actual no permite crear tickets.']);
                exit;
            }
        }

        $usuario_info = null;
        if ($es_administrador) {
            $sql_cliente = "SELECT id_usuario, email, nombre, id_rol, activo FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1";
            $stmt_cliente = $pdo->prepare($sql_cliente);
            $stmt_cliente->execute([':id_usuario' => $id_usuario]);
            $usuario_info = $stmt_cliente->fetch();

            if (!$usuario_info || intval($usuario_info['id_rol']) !== 1) {
                echo json_encode(['status' => 'error', 'message' => 'El usuario seleccionado no es un cliente válido.']);
                exit;
            }

            if (intval($usuario_info['activo']) !== 1) {
                echo json_encode(['status' => 'error', 'message' => 'No puedes abrir tickets para un cliente inactivo.']);
                exit;
            }

            if (!filter_var(trim($usuario_info['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'message' => 'El cliente seleccionado no tiene un correo válido para recibir la notificación.']);
                exit;
            }
        }

        // 3. Inserción del ticket en la base de datos
        $query = "INSERT INTO tickets (id_usuario, titulo, descripcion, estado, prioridad, categoria, otra_categoria, fecha_creacion)
                  VALUES (:id_usuario, :titulo, :descripcion, :estado, :prioridad, :categoria, :otra_categoria, NOW())";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':titulo' => $titulo,
            ':prioridad' => $prioridad,
            ':descripcion' => $descripcion,
            ':estado' => $estado,
            ':categoria' => $categoria,
            ':otra_categoria' => $otra_categoria
        ]);
        $id_ticket = intval($pdo->lastInsertId());

        // 4. [NUEVO] Obtener información del usuario para el correo
        if (!$usuario_info) {
            $sql_user = "SELECT email, nombre FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1";
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute([':id_usuario' => $id_usuario]);
            $usuario_info = $stmt_user->fetch();
        }

        $nombre_usuario = $usuario_info['nombre'] ?? 'Usuario ID: ' . $id_usuario;
        $correo_usuario = $usuario_info['email'] ?? 'No disponible';
        $hora_actual = date('Y-m-d H:i:s');
        $categoria_mostrada = $categoria === 'Otro' ? "Otro: {$otra_categoria}" : $categoria;
        $categoria_correo = htmlspecialchars($categoria_mostrada, ENT_QUOTES, 'UTF-8');

        // 5. [NUEVO] Lógica de envío de Correo (Formato HTML Corporativo)
        $para = "tickets@nordictech-corp.com"; // <-- Coloca aquí el correo donde quieres recibir las alertas
        $asunto = "⚠️ NUEVO TICKET: [$prioridad] - $titulo";

        // Cuerpo en formato HTML con los colores de tu consola
        $mensajeHTML = "
        <!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Notificación de Soporte | NordicTech</title>
</head>
<body style='margin: 0; padding: 0; background-color: #101729; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: #ffffff; -webkit-font-smoothing: antialiased;'>

    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #060913; padding: 40px 20px;'>
        <tr>
            <td align='center'>

                <!-- Tarjeta Principal Centrada -->
                <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #0D1425; border: 1px solid #1E293B; border-top: 4px solid #2A4094; padding: 40px 30px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);'>

                    <!-- Encabezado con Logo Corporativo -->
                    <tr>
                        <td align='center' style='padding-bottom: 30px; border-bottom: 1px solid #1E293B;'>
                            <div style='font-family: \"Space Grotesk\", \"Segoe UI\", sans-serif; font-weight: bold; font-size: 24px; letter-spacing: -0.02em; color: #ffffff; text-transform: uppercase; line-height: 24px;'>
                                <img src='https://nordictech-corp.com/assets/img/Marca%20de%20agua%20black.png' alt='Logo' style='display: inline-block; height: 84px; width: auto; vertical-align: middle; margin-left: 6px; border: 0;'>
                            </div>
                        </td>
                    </tr>

                    <!-- Título de la Alerta -->
                    <tr>
                        <td style='padding-top: 35px; padding-bottom: 15px;'>
                            <span style='font-size: 10px; font-weight: bold; letter-spacing: 2px; color: #3C56C4; text-transform: uppercase; display: block; margin-bottom: 6px;'>
                                Gestión de Incidencias
                            </span>
                            <h2 style='margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: -0.01em; text-transform: uppercase;'>
                                NUEVO TICKET APERTURADO
                            </h2>
                        </td>
                    </tr>

                    <!-- Bloque 1: Datos del Cliente -->
                    <tr>
                        <td style='padding-bottom: 15px;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #060913; border: 1px solid #1E293B; padding: 20px;'>
                                <tr>
                                    <td style='font-size: 13px; color: #ffffff; font-weight: bold; padding-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #1E293B;'>
                                        Detalles del Cliente
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding-top: 10px; font-size: 12px; color: #94A3B8; line-height: 1.8;'>
                                        <strong style='color: #ffffff;'>Nombre:</strong> " . $nombre_usuario . "<br>
                                        <strong style='color: #ffffff;'>Correo de la cuenta:</strong> " . $correo_usuario . "<br>
                                        <strong style='color: #ffffff;'>Fecha/Hora de Registro:</strong> " . $hora_actual . "
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Bloque 2: Detalles Técnicos del Ticket -->
                    <tr>
                        <td style='padding-bottom: 25px;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #060913; border: 1px solid #1E293B; padding: 20px;'>
                                <tr>
                                    <td style='font-size: 13px; color: #ffffff; font-weight: bold; padding-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #1E293B;'>
                                        Datos del Requerimiento
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding-top: 10px; font-size: 12px; color: #94A3B8; line-height: 1.8;'>
                                        <strong style='color: #ffffff;'>Título:</strong> <span style='color: #ffffff; font-weight: 600;'>" . $titulo . "</span><br>
                                        <strong style='color: #ffffff;'>Categoría:</strong> <span style='color: #60a5fa; font-weight: bold;'>" . $categoria_correo . "</span><br>
                                        <strong style='color: #ffffff;'>Prioridad asignada:</strong> <span style='color: #f87171; font-weight: bold; text-transform: uppercase;'>" . $prioridad . "</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Bloque 3: Descripción Completa -->
                    <tr>
                        <td style='padding-bottom: 30px;'>
                            <h3 style='margin: 0 0 10px 0; font-size: 11px; font-weight: bold; letter-spacing: 1.5px; color: #ffffff; text-transform: uppercase;'>
                                Descripción del Ticket:
                            </h3>
                            <div style='background-color: #060913; padding: 20px; border-left: 3px solid #2A4094; border-top: 1px solid #1E293B; border-right: 1px solid #1E293B; border-bottom: 1px solid #1E293B; color: #cbd5e1; font-size: 13px; line-height: 1.6; white-space: pre-wrap;'>
                                " . nl2br($descripcion) . "
                            </div>
                        </td>
                    </tr>

                    <!-- Footer Interno Automatizado -->
                    <tr>
                        <td align='center' style='padding-top: 40px; border-top: 1px solid #1E293B; font-size: 11px; color: #64748B; font-style: italic;'>
                            NordicTech El Salvador
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
        ";

        // Enviar correo inmediatamente desde el hosting.
        if (!enviarCorreoDirecto(
            $para,
            $asunto,
            $mensajeHTML,
            'soporte@nordictech-corp.com',
            'Consola NordicTech',
            filter_var($correo_usuario, FILTER_VALIDATE_EMAIL) ? $correo_usuario : null
        )) {
            error_log('[Landing NordicTech][crear_ticket] mail() no aceptó la alerta del ticket.');
        }

        // Cuando administración abre el ticket para otra cuenta, avisar de
        // inmediato al correo registrado del cliente mediante mail().
        $correo_cliente_enviado = true;
        if ($es_administrador) {
            $nombre_cliente_seguro = htmlspecialchars($nombre_usuario, ENT_QUOTES, 'UTF-8');
            $titulo_cliente_seguro = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
            $categoria_cliente_segura = htmlspecialchars($categoria_mostrada, ENT_QUOTES, 'UTF-8');
            $prioridad_cliente_segura = htmlspecialchars($prioridad, ENT_QUOTES, 'UTF-8');
            $asunto_cliente = "Nuevo ticket de soporte #TK-{$id_ticket} - {$titulo}";
            $mensaje_cliente = "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'><title>Nuevo ticket de soporte</title></head>
<body style='margin:0;padding:0;background:#060913;font-family:Segoe UI,Arial,sans-serif;color:#ffffff;'>
  <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background:#060913;padding:36px 18px;'>
    <tr><td align='center'>
      <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width:600px;background:#0D1425;border:1px solid #1E293B;border-top:4px solid #3B82F6;padding:34px 28px;'>
        <tr><td style='font-size:10px;font-weight:700;letter-spacing:2px;color:#60A5FA;text-transform:uppercase;'>NordicTech · Centro de soporte</td></tr>
        <tr><td style='padding-top:10px;font-size:22px;font-weight:700;color:#ffffff;'>Se abrió un ticket para tu cuenta</td></tr>
        <tr><td style='padding-top:18px;font-size:14px;line-height:1.7;color:#CBD5E1;'>Hola <strong style='color:#ffffff;'>{$nombre_cliente_seguro}</strong>, el equipo de soporte registró una nueva solicitud técnica a nombre de tu cuenta.</td></tr>
        <tr><td style='padding-top:22px;'>
          <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background:#07111F;border:1px solid #1E293B;padding:20px;'>
            <tr><td style='font-size:12px;line-height:1.9;color:#94A3B8;'>
              <strong style='color:#ffffff;'>Ticket:</strong> #TK-{$id_ticket}<br>
              <strong style='color:#ffffff;'>Título:</strong> {$titulo_cliente_seguro}<br>
              <strong style='color:#ffffff;'>Categoría:</strong> {$categoria_cliente_segura}<br>
              <strong style='color:#ffffff;'>Prioridad:</strong> {$prioridad_cliente_segura}<br>
              <strong style='color:#ffffff;'>Estado:</strong> Abierto
            </td></tr>
          </table>
        </td></tr>
        <tr><td style='padding-top:20px;font-size:12px;color:#94A3B8;line-height:1.7;'>Podés consultar el seguimiento, las asignaciones y la resolución ingresando al portal de clientes de NordicTech.</td></tr>
        <tr><td style='padding-top:24px;'>
          <a href='https://nordictech-corp.com/portal' style='display:inline-block;background:#3B82F6;color:#ffffff;text-decoration:none;font-size:12px;font-weight:700;padding:12px 18px;border-radius:8px;'>Consultar ticket</a>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>";

            $correo_cliente_enviado = enviarCorreoDirecto(
                $correo_usuario,
                $asunto_cliente,
                $mensaje_cliente,
                'soporte@nordictech-corp.com',
                'Soporte NordicTech'
            );

            if (!$correo_cliente_enviado) {
                error_log('[Landing NordicTech][crear_ticket] mail() no aceptó la notificación directa al cliente.');
            }
        }

        // 6. Respuesta al Frontend
        $mensaje_respuesta = 'Ticket de soporte aperturado y notificación de alerta enviada con éxito.';
        if ($es_administrador) {
            $mensaje_respuesta = $correo_cliente_enviado
                ? 'Ticket abierto correctamente. La notificación fue enviada directamente al cliente.'
                : 'Ticket abierto correctamente, pero mail() no aceptó la notificación al cliente.';
        }

        echo json_encode([
            'status' => 'success',
            'message' => $mensaje_respuesta,
            'id_ticket' => $id_ticket
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Fallo al interactuar con el servidor de base de datos.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Petición denegada por protocolo de red.']);
    exit;
}
