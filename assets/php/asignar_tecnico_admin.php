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
    $id_tecnico = intval($_POST['id_tecnico'] ?? 0);
    $notas_transferencia = trim($_POST['notas_transferencia'] ?? $_POST['observaciones'] ?? '');

    if ($id_ticket === 0 || $id_tecnico === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Selección de ticket o técnico inválida.']);
        exit;
    }

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
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Fallo de conexión a la BD.', 'debug' => $e->getMessage()]);
        exit;
    }

    date_default_timezone_set('America/El_Salvador');
    $fechaActual = date('d/m/Y h:i A');

    try {
        // 1. Obtener el nombre del técnico seleccionado
        $stmt_tec = $pdo->prepare("SELECT nombre FROM usuarios WHERE id_usuario = ? AND id_rol = 2");
        $stmt_tec->execute([$id_tecnico]);
        $tecnico = $stmt_tec->fetch();

        if (!$tecnico) {
            echo json_encode(['status' => 'error', 'message' => 'El técnico seleccionado no existe o no es un técnico válido.']);
            exit;
        }

        $nombre_tecnico = $tecnico['nombre'];

        // 2. Consultar las observaciones de proceso actuales del ticket
        $stmt_ticket = $pdo->prepare("SELECT observacion_proceso FROM tickets WHERE id_ticket = ?");
        $stmt_ticket->execute([$id_ticket]);
        $ticket_actual = $stmt_ticket->fetch();

        if (!$ticket_actual) {
            echo json_encode(['status' => 'error', 'message' => 'El ticket no existe en el sistema.']);
            exit;
        }

        $observacion_proceso_anterior = $ticket_actual['observacion_proceso'] ?? '';

        // 3. Registrar en la tabla de asignaciones (aquí se guardan las NOTAS)
        $sql_asig = "INSERT INTO asignaciones_tickets (id_ticket, id_tecnico, notas_asignacion, fecha_asignacion) 
                     VALUES (:id_ticket, :id_tecnico, :notas_asignacion, NOW())";
        $stmt_asig = $pdo->prepare($sql_asig);
        $stmt_asig->execute([
            ':id_ticket'        => $id_ticket,
            ':id_tecnico'       => $id_tecnico,
            ':notas_asignacion' => $notas_transferencia
        ]);

        // 4. Formatear la leyenda para la bitácora del ticket (SIN incluir la nota)
        $comentario_ticket = "[$fechaActual] - El ticket se ha asignado a el técnico $nombre_tecnico";
        $observacion_proceso_final = empty($observacion_proceso_anterior) 
            ? $comentario_ticket 
            : $observacion_proceso_anterior . "\n" . $comentario_ticket;

        // 5. Actualizar el ticket
        $sql_upd = "UPDATE tickets 
                    SET estado = 'En Proceso', 
                        id_tecnico = :id_tecnico,
                        observacion_proceso = :observacion_proceso, 
                        fecha_actualizacion = NOW() 
                    WHERE id_ticket = :id_ticket";
        $stmt_upd = $pdo->prepare($sql_upd);
        $stmt_upd->execute([
            ':id_tecnico'          => $id_tecnico,
            ':observacion_proceso' => $observacion_proceso_final,
            ':id_ticket'           => $id_ticket
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => "El ticket #TK-{$id_ticket} se ha asignado correctamente a {$nombre_tecnico}."
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Error al ejecutar la asignación en la base de datos.',
            'debug'   => $e->getMessage()
        ]);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Petición incorrecta.']);
    exit;
}