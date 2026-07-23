<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// -------------------------------------------------------------------------
// VALIDACIÓN DE SESIÓN
// -------------------------------------------------------------------------
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado por falta de sesión activa.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------------------------------------------------------------
    // PARSER FLEXIBLE DE PARÁMETROS
    // -------------------------------------------------------------------------
    $input_raw = file_get_contents('php://input');
    $data_json = json_decode($input_raw, true);

    if (is_array($data_json)) {
        $params = array_merge($_REQUEST, $_POST, $data_json);
    } else {
        $params = array_merge($_REQUEST, $_POST);
    }

    $id_ticket     = intval($params['id_ticket'] ?? $params['id'] ?? 0);
    $accion        = trim($params['accion'] ?? $params['action'] ?? 'avance');
    $comentario    = trim($params['comentario'] ?? $params['observaciones'] ?? '');
    $enviar_correo = intval($params['enviar_correo'] ?? 1);

    if ($id_ticket <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Identificador de ticket no válido.']);
        exit;
    }

    if (empty($comentario)) {
        echo json_encode(['status' => 'error', 'message' => 'Debes ingresar un comentario o detalle de la actividad.']);
        exit;
    }

    // -------------------------------------------------------------------------
    // LECTURA DE CREDENCIALES DESDE wp-config.php
    // -------------------------------------------------------------------------
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
        echo json_encode(['status' => 'error', 'message' => 'Archivo wp-config.php no encontrado.']);
        exit;
    }

    // -------------------------------------------------------------------------
    // BLOQUE 1: CONEXIÓN A BD
    // -------------------------------------------------------------------------
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password_db, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Fallo al conectar con BD: ' . $e->getMessage()]);
        exit;
    }

    // Configurar Zona Horaria y Estampa
    date_default_timezone_set('America/El_Salvador');
    $fechaActual = date('d/m/Y h:i A');

    // Consultar el nombre del Técnico activo en la sesión
    $id_tecnico_sesion = intval($_SESSION['id_usuario']);
    $stmt_tecnico = $pdo->prepare("SELECT nombre FROM usuarios WHERE id_usuario = ?");
    $stmt_tecnico->execute([$id_tecnico_sesion]);
    $tecnico_data = $stmt_tecnico->fetch();
    $nombre_tecnico = !empty($tecnico_data['nombre']) ? trim($tecnico_data['nombre']) : 'Técnico';

    // Limpiar si el comentario ya traía alguna estampa previa con corchetes
    $comentario_limpio = preg_replace('/^\[.*?\]\s*-\s*/', '', $comentario);

    // FORMATO FINAL: [23/07/2026 08:52 AM] - [Técnico: Nombre] - Comentario
    $comentario_final = "[$fechaActual] - Técnico:  " . $comentario_limpio;

    // -------------------------------------------------------------------------
    // BLOQUE 2: PROCESAMIENTO Y TRANSACCIÓN
    // -------------------------------------------------------------------------
    try {
        $stmt_consulta = $pdo->prepare("SELECT id_usuario, titulo, estado, observacion_proceso, observacion_cierre FROM tickets WHERE id_ticket = ?");
        $stmt_consulta->execute([$id_ticket]);
        $ticket_actual = $stmt_consulta->fetch();

        if (!$ticket_actual) {
            echo json_encode(['status' => 'error', 'message' => 'El ticket especificado no existe.']);
            exit;
        }

        $id_cliente = intval($ticket_actual['id_usuario']);
        $titulo_ticket = $ticket_actual['titulo'] ?? 'Sin Título';
        $observacion_proceso_anterior = $ticket_actual['observacion_proceso'] ?? '';
        $observacion_cierre_anterior = $ticket_actual['observacion_cierre'] ?? '';

        $pdo->beginTransaction();

        if ($accion === 'resolver') {
            $observacion_cierre_final = empty($observacion_cierre_anterior)
                ? $comentario_final
                : $observacion_cierre_anterior . "\n" . $comentario_final;

            $stmt = $pdo->prepare("UPDATE tickets SET estado = 'Resuelto', observacion_cierre = :obs, fecha_actualizacion = NOW() WHERE id_ticket = :id");
            $stmt->execute([':obs' => $observacion_cierre_final, ':id' => $id_ticket]);

            $mensaje_db = 'Ticket marcado como Resuelto con éxito.';
            $asunto = "Incidente RESUELTO - Ticket #TK-{$id_ticket}";
            $status_title = "El incidente ha sido marcado como RESUELTO";
            $status_color = "#22c55e";

        } elseif ($accion === 'cerrar') {
            $observacion_cierre_final = empty($observacion_cierre_anterior)
                ? $comentario_final
                : $observacion_cierre_anterior . "\n" . $comentario_final;

            $stmt = $pdo->prepare("UPDATE tickets SET estado = 'Cerrado', observacion_cierre = :obs, fecha_actualizacion = NOW() WHERE id_ticket = :id");
            $stmt->execute([':obs' => $observacion_cierre_final, ':id' => $id_ticket]);

            $mensaje_db = 'Ticket cerrado definitivamente.';
            $asunto = "Incidente CERRADO - Ticket #TK-{$id_ticket}";
            $status_title = "El caso ha sido CERRADO de forma definitiva";
            $status_color = "#64748b";

        } else { // Avance en proceso
            $observacion_proceso_final = empty($observacion_proceso_anterior)
                ? $comentario_final
                : $observacion_proceso_anterior . "\n" . $comentario_final;

            $stmt = $pdo->prepare("UPDATE tickets SET estado = 'En Proceso', observacion_proceso = :obs, fecha_actualizacion = NOW() WHERE id_ticket = :id");
            $stmt->execute([':obs' => $observacion_proceso_final, ':id' => $id_ticket]);

            $mensaje_db = 'Avance registrado en bitácora correctamente.';
            $asunto = "Actualización de Avance - Ticket #TK-{$id_ticket}";
            $status_title = "El incidente se encuentra actualmente EN PROCESO";
            $status_color = "#0284c7";
        }

        // -------------------------------------------------------------------------
        // BLOQUE 3: REGISTRO EN COLA DE CORREOS
        // -------------------------------------------------------------------------
        if ($enviar_correo === 1 && $id_cliente > 0) {
            $stmt_usuario = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id_usuario = ?");
            $stmt_usuario->execute([$id_cliente]);
            $datos_usuario = $stmt_usuario->fetch();

            if ($datos_usuario && !empty($datos_usuario['email'])) {
                $correo_destino = $datos_usuario['email'];
                $nombre_cliente = htmlspecialchars($datos_usuario['nombre'] ?? 'Cliente');

                $comentario_html = "<div style='background-color: #f8fafc; border-left: 4px solid {$status_color}; padding: 12px; margin: 15px 0; font-family: monospace; font-size: 13px; color: #334155; white-space: pre-wrap;'>\" " . nl2br(htmlspecialchars($comentario_final)) . " \"</div>";

                $cuerpo_correo = "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'></head>
<body style='margin: 0; padding: 0; background-color: #101729; font-family: sans-serif; color: #ffffff;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #060913; padding: 40px 20px;'>
        <tr><td align='center'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #0D1425; border: 1px solid #1E293B; border-top: 4px solid #2A4094; padding: 40px 30px;'>
                <tr>
                    <td align='center' style='padding-bottom: 20px;'>
                        <h2 style='color: #ffffff; margin: 0;'>NordicTech El Salvador</h2>
                    </td>
                </tr>
                <tr>
                    <td style='font-size: 14px; color: #ffffff; padding-bottom: 20px;'>
                        Estimado/a <strong>{$nombre_cliente}</strong>,<br><br>
                        Se ha registrado una nueva actualización por parte de nuestro equipo técnico.
                    </td>
                </tr>
                <tr>
                    <td style='padding-bottom: 20px;'>
                        <div style='padding: 12px; background-color: #060913; text-align: center; font-weight: bold; color: {$status_color}; font-size: 13px;'>
                            {$status_title}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style='padding-bottom: 20px;'>
                        <strong>Ticket:</strong> #TK-{$id_ticket}<br>
                        <strong>Incidente:</strong> " . htmlspecialchars($titulo_ticket) . "<br>
                        <strong>Técnico Encargado:</strong> " . htmlspecialchars($nombre_tecnico) . "<br>
                        <strong>Fecha:</strong> {$fechaActual}
                    </td>
                </tr>
                <tr>
                    <td style='padding-bottom: 20px;'>
                        <p style='font-size: 13px; font-weight: bold; margin: 0 0 10px 0;'>Notas de la actualización:</p>
                        {$comentario_html}
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>";

                $sql_cola = "INSERT INTO cola_correos (id_ticket, destinatario, asunto, cuerpo, estado) VALUES (?, ?, ?, ?, 'pendiente')";
                $stmt_cola = $pdo->prepare($sql_cola);
                $stmt_cola->execute([$id_ticket, $correo_destino, $asunto, $cuerpo_correo]);
            }
        }

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => $mensaje_db
        ]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Error en base de datos: ' . $e->getMessage()]);
        exit;
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de petición no válido.']);
    exit;
}