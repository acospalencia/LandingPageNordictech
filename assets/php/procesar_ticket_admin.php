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

    // Validación contra los estados lógicos de tu flujo técnico
    if ($id_ticket === 0 || !in_array($estado, ['En Proceso', 'Resuelto', 'Cerrado', 'Reabrir'])) {
        echo json_encode(['status' => 'error', 'message' => 'Parámetros del ticket corruptos o fuera del estándar de red.']);
        exit;
    }

    // Validación obligatoria de explicaciones para Resueltos, Cerrados y Reaperturas
    if (in_array($estado, ['Resuelto', 'Cerrado', 'Reabrir']) && empty($observaciones)) {
        echo json_encode(['status' => 'error', 'message' => 'Es obligatorio registrar la bitácora de la operación actual.']);
        exit;
    }

    // Cargar parámetros de wp-config.php de forma dinámica para obtener las credenciales de la BD
    $wp_config_path = __DIR__ . '/../../wp-config.php';
    if (file_exists($wp_config_path)) {
        $config_content = file_get_contents($wp_config_path);
        preg_match("/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_name);
        preg_match("/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_user);
        preg_match("/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_password);
        preg_match("/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $config_content, $db_host);

        $database = isset($db_name[1]) ? trim($db_name[1]) : '';
        $username = isset($db_user[1]) ? trim($db_user[1]) : '';
        $password_db = isset($db_password[1]) ? trim($db_password[1]) : '';
        $host     = isset($db_host[1]) ? trim($db_host[1]) : 'localhost';
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: Archivo de configuración de base de datos inalcanzable.']);
        exit;
    }

    // -------------------------------------------------------------------------
    // BLOQUE 1: CONEXIÓN A LA BASE DE DATOS
    // -------------------------------------------------------------------------
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password_db, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error', 
            'stage' => 'db_connection',
            'message' => 'Fallo al conectar con la base de datos.',
            'debug' => $e->getMessage()
        ]);
        exit;
    }

    // Ajustar zona horaria local de El Salvador
    date_default_timezone_set('America/El_Salvador');
    $fechaActual = date('d/m/Y h:i A');

    // -------------------------------------------------------------------------
    // BLOQUE 2: ACTUALIZACIÓN DEL TICKET EN LA BD
    // -------------------------------------------------------------------------
    try {
        // Consultar observaciones anteriores e información del ticket / usuario
        $stmt_consulta = $pdo->prepare("SELECT id_usuario, titulo, estado, observacion_proceso, observacion_cierre FROM tickets WHERE id_ticket = ?");
        $stmt_consulta->execute([$id_ticket]);
        $ticket_actual = $stmt_consulta->fetch();

        if (!$ticket_actual) {
            echo json_encode(['status' => 'error', 'message' => 'El ticket especificado no existe en el sistema.']);
            exit;
        }

        $id_cliente = intval($ticket_actual['id_usuario']);
        $titulo_ticket = $ticket_actual['titulo'] ?? 'Sin Título';
        $observacion_proceso_anterior = $ticket_actual['observacion_proceso'] ?? '';
        $observacion_cierre_anterior = $ticket_actual['observacion_cierre'] ?? '';
        
        $nuevo_estado = $estado;
        $mensaje_db = '';
        $tipo_notificacion = ''; 

        // Procesar y formatear según el estado objetivo de la operación
        if ($estado === 'En Proceso') {
            $tipo_notificacion = 'progreso';
            if (!empty($observaciones)) {
                $comentario_nuevo = "[$fechaActual] - $observaciones";
                $observacion_proceso_final = empty($observacion_proceso_anterior) 
                    ? $comentario_nuevo 
                    : $observacion_proceso_anterior . "\n" . $comentario_nuevo;
            } else {
                $observacion_proceso_final = !empty($observacion_proceso_anterior) ? $observacion_proceso_anterior : null;
            }

            $sql = "UPDATE tickets 
                    SET estado = :estado, 
                        observacion_proceso = :observacion_proceso, 
                        fecha_actualizacion = NOW() 
                    WHERE id_ticket = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':estado'              => $nuevo_estado,
                ':observacion_proceso' => $observacion_proceso_final,
                ':id'                  => $id_ticket
            ]);
            $mensaje_db = 'Ticket asignado o actualizado bajo estado En Proceso con éxito.';

        } elseif ($estado === 'Reabrir') {
            $tipo_notificacion = 'reapertura';
            $nuevo_estado = 'En Proceso';
            $comentario_reapertura = "[$fechaActual] - REAPERTURA: $observaciones";
            
            $observacion_proceso_final = empty($observacion_proceso_anterior) 
                ? $comentario_reapertura 
                : $observacion_proceso_anterior . "\n" . $comentario_reapertura;

            $sql = "UPDATE tickets 
                    SET estado = :estado, 
                        observacion_proceso = :observacion_proceso, 
                        fecha_actualizacion = NOW() 
                    WHERE id_ticket = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':estado'              => $nuevo_estado,
                ':observacion_proceso' => $observacion_proceso_final,
                ':id'                  => $id_ticket
            ]);
            $mensaje_db = 'Ticket reabierto correctamente. Se ha retornado al estado En Proceso.';

        } else { // 'Resuelto' o 'Cerrado'
            $tipo_notificacion = strtolower($estado); 
            $comentario_nuevo = "[$fechaActual] - $observaciones";
            $observacion_cierre_final = empty($observacion_cierre_anterior) 
                ? $comentario_nuevo 
                : $observacion_cierre_anterior . "\n" . $comentario_nuevo;

            $sql = "UPDATE tickets 
                    SET estado = :estado, 
                        observacion_cierre = :observacion_cierre, 
                        fecha_actualizacion = NOW() 
                    WHERE id_ticket = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':estado'             => $nuevo_estado,
                ':observacion_cierre' => $observacion_cierre_final,
                ':id'                 => $id_ticket
            ]);
            $mensaje_db = 'Operación procesada con éxito. Incidente guardado en el histórico.';
        }

    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error', 
            'stage' => 'ticket_update',
            'message' => 'Error al ejecutar la actualización del ticket en la base de datos.',
            'debug' => $e->getMessage()
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // BLOQUE 3: ENCOLAR NOTIFICACIÓN DE CORREO ELECTRÓNICO (NUEVO)
    // -------------------------------------------------------------------------
    $mail_status = 'skipped';
    $mail_debug = 'No client email found or query skipped';

    try {
        if ($id_cliente > 0) {
            // CORRECCIÓN AQUÍ: Cambiado wp_users por tu tabla real 'usuarios'
            $stmt_usuario = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id_usuario = ?");
            $stmt_usuario->execute([$id_cliente]);
            $datos_usuario = $stmt_usuario->fetch();

            if ($datos_usuario && !empty($datos_usuario['email'])) {
                $correo_destino = $datos_usuario['email'];
                $nombre_cliente = $datos_usuario['nombre'] ?? 'Cliente';
                $correo_sistema = 'no-reply@nordictech-corp.com'; 

                $asunto = "";
                $status_title = "";
                $status_color = "#2A4094"; 

                switch ($tipo_notificacion) {
                    case 'progreso':
                        $asunto = "Actualizacion de Avance - Ticket #TK-{$id_ticket}";
                        $status_title = "El incidente se encuentra actualmente EN PROCESO";
                        $status_color = "#0284c7"; 
                        break;
                    case 'reapertura':
                        $asunto = "Reapertura de Incidente - Ticket #TK-{$id_ticket}";
                        $status_title = "El incidente ha sido REABIERTO";
                        $status_color = "#f59e0b"; 
                        break;
                    case 'resuelto':
                        $asunto = "Incidente RESUELTO - Ticket #TK-{$id_ticket}";
                        $status_title = "El incidente ha sido marcado como RESUELTO";
                        $status_color = "#22c55e"; 
                        break;
                    case 'cerrado':
                        $asunto = "Incidente CERRADO - Ticket #TK-{$id_ticket}";
                        $status_title = "El caso ha sido CERRADO de forma definitiva";
                        $status_color = "#64748b"; 
                        break;
                }

                $comentario_html = !empty($observaciones) 
                    ? "<div style='background-color: #f8fafc; border-left: 4px solid {$status_color}; padding: 12px; margin: 15px 0; font-family: monospace; font-size: 13px; color: #334155; white-space: pre-wrap;'>\" " . nl2br(htmlspecialchars($observaciones)) . " \"</div>"
                    : "<p style='color: #64748b; font-style: italic;'>No se registraron comentarios de texto adicionales para esta actualización.</p>";

                $cuerpo_correo = "
                <!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Notificación de Ticket | NordicTech</title>
</head>
<body style='margin: 0; padding: 0; background-color: #101729; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: #ffffff; -webkit-font-smoothing: antialiased;'>

    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #060913; padding: 40px 20px;'>
        <tr>
            <td align='center'>

                <!-- Tarjeta Principal Centrada -->
                <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #0D1425; border: 1px solid #1E293B; border-top: 4px solid #2A4094; padding: 40px 30px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);'>

                    <!-- Encabezado con Logo Corporativo -->
                    <tr>
                        <td align='center' style='padding-bottom: 30px; border-bottom: 1px solid #1E293B;'>
                            <div style='font-family: \"Space Grotesk\", \"Segoe UI\", sans-serif; font-weight: bold; font-size: 24px; letter-spacing: -0.02em; color: #ffffff; text-transform: uppercase; line-height: 24px;'>
                                <img src='https://nordictech-corp.com/assets/img/Marca%20de%20agua%20black.png' alt='Logo' style='display: inline-block; height: 84px; width: auto; vertical-align: middle; margin-left: 6px; border: 0;'>
                            </div>
                        </td>
                    </tr>

                    <!-- Título de la Notificación -->
                    <tr>
                        <td style='padding-top: 35px; padding-bottom: 15px;'>
                            <span style='font-size: 10px; font-weight: bold; letter-spacing: 2px; color: #3C56C4; text-transform: uppercase; display: block; margin-bottom: 6px;'>
                                Portal de Clientes
                            </span>
                            <h1 style='color: #ffffff; margin: 0; font-size: 20px; text-transform: uppercase;'>NordicTech El Salvador</h1>
                        </td>
                    </tr>

                    <!-- Contenido del Mensaje (Tu texto original) -->
                    <tr>
                        <td style='font-size: 14px; color: #ffffff; padding-bottom: 20px; line-height: 1.6;'>
                            Estimado/a <strong>" . $nombre_cliente . "</strong>,<br><br>
                            Te notificamos que el estado de tu ticket ha sido actualizado en nuestro portal de clientes.
                        </td>
                    </tr>

                    <!-- Bloque de Estado -->
                    <tr>
                        <td style='padding-bottom: 25px;'>
                            <div style='margin: 0; padding: 12px; background-color: #060913; border: 1px solid #1E293B; text-align: center; font-weight: bold; border-radius: 4px; color: " . $status_color . "; text-transform: uppercase; font-size: 13px;'>
                                " . $status_title . "
                            </div>
                        </td>
                    </tr>

                    <!-- Tabla de Datos Técnicos -->
                    <tr>
                        <td style='padding-bottom: 25px;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='font-size: 13px; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 6px 0; color: #64748b; width: 30%; border-bottom: 1px solid #1E293B;'><strong>Referencia:</strong></td>
                                    <td style='padding: 6px 0; font-family: monospace; color: #ffffff; border-bottom: 1px solid #1E293B;'>#TK-" . $id_ticket . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0 6px 0; color: #64748b;'><strong>Incidente:</strong></td>
                                    <td style='padding: 8px 0 6px 0; font-weight: bold; color: #ffffff;'> " . strtoupper(htmlspecialchars($titulo_ticket)) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 6px 0 0 0; color: #64748b;'><strong>Fecha Cambios:</strong></td>
                                    <td style='padding: 6px 0 0 0; color: #ffffff;'>" . $fechaActual . "</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Comentarios Técnicos -->
                    <tr>
                        <td style='padding-bottom: 30px;'>
                            <p style='font-size: 13px; font-weight: bold; margin: 0 0 10px 0; color: #ffffff;'>Detalles y notas de la operación técnica:</p>
                            <div style='background-color: #060913; padding: 20px; border-left: 3px solid #2A4094; border-top: 1px solid #1E293B; border-right: 1px solid #1E293B; border-bottom: 1px solid #1E293B; color: #cbd5e1; font-size: 13px; line-height: 1.6;'>
                                " . $comentario_html . "
                            </div>
                        </td>
                    </tr>

                    <!-- Footer del Correo -->
                    <tr>
                        <td align='center' style='padding-top: 40px; border-top: 1px solid #1E293B; font-size: 11px; color: #64748b; line-height: 1.6;'>
                            Este es un correo automático. Por favor, no respondas directamente a este mensaje.<br><br>
                            &copy; " . date('Y') . " NordicTech El Salvador . Todos los derechos reservados.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>";

                // Insertamos en la tabla de la cola. Usamos fk_id_ticket para relacionarla correctamente.
                $sql_cola = "INSERT INTO cola_correos (id_ticket, destinatario, asunto, cuerpo, estado) VALUES (?, ?, ?, ?, 'pendiente')";
                $stmt_cola = $pdo->prepare($sql_cola);
                $stmt_cola->execute([$id_ticket, $correo_destino, $asunto, $cuerpo_correo]);
                
                $mail_status = 'success_queued';
                $mail_debug = 'Correo encolado exitosamente en base de datos.';
            } else {
                $mail_debug = 'El usuario no tiene correo registrado en la tabla usuarios.';
            }
        }
    } catch (Exception $e) {
        $mail_status = 'error';
        $mail_debug = 'Fallo al insertar en cola de correos: ' . $e->getMessage();
    }

    // -------------------------------------------------------------------------
    // RESPUESTA FINAL INTEGRADA CON DIAGNÓSTICO
    // -------------------------------------------------------------------------
    echo json_encode([
        'status' => 'success',
        'message' => $mensaje_db,
        'mail_diagnostic' => [
            'status' => $mail_status,
            'info' => $mail_debug
        ]
    ]);
    exit;

} else {
    echo json_encode(['status' => 'error', 'message' => 'Petición incorrecta.']);
    exit;
}