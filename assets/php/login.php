<?php
session_start();

// Si el usuario ya está autenticado
if (isset($_SESSION['id_usuario']) && isset($_SESSION['id_rol'])) {
    $redirect = (intval($_SESSION['id_rol']) === 3) ? '/pages/Dashboard.php' : '/pages/PortalClientes.html';
    echo json_encode(['status' => 'success', 'message' => 'Sesión activa detectada.', 'redirect' => $redirect]);
    exit;
}

header('Content-Type: application/json');

// 1. Extraer credenciales desde wp-config.php de Bluehost
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
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error de infraestructura: No se encontró el archivo de configuración base.']);
    exit;
}

// 2. Procesar el envío de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = filter_input(INPUT_POST, 'username', FILTER_VALIDATE_EMAIL);
    $pass_input = $_POST['password'] ?? '';

    if (!$user_input || empty($pass_input)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, rellene todos los campos con un formato de correo válido.']);
        exit;
    }

    try {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password_db, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // 3. Consultar usuario e inyectar el campo id_rol de la relación
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, email, password, verificado, id_rol FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $user_input]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // 4. Verificar firma de la contraseña
            if (password_verify($pass_input, $usuario['password'])) {
                
                // 5. Comprobar estado de aprobación manual
                if (intval($usuario['verificado']) === 1) {
                    
                    // Inicializar variables seguras de sesión en servidor
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['nombre_usuario'] = $usuario['nombre'];
                    $_SESSION['id_rol'] = intval($usuario['id_rol']);
                    $_SESSION['ultimo_acceso'] = time();

                    // Definición dinámica del destino según su nivel de privilegios
                    if (intval($usuario['id_rol']) === 3) {
                        $redirectUrl = '/pages/Dashboard.php';
                    } else {
                        $redirectUrl = '/pages/PortalClientes.html';
                    }
                    
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Autenticación concedida. Accediendo al sistema.',
                        'redirect' => $redirectUrl
                    ]);
                    exit;
                    
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Tu cuenta aún no ha sido aprobada. El equipo técnico de soporte debe verificar tu solicitud.'
                    ]);
                    exit;
                }
                
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Las credenciales introducidas son incorrectas.']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Las credenciales introducidas son incorrectas.']);
            exit;
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al interactuar con el sistema de base de datos.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de petición no permitido.']);
}   