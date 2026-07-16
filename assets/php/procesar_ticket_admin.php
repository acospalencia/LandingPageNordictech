<?php
session_start();
header('Content-Type: application/json');

// Comprobar privilegios de nivel 3 (Administrador)
if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_rol']) !== 3) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado por insuficiencia de privilegios.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ticket = intval($_POST['id_ticket'] ?? 0);
    $estado = trim($_POST['estado'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Validación contra el ENUM exacto de tu tabla de tickets
    if ($id_ticket === 0 || !in_array($estado, ['En Proceso', 'Resuelto', 'Cerrado'])) {
        echo json_encode(['status' => 'error', 'message' => 'Parámetros del ticket corruptos o fuera del estándar de red.']);
        exit;
    }

    // Validación obligatoria de explicaciones para Resueltos y Cerrados
    if (in_array($estado, ['Resuelto', 'Cerrado']) && empty($observaciones)) {
        echo json_encode(['status' => 'error', 'message' => 'Es obligatorio registrar la bitácora técnica de resolución.']);
        exit;
    }

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

        if ($estado === 'En Proceso') {
            // Se actualiza a "En Proceso" y se escribe en observacion_proceso si se ingresó algo
            $sql = "UPDATE tickets 
                    SET estado = :estado, observacion_proceso = :observaciones, fecha_actualizacion = NOW() 
                    WHERE id_ticket = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':estado'        => $estado,
                ':observaciones' => !empty($observaciones) ? $observaciones : null,
                ':id'            => $id_ticket
            ]);
            $mensaje = 'Ticket asignado al área técnica bajo estado En Proceso con éxito.';
        } else {
            // Se actualiza a "Resuelto" o "Cerrado" guardando el descargo técnico en observacion_cierre
            $sql = "UPDATE tickets 
                    SET estado = :estado, observacion_cierre = :observaciones, fecha_actualizacion = NOW() 
                    WHERE id_ticket = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':estado'        => $estado,
                ':observaciones' => $observaciones,
                ':id'            => $id_ticket
            ]);
            $mensaje = 'Operación procesada con éxito. Incidente guardado en el archivo histórico.';
        }

        echo json_encode(['status' => 'success', 'message' => $mensaje]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Fallo interno de comunicación con el nodo de base de datos.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Petición incorrecta.']);
    exit;
}