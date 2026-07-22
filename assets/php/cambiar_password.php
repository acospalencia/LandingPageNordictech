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
    $codigo = trim($_POST['codigo'] ?? '');
    $nueva_password = $_POST['nueva_password'] ?? '';

    if (!$email || empty($codigo) || strlen($nueva_password) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor completa todos los campos correctamente (Mínimo 6 caracteres).']);
        exit;
    }

    // 1. Validar código y tiempo de expiración
    $stmt = $pdo->prepare("
        SELECT id_usuario 
        FROM usuarios 
        WHERE email = :email 
          AND reset_code = :codigo 
          AND reset_expires > NOW() 
        LIMIT 1
    ");
    $stmt->execute([
        ':email' => $email,
        ':codigo' => $codigo
    ]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'El código de verificación es incorrecto o ya ha expirado.']);
        exit;
    }

    // 2. Encriptar nueva contraseña
    $password_hashed = password_hash($nueva_password, PASSWORD_BCRYPT);

    // 3. Actualizar contraseña y limpiar reset_code y reset_expires
    $update = $pdo->prepare("
        UPDATE usuarios 
        SET password = :password, 
            reset_code = NULL, 
            reset_expires = NULL 
        WHERE id_usuario = :id
    ");
    $update->execute([
        ':password' => $password_hashed,
        ':id' => $user['id_usuario']
    ]);

    echo json_encode([
        'status' => 'success', 
        'message' => '¡Contraseña actualizada con éxito! Redirigiendo al inicio de sesión...'
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Fallo de conexión a la base de datos.']);
    exit;
}