<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no autorizada.']);
    exit;
}

$idTecnico = intval($_SESSION['id_usuario']);

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
    echo json_encode(['status' => 'error', 'message' => 'Error al leer la configuración del servidor.']);
    exit;
}

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password_db, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. Obtener ÚNICAMENTE clientes que tengan tickets asignados a este técnico
    $sqlClientes = "
        SELECT DISTINCT u.id_usuario, u.nombre, u.email, u.empresa
        FROM usuarios u
        INNER JOIN tickets t ON t.id_cliente = u.id_usuario
        WHERE t.id_tecnico = :id_tecnico
        ORDER BY u.nombre ASC
    ";
    $stmtClientes = $pdo->prepare($sqlClientes);
    $stmtClientes->execute(['id_tecnico' => $idTecnico]);
    $clientes = $stmtClientes->fetchAll();

    // 2. Obtener ÚNICAMENTE los tickets asignados a este técnico
    $sqlTickets = "
        SELECT t.*, u.nombre AS cliente_nombre, u.email AS cliente_email
        FROM tickets t
        INNER JOIN usuarios u ON t.id_cliente = u.id_usuario
        WHERE t.id_tecnico = :id_tecnico
        ORDER BY t.id_ticket DESC
    ";
    $stmtTickets = $pdo->prepare($sqlTickets);
    $stmtTickets->execute(['id_tecnico' => $idTecnico]);
    $tickets = $stmtTickets->fetchAll();

    echo json_encode([
        'status' => 'success',
        'clientes' => $clientes,
        'tickets' => $tickets
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
}