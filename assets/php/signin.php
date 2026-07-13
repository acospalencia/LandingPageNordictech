<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($nombre) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    // Extraer parámetros desde el wp-config.php de Bluehost
    $wp_config_path = __DIR__ . '/../../wp-config.php';
    if (file_exists($wp_config_path)) {
        $config_content = file_get_contents($wp_config_path);
        preg_match("/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*)['\"]\s*\);/", $config_content, $db_name);
        preg_match("/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*)['\"]\s*\);/", $config_content, $db_user);
        preg_match("/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*)['\"]\s*\);/", $config_content, $db_password);
        preg_match("/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*)['\"]\s*\);/", $config_content, $db_host);

        $database = $db_name[1] ?? '';
        $username = $db_user[1] ?? '';
        $password_db = $db_password[1] ?? '';
        $host     = $db_host[1] ?? 'localhost';
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password_db, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Verificar si el correo ya existe en tu columna 'email'
        // (Cambia 'usuarios' si tu tabla se llama diferente)
        $stmt_check = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1");
        $stmt_check->execute([':email' => $email]);
        if ($stmt_check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya se encuentra registrado.']);
            exit;
        }

        // Encriptación segura del password
        $pass_hash = password_hash($password, PASSWORD_BCRYPT);

        // Registro con tus columnas exactas (verificado = 0 por defecto)
        $sql = "INSERT INTO usuarios (nombre, email, password, verificado, fecha_registro) 
                VALUES (:nombre, :email, :password, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'   => $nombre,
            ':email'    => $email,
            ':password' => $pass_hash
        ]);

        // Recuperamos el ID autogenerado
        $id_usuario = $pdo->lastInsertId();

        // Generamos una firma criptográfica única y segura para el enlace (evita alteraciones)
        $clave_secreta = "NordicTechSecureKey2026!!"; 
        $firma = hash_hmac('sha256', $id_usuario, $clave_secreta);

        // Enlace directo de activación
        $enlace_verificacion = "https://" . $_SERVER['HTTP_HOST'] . "/assets/php/verify.php?id=" . $id_usuario . "&sig=" . $firma;

        // Envío de correo a soporte
        $para = "soporte@nordictech-corp.com";
        $asunto = "APROBACIÓN REQUERIDA: Nueva cuenta - $nombre";
        
        $mensajeHTML = "
        <html>
        <body style='background-color: #060913; color: #ffffff; font-family: sans-serif; padding: 20px;'>
            <div style='max-width: 500px; margin: 0 auto; background-color: #0D1425; border: 1px solid #1E293B; padding: 25px; text-align: center;'>
                <h3 style='color: #2A4094; text-transform: uppercase;'>Solicitud de Activacion de Cuenta</h3>
                <p style='color: #94A3B8; text-align: left;'>Un nuevo usuario ha solicitado que se verifique su cuenta:</p>
                <ul style='color: #ffffff; text-align: left; list-style: none; padding: 0; line-height: 1.8;'>
                    <li><strong>ID Usuario:</strong> $id_usuario</li>
                    <li><strong>Nombre:</strong> $nombre</li>
                    <li><strong>Correo:</strong> $email</li>
                </ul>
                <p style='color: #94A3B8; margin-top: 25px;'>Para otorgar los privilegios y verificar la cuenta de forma inmediata, presiona el botón inferior:</p>
                <a href='$enlace_verificacion' style='display: inline-block; background-color: #2A4094; color: #ffffff; padding: 12px 24px; text-decoration: none; font-weight: bold; text-transform: uppercase; font-size: 12px; margin-top: 15px; border: 1px solid rgba(255,255,255,0.1);'>
                    Aprobar y Verificar Cuenta
                </a>
            </div>
        </body>
        </html>
        ";

        $cabeceras  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n";
        $cabeceras .= "From: Consola Registro <no-reply@nordictech-corp.com>\r\n";

        @mail($para, $asunto, $mensajeHTML, $cabeceras);

        echo json_encode(['status' => 'success', 'message' => 'Solicitud enviada. Tu cuenta se activará cuando soporte la verifique.']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Fallo interno de Base de Datos.']);
        exit;
    }
}