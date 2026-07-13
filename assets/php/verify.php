<?php
header('Content-Type: text/html; charset=utf-8');

$id_usuario = trim($_GET['id'] ?? '');
$sig = trim($_GET['sig'] ?? '');

if (empty($id_usuario) || empty($sig)) {
    die("<h2 style='color: #f87171; text-align:center; font-family:sans-serif; margin-top:50px;'>❌ Parámetros de verificación corruptos o ausentes.</h2>");
}

// Validar la firma criptográfica para evitar que alguien intente adivinar IDs al azar
$clave_secreta = "NordicTechSecureKey2026!!";
$firma_esperada = hash_hmac('sha256', $id_usuario, $clave_secreta);

if (!hash_equals($firma_esperada, $sig)) {
    die("<h2 style='color: #f87171; text-align:center; font-family:sans-serif; margin-top:50px;'>❌ Firma digital inválida. Petición rechazada por seguridad.</h2>");
}

// Cargar parámetros de wp-config.php
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

    // Buscar al usuario y verificar si aún no está aprobado (verificado = 0)
    $stmt = $pdo->prepare("SELECT nombre, verificado FROM usuarios WHERE id_usuario = :id LIMIT 1");
    $stmt->execute([':id' => $id_usuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        if ($usuario['verificado'] == 1) {
            echo "<h2 style='color: #aeecff; text-align:center; font-family:sans-serif; margin-top:100px;'>⚠️ Esta cuenta ya se encontraba verificada previamente.</h2>";
            exit;
        }

        // Actualizar columna verificado a 1
        $update = $pdo->prepare("UPDATE usuarios SET verificado = 1 WHERE id_usuario = :id");
        $update->execute([':id' => $id_usuario]);

        echo "
        <div style='max-width:500px; margin: 100px auto; font-family: sans-serif; border: 1px solid #1E293B; background-color: #0D1425; color: #ffffff; padding: 30px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.5);'>
            <h2 style='color: #4ade80; letter-spacing: 0.05em;'>✔️ ¡CUENTA ACTIVADA!</h2>
            <p style='color: #94A3B8; font-size: 14px; margin-top: 15px;'>El operador <strong>{$usuario['nombre']}</strong> (ID: $id_usuario) ha sido aprobado y verificado de forma exitosa.</p>
            <p style='font-size: 11px; color: #64748B; margin-top:25px;'>Ya puede iniciar sesión de forma normal en el portal corporativo.</p>
        </div>";
    } else {
        echo "<h2 style='color: #f87171; text-align:center; font-family:sans-serif; margin-top:100px;'>⚠️ El usuario solicitado no existe en la base de datos.</h2>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color: red; text-align:center; font-family:sans-serif; margin-top:100px;'>❌ Error de infraestructura en la base de datos.</h2>";
}