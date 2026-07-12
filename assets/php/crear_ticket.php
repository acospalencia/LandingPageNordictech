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
        <html>
        <head>
            <title>Notificación de Soporte</title>
        </head>
        <body style='background-color: #060913; color: #ffffff; font-family: sans-serif; padding: 20px;'>
            <div style='max-w: 600px; margin: 0 auto; background-color: #0D1425; border: 1px solid #1E293B; padding: 25px;'>
                <h2 style='color: #2A4094; text-transform: uppercase; font-size: 18px; margin-bottom: 20px; border-b: 1px solid #1E293B; padding-bottom: 10px;'>
                    Alerta de Sistema: Nuevo Ticket Aperturado
                </h2>
                
                <p style='font-size: 14px;'><strong>Detalles del Solicitante:</strong></p>
                <ul style='font-size: 13px; color: #94A3B8;'>
                    <li><strong>Nombre:</strong> $nombre_usuario</li>
                    <li><strong>Correo de la cuenta:</strong> $correo_usuario</li>
                    <li><strong>Fecha/Hora de Registro:</strong> $hora_actual</li>
                </ul>

                <hr style='border: 0; border-top: 1px solid #1E293B; margin: 20px 0;'>

                <p style='font-size: 14px;'><strong>Datos del Incidente:</strong></p>
                <ul style='font-size: 13px; color: #94A3B8;'>
                    <li><strong>Título:</strong> <span style='color: #ffffff;'>$titulo</span></li>
                    <li><strong>Prioridad asignada:</strong> <span style='color: #f87171;'>$prioridad</span></li>
                </ul>

                <p style='font-size: 14px; margin-top: 20px;'><strong>Descripción Técnica:</strong></p>
                <div style='background-color: #060913; border: 1px solid #1E293B; padding: 15px; font-size: 13px; color: #cbd5e1; line-height: 1.6; white-space: pre-wrap;'>$descripcion</div>
                
                <p style='font-size: 10px; color: #64748B; margin-top: 30px; text-align: center;'>
                    Consola de NordicTech El Salvador • Alerta Cifrada de Servidor
                </p>
            </div>
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