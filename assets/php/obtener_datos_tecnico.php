<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida.']);
    exit;
}

$idTecnico = intval($_SESSION['id_usuario']);

// Cargar credenciales desde wp-config.php
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
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión con la configuración base.']);
    exit;
}

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password_db, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. SOLAMENTE clientes con tickets asignados a este técnico
    $sqlClientes = "
        SELECT DISTINCT u.id_usuario, u.nombre, u.email
        FROM usuarios u
        INNER JOIN tickets t ON t.id_usuario = u.id_usuario
        WHERE t.id_tecnico = :id_tecnico
    ";
    $stmtClientes = $pdo->prepare($sqlClientes);
    $stmtClientes->execute(['id_tecnico' => $idTecnico]);
    $clientes = $stmtClientes->fetchAll();

    // 2. SOLAMENTE tickets EN PROCESO + notas de la tabla asignaciones_tickets (con MAX para compatibilidad ONLY_FULL_GROUP_BY)
    $sqlProceso = "
        SELECT t.*, u.nombre AS cliente_nombre,
               MAX(a.notas_asignacion) AS notas_asignacion
        FROM tickets t
        INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
        LEFT JOIN asignaciones_tickets a ON a.id_ticket = t.id_ticket AND a.id_tecnico = t.id_tecnico
        WHERE t.id_tecnico = :id_tecnico 
          AND t.estado IN ('En Proceso')
        GROUP BY t.id_ticket
        ORDER BY t.id_ticket DESC
    ";
    $stmtProceso = $pdo->prepare($sqlProceso);
    $stmtProceso->execute(['id_tecnico' => $idTecnico]);
    $ticketsProceso = $stmtProceso->fetchAll();

    // 3. SOLAMENTE tickets CERRADOS + notas de la tabla asignaciones_tickets (con MAX para compatibilidad ONLY_FULL_GROUP_BY)
    $sqlCerrados = "
        SELECT t.*, u.nombre AS cliente_nombre,
               MAX(a.notas_asignacion) AS notas_asignacion
        FROM tickets t
        INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
        LEFT JOIN asignaciones_tickets a ON a.id_ticket = t.id_ticket AND a.id_tecnico = t.id_tecnico
        WHERE t.id_tecnico = :id_tecnico 
          AND t.estado IN ('Cerrado', 'Resuelto') 
        GROUP BY t.id_ticket
        ORDER BY t.id_ticket DESC
    ";
    $stmtCerrados = $pdo->prepare($sqlCerrados);
    $stmtCerrados->execute(['id_tecnico' => $idTecnico]);
    $ticketsCerrados = $stmtCerrados->fetchAll();

    echo json_encode([
        'status' => 'success',
        'clientes' => $clientes,
        'tickets_proceso' => $ticketsProceso,
        'tickets_cerrados' => $ticketsCerrados
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en base de datos: ' . $e->getMessage()]);
}