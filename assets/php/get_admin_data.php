<?php
session_start();
header('Content-Type: application/json');

// Comprobar privilegios de nivel 3 (Administrador)
if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_rol']) !== 3) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

$action = $_GET['action'] ?? '';

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

    if ($action === 'get_clientes') {
        // Filtrar solo usuarios cuyo id_rol sea exactamente 1 (Cliente)
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, email, codigo_empresa FROM usuarios WHERE id_rol = 1 ORDER BY nombre ASC");
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $clientes]);
        exit;
    } 
    
    elseif ($action === 'get_tickets') {
        $id_usuario = intval($_GET['id_usuario'] ?? 0);
        
        if ($id_usuario === 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de operador inválido.']);
            exit;
        }

        // Obtener la bitácora completa de tickets de este cliente
        $stmt = $pdo->prepare("SELECT id_ticket, titulo, descripcion, estado, prioridad, observacion_proceso, observacion_cierre, fecha_creacion, fecha_actualizacion FROM tickets WHERE id_usuario = :id ORDER BY id_ticket DESC");
        $stmt->execute([':id' => $id_usuario]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $tickets]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Fallo de consulta de red.']);
    exit;
}