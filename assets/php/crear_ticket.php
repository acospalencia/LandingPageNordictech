<?php
session_start();
header('Content-Type: application/json');

// 1. Protección: Validar que la sesión esté viva
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'session_expired', 'message' => 'Acceso denegado. Requiere autenticación activa.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['id_usuario'];
    $titulo = trim($_POST['titulo'] ?? '');
    $prioridad = trim($_POST['prioridad'] ?? 'Media');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado = 'Abierto';

    if (empty($titulo) || empty($descripcion)) {
        echo json_encode(['status' => 'error', 'message' => 'El título y la descripción técnica son mandatorios.']);
        exit;
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

        // 3. Inserción del ticket en la base de datos
        $query = "INSERT INTO tickets (id_usuario, titulo, descripcion, estado, prioridad, fecha_creacion) 
                  VALUES (:id_usuario, :titulo, :descripcion, :estado, :prioridad, NOW())";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':titulo' => $titulo,
            ':prioridad' => $prioridad,
            ':descripcion' => $descripcion,
            ':estado' => $estado
        ]);

        // 4. [NUEVO] Obtener información del usuario para el correo
        // Nota: Ajusta 'wp_users' u 'id', 'user_email', 'display_name' según los nombres exactos de tu tabla
        $sql_user = "SELECT email, nombre FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([':id_usuario' => $id_usuario]);
        $usuario_info = $stmt_user->fetch();

        $nombre_usuario = $usuario_info['nombre'] ?? 'Usuario ID: ' . $id_usuario;
        $correo_usuario = $usuario_info['email'] ?? 'No disponible';
        $hora_actual = date('Y-m-d H:i:s');

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

        // Cabeceras obligatorias para el envío de HTML en Bluehost
        $cabeceras  = "MIME-Version: 1.0\r\n";
        $cabeceras .= "Content-type: text/html; charset=utf-8\r\n";
        $cabeceras .= "From: Consola NordicTech <soporte@nordictech-corp.com>\r\n";
        $cabeceras .= "Reply-To: $correo_usuario\r\n";

        // Enviar correo
        @mail($para, $asunto, $mensajeHTML, $cabeceras);

        // 6. Respuesta al Frontend
        echo json_encode([
            'status' => 'success',
            'message' => 'Ticket de soporte aperturado y notificación de alerta enviada con éxito.'
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
