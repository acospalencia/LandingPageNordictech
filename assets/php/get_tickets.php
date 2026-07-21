<?php
session_start();
header('Content-Type: application/json');
// Evitar que el navegador guarde en caché la lista de tickets
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Configuración del límite de tiempo (60 minutos = 3600 segundos)
define('LIMITE_INACTIVIDAD', 3600);

// 1. Validar que la sesión básica exista
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'session_expired', 'message' => 'Acceso denegado. Se requiere iniciar sesión.']);
    exit;
}

// 2. Control del ciclo de vida de la sesión (Expiración de 60 minutos)
if (isset($_SESSION['ultimo_acceso'])) {
    $tiempo_inactivo = time() - $_SESSION['ultimo_acceso'];
    
    if ($tiempo_inactivo >= LIMITE_INACTIVIDAD) {
        // La sesión expiró: Limpiar y destruir
        session_unset();
        session_destroy();
        
        echo json_encode([
            'status' => 'session_expired', 
            'message' => 'Conexión finalizada por inactividad. El token de seguridad expiró tras 60 minutos.'
        ]);
        exit;
    }
}

// Actualizar la marca de tiempo de la última interacción del usuario
$_SESSION['ultimo_acceso'] = time();

$id_usuario = $_SESSION['id_usuario'];

// 3. Extraer credenciales desde wp-config.php
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
    echo json_encode(['status' => 'error', 'message' => 'Error: Archivo de configuración de base de datos inalcanzable.']);
    exit;
}

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // SE AGREGA AL SELECT: observacion_proceso y observacion_cierre para la bitácora técnica
    $query = "SELECT 
                id_ticket, 
                titulo, 
                descripcion, 
                estado, 
                prioridad, 
                observacion_proceso, 
                observacion_cierre,
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha 
              FROM tickets 
              WHERE id_usuario = :id_usuario 
                AND estado IN ('Abierto', 'En Proceso', 'Resuelto', 'Cerrado')
              ORDER BY fecha_creacion DESC";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id_usuario' => $id_usuario]);
    $tickets = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'nombre_usuario' => $_SESSION['nombre_usuario'] ?? 'Cliente NordicTech',
        'tickets' => $tickets
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Fallo de enlace con la base de datos.']);
}