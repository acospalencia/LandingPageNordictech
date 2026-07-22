<?php
header('Content-Type: application/json; charset=utf-8');

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
    $update = $pdo->prepare("UPDATE usuarios SET reset_code = :codigo, reset_expires = :expiracion WHERE email = :email");
    $update->execute([
        ':codigo' => $codigo,
        ':expiracion' => $expiracion,
        ':email' => $email
    ]);

    // 4. Preparar el correo
    $nombreCliente = !empty($usuario['nombre']) ? $usuario['nombre'] : 'Usuario';
    $asunto = "Código de Verificación - Restablecer Contraseña";
    $cuerpo = "Hola {$nombreCliente},\n\n"
            . "Has solicitado restablecer la contraseña de tu cuenta.\n\n"
            . "Tu código de verificación es: {$codigo}\n\n"
            . "Este código expira en 15 minutos.\n"
            . "Si no solicitaste este cambio, puedes ignorar este mensaje de forma segura.";

    // 5. Insertar en la cola de correos para el Cron Job
    $insertQueue = $pdo->prepare("
        INSERT INTO cola_correos (id_ticket, destinatario, asunto, cuerpo, estado, intentos, fecha_registro) 
        VALUES (NULL, :destinatario, :asunto, :cuerpo, 'pendiente', 0, NOW())
    ");
    $insertQueue->execute([
        ':destinatario' => $email,
        ':asunto' => $asunto,
        ':cuerpo' => $cuerpo
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'El código de verificación ha sido enviado a tu correo.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Fallo de conexión: ' . $e->getMessage(),
        'debug' => [
            'host' => $host,
            'db' => $database,
            'user' => $username,
            'config_existe' => file_exists($wp_config_path)
        ]
    ]);
    exit;
}