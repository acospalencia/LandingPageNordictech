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
    preg_match("/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_name);
    preg_match("/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_user);
    preg_match("/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_password);
    preg_match("/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_host);

    $database    = isset($db_name[1]) ? trim($db_name[1]) : '';
    $username    = isset($db_user[1]) ? trim($db_user[1]) : '';
    $password_db = isset($db_password[1]) ? trim($db_password[1]) : '';
    $host        = isset($db_host[1]) ? trim($db_host[1]) : 'localhost';
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error: Archivo de configuración inalcanzable.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password_db, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    if ($action === 'get_clientes') {
        // Filtrar solo clientes (id_rol = 1)
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, email, codigo_empresa FROM usuarios WHERE id_rol = 1 ORDER BY nombre ASC");
        $stmt->execute();
        $clientes = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $clientes]);
        exit;
    } 
    
    elseif ($action === 'get_tecnicos') {
        // Filtrar solo técnicos (id_rol = 2) - Se eliminó la columna "identificador" de aquí
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, email FROM usuarios WHERE id_rol = 2 ORDER BY nombre ASC");
        $stmt->execute();
        $tecnicos = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $tecnicos]);
        exit;
    }

    elseif ($action === 'get_tickets') {
        $id_usuario = intval($_GET['id_usuario'] ?? 0);
        
        if ($id_usuario === 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de operador inválido.']);
            exit;
        }

        // Se corrigió la coma sobrante después de nombre_tecnico
        $sql = "SELECT 
                    t.id_ticket, 
                    t.id_tecnico, 
                    t.id_usuario, 
                    t.titulo, 
                    t.descripcion, 
                    t.estado, 
                    t.prioridad, 
                    t.observacion_proceso, 
                    t.observacion_cierre, 
                    t.fecha_creacion, 
                    t.fecha_actualizacion,
                    tec.nombre AS nombre_tecnico
                FROM tickets t
                LEFT JOIN usuarios tec ON t.id_tecnico = tec.id_usuario
                WHERE t.id_usuario = :id 
                ORDER BY t.id_ticket DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_usuario]);
        $tickets = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $tickets]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Acción no especificada o no válida.']);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error', 
        'message' => 'Fallo de consulta de red.',
        'debug'   => $e->getMessage()
    ]);
    exit;
}