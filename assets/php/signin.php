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
        <!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Activación de Cuenta | NordicTech</title>
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

                    <!-- Título de la Solicitud -->
                    <tr>
                        <td style='padding-top: 35px; padding-bottom: 15px;'>
                            <span style='font-size: 10px; font-weight: bold; letter-spacing: 2px; color: #3C56C4; text-transform: uppercase; display: block; margin-bottom: 6px;'>
                                Seguridad y Accesos
                            </span>
                            <h2 style='margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: -0.01em; text-transform: uppercase;'>
                                Solicitud de Activación de Cuenta
                            </h2>
                        </td>
                    </tr>

                    <!-- Introducción -->
                    <tr>
                        <td style='font-size: 14px; color: #94A3B8; padding-bottom: 15px; line-height: 1.5;'>
                            Un nuevo usuario ha solicitado que se verifique su cuenta en el sistema:
                        </td>
                    </tr>

                    <!-- Bloque de Datos del Usuario -->
                    <tr>
                        <td style='padding-bottom: 25px;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #060913; border: 1px solid #1E293B; padding: 20px;'>
                                <tr>
                                    <td style='font-size: 12px; color: #94A3B8; line-height: 2;'>
                                        <strong style='color: #ffffff; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;'>ID Usuario:</strong> " . $id_usuario . "<br>
                                        <strong style='color: #ffffff; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;'>Nombre completo:</strong> " . $nombre . "<br>
                                        <strong style='color: #ffffff; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;'>Correo Electrónico:</strong> <a href='mailto:" . $email . "' style='color: #3C56C4; text-decoration: none;'>" . $email . "</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Instrucciones del Botón -->
                    <tr>
                        <td style='font-size: 14px; color: #94A3B8; padding-bottom: 20px; line-height: 1.5;'>
                            Para otorgar los privilegios correspondientes y verificar la cuenta de forma inmediata, presiona el botón inferior:
                        </td>
                    </tr>

                    <!-- Botón de Acción Centrado -->
                    <tr>
                        <td align='center' style='padding-bottom: 35px;'>
                            <table border='0' cellpadding='0' cellspacing='0'>
                                <tr>
                                    <td align='center' bgcolor='#2A4094' style='border: 1px solid rgba(255,255,255,0.1);'>
                                        <a href='" . $enlace_verificacion . "' target='_blank' style='display: inline-block; padding: 14px 28px; font-size: 12px; font-family: sans-serif; color: #ffffff; text-decoration: none; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>
                                            Aprobar y Verificar Cuenta
                                        </a>
                                    </td>
                                </tr>
                            </table>
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