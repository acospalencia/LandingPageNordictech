<?php
// Iniciar sesión para mantener al usuario autenticado en el servidor
session_start();
header('Content-Type: application/json');

// 1. Extraer credenciales desde el archivo wp-config.php de forma segura
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
    echo json_encode(['status' => 'error', 'message' => 'Error de infraestructura: No se encontró el archivo de configuración base.']);
    exit;
}

// 2. Procesar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = filter_input(INPUT_POST, 'username', FILTER_VALIDATE_EMAIL);
    $pass_input = $_POST['password'] ?? '';

    if (!$user_input || empty($pass_input)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, rellene todos los campos con un formato de correo válido.']);
        exit;
    }

    try {
        // Enlace mediante PDO a la base de datos de Bluehost
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // 3. Consultar si el usuario existe en la tabla nueva
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, email, password, verificado FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $user_input]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // 4. Verificar si la contraseña coincide (Encriptación bcrypt estándar)
            if (password_verify($pass_input, $usuario['password'])) {
                
                // 5. EVALUACIÓN DEL CAMPO VERIFICADO (Tu requerimiento central)
                if ((int)$usuario['verificado'] === 1) {
                    
                    // Guardamos los datos en la sesión global del servidor
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['nombre_usuario'] = $usuario['nombre'];
                    $_SESSION['ultimo_acceso'] = time();
                    
                    // Enviamos estatus exitoso y la ruta hacia donde debe redirigir el JavaScript
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Autenticación concedida. Bienvenido al portal de clientes.',
                        'redirect' => '/../../pages/PortalClientes.html?v=' . time() // Modifica esta ruta según tu estructura final del portal
                    ]);
                    exit;
                    
                } else {
                    // Si el usuario existe y la clave está bien, pero no ha sido verificado por el administrador:
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Tu cuenta aun no ha sido verificada. El equipo técnico de NordicTech debe verificar tu cuenta corporativa.'
                    ]);
                    exit;
                }
                
            } else {
                // Contraseña inválida
                echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Las credenciales estan incorrectas.']);
                exit;
            }
        } else {
            // Correo electrónico no registrado
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Las credenciales estan incorrectas.']);
            exit;
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error con los servicios de base de datos.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de transferencia de datos no permitido.']);
}