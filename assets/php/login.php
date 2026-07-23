<?php
session_start();
header('Content-Type: application/json');

// Función auxiliar para determinar la ruta de redirección según el rol
function obtenerRutaRedireccion($idRol) {
    switch (intval($idRol)) {
        case 3:
            return '/pages/Dashboard.php';       // Administrador
        case 2:
            return '/pages/Tecnicos.php';        // Técnico
        case 1:
        default:
            return '/pages/PortalClientes.php';   // Cliente
    }
}

// Si el usuario ya está autenticado en sesión activa
if (isset($_SESSION['id_usuario']) && isset($_SESSION['id_rol'])) {
    $redirect = obtenerRutaRedireccion($_SESSION['id_rol']);
    echo json_encode(['status' => 'success', 'message' => 'Sesión activa detectada.', 'redirect' => $redirect]);
    exit;
}

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
    // Obtenemos el valor de entrada sin forzar validación de formato de correo
    $user_input = trim($_POST['username'] ?? '');
    $pass_input = $_POST['password'] ?? '';

    if (empty($user_input) || empty($pass_input)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, rellene todos los campos.']);
        exit;
    }

    try {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password_db, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);


        // 3. Consultar coincidencia por EMAIL o por NOMBRE
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, email, password, verificado, id_rol FROM usuarios WHERE email = :input OR nombre = :input");
        $stmt->execute(['input' => $user_input]);
        $candidatos = $stmt->fetchAll();

        $usuario = null;

        // Validar contraseña entre las coincidencias obtenidas
        foreach ($candidatos as $c) {
            if (password_verify($pass_input, $c['password'])) {
                $usuario = $c;
                break;
            }
        }

        if ($usuario) {
            // 4. Comprobar estado de aprobación manual
            if (intval($usuario['verificado']) === 1) {
                
                // Inicializar variables seguras de sesión
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre'];
                $_SESSION['id_rol'] = intval($usuario['id_rol']);
                $_SESSION['ultimo_acceso'] = time();

                // Definición dinámica del destino según su rol (Admin = 3, Técnico = 2, Cliente = 1)
                $redirectUrl = obtenerRutaRedireccion($usuario['id_rol']);
                
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

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al interactuar con el sistema de base de datos. Error PDO: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de petición no permitido.']);
}