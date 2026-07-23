<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Comprobar privilegios de nivel 3 (Administrador)
if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_rol'] ?? 0) !== 3) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado por insuficiencia de privilegios.']);
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

    $action             = trim($params['action'] ?? '');
    $id_ticket          = intval($params['id_ticket'] ?? $params['id'] ?? $params['ticket_id'] ?? 0);
    $id_tecnico         = intval($params['id_tecnico'] ?? $params['id_tecnico_asignado'] ?? 0);
    $notas_tecnico      = trim($params['notas_tecnico'] ?? $params['notas_asignacion'] ?? '');
    $comentario_cliente = trim($params['comentario_cliente'] ?? $params['observaciones'] ?? $params['notas'] ?? '');
    $estado_raw         = trim($params['estado'] ?? $params['nuevo_estado'] ?? $params['status'] ?? '');

    // Si la acción es asignación técnica
    if ($action === 'assign_tech' || $id_tecnico > 0) {
        $estado_raw = 'En Proceso';

        if ($id_tecnico <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Debes seleccionar un técnico especialista.']);
            exit;
        }

        if (empty($notas_tecnico)) {
            echo json_encode(['status' => 'error', 'message' => 'Debes ingresar las instrucciones internas para el técnico.']);
            exit;
        }

        if (empty($comentario_cliente)) {
            echo json_encode(['status' => 'error', 'message' => 'Debes ingresar el comentario que se registrará en la bitácora del cliente.']);
            exit;
        }
    }

    // Normalización de estado
    $estado_clean = mb_strtolower($estado_raw);
    $estado = '';

    if (in_array($estado_clean, ['en proceso', 'en_proceso', 'enproceso', 'progreso', 'proceso'])) {
        $estado = 'En Proceso';
    } elseif (in_array($estado_clean, ['resuelto', 'resolved'])) {
        $estado = 'Resuelto';
    } elseif (in_array($estado_clean, ['cerrado', 'closed'])) {
        $estado = 'Cerrado';
    } elseif (in_array($estado_clean, ['reabrir', 'reabierto', 'reopen'])) {
        $estado = 'Reabrir';
    } else {
        $estado = $estado_raw;
    }

    $estados_permitidos = ['En Proceso', 'Resuelto', 'Cerrado', 'Reabrir'];

    if ($id_ticket === 0 || !in_array($estado, $estados_permitidos, true)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Parámetros del ticket corruptos o fuera del estándar.',
            'debug' => [
                'id_ticket_recibido' => $id_ticket,
                'estado_recibido' => $estado_raw
            ]
        ]);
        exit;
    }

    // Cargar credenciales desde wp-config.php
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

    date_default_timezone_set('America/El_Salvador');
    $fechaActual = date('d/m/Y h:i A');

    // -------------------------------------------------------------------------
    // BLOQUE 2: PROCESAMIENTO Y BASE DE DATOS
    // -------------------------------------------------------------------------
    try {
        $stmt_consulta = $pdo->prepare("SELECT id_usuario, titulo, estado, observacion_proceso, observacion_cierre FROM tickets WHERE id_ticket = ?");
        $stmt_consulta->execute([$id_ticket]);
        $ticket_actual = $stmt_consulta->fetch();

        if (!$ticket_actual) {
            echo json_encode(['status' => 'error', 'message' => 'El ticket no existe.']);
            exit;
        }

        $id_cliente = intval($ticket_actual['id_usuario']);
        $titulo_ticket = $ticket_actual['titulo'] ?? 'Sin Título';
        $observacion_proceso_anterior = $ticket_actual['observacion_proceso'] ?? '';
        $observacion_cierre_anterior = $ticket_actual['observacion_cierre'] ?? '';

        $pdo->beginTransaction();

        // CASO A: ASIGNACIÓN DE TÉCNICO
        if ($id_tecnico > 0) {
            // 1. Insertar en asignaciones_tickets
            $stmt_asig = $pdo->prepare("
                INSERT INTO asignaciones_tickets (id_ticket, id_tecnico, id_asignado_por, notas_asignacion, fecha_asignacion)
                VALUES (:id_ticket, :id_tecnico, :id_asignado_por, :notas_asignacion, NOW())
            ");
            $stmt_asig->execute([
                ':id_ticket'        => $id_ticket,
                ':id_tecnico'       => $id_tecnico,
                ':id_asignado_por'  => intval($_SESSION['id_usuario']),
                ':notas_asignacion' => $notas_tecnico
            ]);

            // 2. Formatear bitácora del cliente
            $comentario_nuevo = "[$fechaActual] - $comentario_cliente";
            $observacion_proceso_final = empty($observacion_proceso_anterior)
                ? $comentario_nuevo
                : $observacion_proceso_anterior . "\n" . $comentario_nuevo;

            // 3. Actualizar ticket
            $sql = "UPDATE tickets 
                    SET estado = 'En Proceso', 
                        id_tecnico = :id_tecnico,
                        observacion_proceso = :observacion_proceso, 
                        fecha_actualizacion = NOW() 
                    WHERE id_ticket = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_tecnico'          => $id_tecnico,
                ':observacion_proceso' => $observacion_proceso_final,
                ':id'                  => $id_ticket
            ]);

            $mensaje_db = 'Técnico asignado correctamente. Registro guardado en la tabla de asignaciones y bitácora del cliente actualizada.';
            $tipo_notificacion = 'progreso';
            $observaciones_mail = $comentario_cliente;
        } else {
            // CASO B: CAMBIOS DE ESTADO DIRECTOS (PROCESO / RESUELTO / CERRADO / REABRIR)
            $observaciones_mail = $comentario_cliente;

            if ($estado === 'En Proceso') {
                $tipo_notificacion = 'progreso';
                $comentario_nuevo = !empty($comentario_cliente) ? "[$fechaActual] - $comentario_cliente" : '';
                $observacion_proceso_final = empty($observacion_proceso_anterior)
                    ? $comentario_nuevo
                    : $observacion_proceso_anterior . "\n" . $comentario_nuevo;

                $sql = "UPDATE tickets SET estado = 'En Proceso', observacion_proceso = :observacion_proceso, fecha_actualizacion = NOW() WHERE id_ticket = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':observacion_proceso' => $observacion_proceso_final, ':id' => $id_ticket]);
                $mensaje_db = 'Ticket actualizado a En Proceso.';
            } elseif ($estado === 'Reabrir') {
                $tipo_notificacion = 'reapertura';
                $comentario_reapertura = "[$fechaActual] - REAPERTURA: $comentario_cliente";
                $observacion_proceso_final = empty($observacion_proceso_anterior)
                    ? $comentario_reapertura
                    : $observacion_proceso_anterior . "\n" . $comentario_reapertura;

                $sql = "UPDATE tickets SET estado = 'En Proceso', observacion_proceso = :observacion_proceso, fecha_actualizacion = NOW() WHERE id_ticket = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':observacion_proceso' => $observacion_proceso_final, ':id' => $id_ticket]);
                $mensaje_db = 'Ticket reabierto correctamente.';
            } else { // Resuelto o Cerrado
                $tipo_notificacion = strtolower($estado);
                $comentario_nuevo = "[$fechaActual] - $comentario_cliente";
                $observacion_cierre_final = empty($observacion_cierre_anterior)
                    ? $comentario_nuevo
                    : $observacion_cierre_anterior . "\n" . $comentario_nuevo;

                $sql = "UPDATE tickets SET estado = :estado, observacion_cierre = :observacion_cierre, fecha_actualizacion = NOW() WHERE id_ticket = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':estado' => $estado, ':observacion_cierre' => $observacion_cierre_final, ':id' => $id_ticket]);
                $mensaje_db = "Ticket marcado como {$estado} con éxito.";
            }
        }

        // -------------------------------------------------------------------------
        // BLOQUE 3: COLA DE CORREOS
        // -------------------------------------------------------------------------
        if ($id_cliente > 0) {
            $stmt_usuario = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id_usuario = ?");
            $stmt_usuario->execute([$id_cliente]);
            $datos_usuario = $stmt_usuario->fetch();

            if ($datos_usuario && !empty($datos_usuario['email'])) {
                $correo_destino = $datos_usuario['email'];
                $nombre_cliente = htmlspecialchars($datos_usuario['nombre'] ?? 'Cliente');

                $asunto = "Actualización de Avance - Ticket #TK-{$id_ticket}";
                $status_title = "El incidente se encuentra actualmente EN PROCESO";
                $status_color = "#0284c7";

                if ($tipo_notificacion === 'reapertura') {
                    $asunto = "Reapertura de Incidente - Ticket #TK-{$id_ticket}";
                    $status_title = "El incidente ha sido REABIERTO";
                    $status_color = "#f59e0b";
                } elseif ($tipo_notificacion === 'resuelto') {
                    $asunto = "Incidente RESUELTO - Ticket #TK-{$id_ticket}";
                    $status_title = "El incidente ha sido marcado como RESUELTO";
                    $status_color = "#22c55e";
                } elseif ($tipo_notificacion === 'cerrado') {
                    $asunto = "Incidente CERRADO - Ticket #TK-{$id_ticket}";
                    $status_title = "El caso ha sido CERRADO de forma definitiva";
                    $status_color = "#64748b";
                }

                $comentario_html = !empty($observaciones_mail)
                    ? "<div style='background-color: #f8fafc; border-left: 4px solid {$status_color}; padding: 12px; margin: 15px 0; font-family: monospace; font-size: 13px; color: #334155; white-space: pre-wrap;'>\" " . nl2br(htmlspecialchars($observaciones_mail)) . " \"</div>"
                    : "<p style='color: #64748b; font-style: italic;'>Sin comentarios adicionales.</p>";

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
                        Se ha actualizado el estado de tu ticket en el portal de clientes.
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
