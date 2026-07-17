<?php
// Evitar que el navegador deje colgado el script si tarda un poco
set_time_limit(60); 

// CORRECCIÓN: Ahora busca el archivo en la misma raíz exacta
$wp_config_path = __DIR__ . '/wp-config.php';
if (!file_exists($wp_config_path)) {
    die("Error: wp-config no encontrado en el directorio actual.");
}

$config_content = file_get_contents($wp_config_path);
preg_match("/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_name);
preg_match("/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_user);
preg_match("/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_password);
preg_match("/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_host);

$database = isset($db_name[1]) ? trim($db_name[1]) : '';
$username = isset($db_user[1]) ? trim($db_user[1]) : '';
$password_db = isset($db_password[1]) ? trim($db_password[1]) : '';
$host     = isset($db_host[1]) ? trim($db_host[1]) : 'localhost';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password_db, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Seleccionar los primeros 5 correos pendientes (para no saturar el servidor por minuto)
    $stmt = $pdo->prepare("SELECT * FROM cola_correos WHERE estado = 'pendiente' AND intentos < 3 LIMIT 5");
    $stmt->execute();
    $correos_pendientes = $stmt->fetchAll();

    foreach ($correos_pendientes as $correo) {
        $id_correo = $correo['id_correo'];
        $correo_sistema = 'no-reply@nordictech-corp.com';

        $headers  = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: NordicTech <" . $correo_sistema . ">" . "\r\n";
        $headers .= "Reply-To: " . $correo_sistema . "\r\n";
        $headers .= "Return-Path: " . $correo_sistema . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        // Intentar enviar el correo
        if (mail($correo['destinatario'], $correo['asunto'], $correo['cuerpo'], $headers)) {
            // Éxito: Actualizar registro
            $upd = $pdo->prepare("UPDATE cola_correos SET estado = 'enviado', fecha_envio = NOW() WHERE id_correo = ?");
            $upd->execute([$id_correo]);
            echo "Correo ID {$id_correo} enviado con éxito.\n";
        } else {
            // Fallo: Incrementar contador de intentos de forma segura
            $upd = $pdo->prepare("UPDATE cola_correos SET intentos = intentos + 1, estado = IF(intentos + 1 >= 3, 'fallido', 'pendiente') WHERE id_correo = ?");
            $upd->execute([$id_correo]);
            echo "Fallo al enviar Correo ID {$id_correo}.\n";
        }
    }

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage();
}