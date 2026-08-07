<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/correo.php';

// Cargar parámetros de wp-config.php
$wp_config_path = __DIR__ . '/../../wp-config.php';
$database = '';
$username = '';
$password_db = '';
$host = 'localhost';

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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
        exit;
    }

    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, ingresa un correo electrónico válido.']);
        exit;
    }

    // 1. Verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT id_usuario, nombre FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(['status' => 'error', 'message' => 'No existe ninguna cuenta vinculada a este correo.']);
        exit;
    }

    // 2. Generar código PIN de 6 dígitos y expiración (15 minutos)
    $codigo = sprintf("%06d", random_int(0, 999999));
    $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // 3. Guardar código en la tabla usuarios
    $pdo->beginTransaction();
    $update = $pdo->prepare("UPDATE usuarios SET reset_code = :codigo, reset_expires = :expiracion WHERE email = :email");
    $update->execute([
        ':codigo' => $codigo,
        ':expiracion' => $expiracion,
        ':email' => $email
    ]);

    // 4. Preparar el correo
    $nombreCliente = !empty($usuario['nombre']) ? $usuario['nombre'] : 'Usuario';
    $asunto = "Código de Verificación - Restablecer Contraseña";
    // 5. Enviar directamente mediante mail() en el hosting.
    $cuerpoHtml = '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#0f172a">'
        . '<h2>Restablecer contrase&ntilde;a</h2>'
        . '<p>Hola ' . htmlspecialchars($nombreCliente, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>Has solicitado restablecer la contrase&ntilde;a de tu cuenta.</p>'
        . '<p>Tu c&oacute;digo de verificaci&oacute;n es:</p>'
        . '<p style="font-size:26px;font-weight:bold;letter-spacing:6px">'
        . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p>Este c&oacute;digo expira en 15 minutos.</p>'
        . '<p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>'
        . '</body></html>';

    if (!enviarCorreoDirecto($email, $asunto, $cuerpoHtml)) {
        throw new RuntimeException('La función mail() no aceptó el mensaje.');
    }
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'El código de verificación ha sido enviado a tu correo.'
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Landing NordicTech][solicitar_codigo] ' . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'No fue posible enviar el código. Intenta nuevamente más tarde.'
    ]);
    exit;
}
