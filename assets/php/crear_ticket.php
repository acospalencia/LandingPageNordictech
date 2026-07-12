<?php
session_start();
header('Content-Type: application/json');

// 1. Protección perimetral: Validar que la sesión esté viva
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'session_expired', 'message' => 'Acceso denegado. Requiere autenticación activa.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['id_usuario'];
    $titulo = trim($_POST['titulo'] ?? '');
    $prioridad = trim($_POST['prioridad'] ?? 'Media');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado = 'Abierto'; // Estado por defecto para nuevos incidentes

    if (empty($titulo) || empty($descripcion)) {
        echo json_encode(['status' => 'error', 'message' => 'El título y la descripción técnica son mandatorios.']);
        exit;
    }

    // 2. Extraer parámetros desde wp-config.php de manera transparente
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
        echo json_encode(['status' => 'error', 'message' => 'Fallo de infraestructura: wp-config inalcanzable.']);
        exit;
    }

    try {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // 3. Inserción parametrizada anti Inyección SQL
        $query = "INSERT INTO tickets (id_usuario, titulo, descripcion, estado, prioridad, fecha_creacion) 
                  VALUES (:id_usuario, :titulo, :descripcion, :estado, :prioridad, NOW())";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':titulo' => $titulo,
            ':prioridad' => $prioridad,
            ':descripcion' => $descripcion,
            ':estado' => $estado
        ]);

        echo json_encode([
            'status' => 'success', 
            'message' => 'Ticket de soporte aperturado y asignado a tu cuenta de forma exitosa.'
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Fallo al interactuar con el servidor.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Petición denegada por protocolo de red.']);
    exit;
}