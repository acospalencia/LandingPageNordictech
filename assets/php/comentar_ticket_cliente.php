<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/correo.php';

function responderComentario(string $status, string $message): void
{
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_rol'] ?? 0) !== 1) {
    responderComentario('session_expired', 'Debes iniciar sesión como cliente.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderComentario('error', 'Método no permitido.');
}

$idUsuario = intval($_SESSION['id_usuario']);
$idTicket = intval($_POST['id_ticket'] ?? 0);
$comentario = trim((string) ($_POST['comentario'] ?? ''));
$comentario = preg_replace('/\s+$/u', '', $comentario) ?? $comentario;

if ($idTicket <= 0 || $comentario === '') {
    responderComentario('error', 'Escribe un comentario válido.');
}
if (mb_strlen($comentario) > 2000) {
    responderComentario('error', 'El comentario no puede superar los 2000 caracteres.');
}

$wpConfigPath = __DIR__ . '/../../wp-config.php';
if (!file_exists($wpConfigPath)) {
    responderComentario('error', 'La configuración del servidor no está disponible.');
}

$configContent = file_get_contents($wpConfigPath);
preg_match("/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $configContent, $dbName);
preg_match("/define\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $configContent, $dbUser);
preg_match("/define\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $configContent, $dbPassword);
preg_match("/define\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*?)['\"]\s*\);/", $configContent, $dbHost);

try {
    $pdo = new PDO(
        'mysql:host=' . ($dbHost[1] ?? 'localhost') . ';dbname=' . ($dbName[1] ?? '') . ';charset=utf8mb4',
        $dbUser[1] ?? '',
        $dbPassword[1] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $stmtRolActual = $pdo->prepare(
        'SELECT id_rol, activo FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1'
    );
    $stmtRolActual->execute(['id_usuario' => $idUsuario]);
    $rolActual = $stmtRolActual->fetch();
    if (!$rolActual || intval($rolActual['id_rol']) !== 1 || intval($rolActual['activo']) !== 1) {
        responderComentario('error', 'Tu perfil actual es de solo lectura.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT t.id_ticket, t.titulo, t.estado, t.observacion_proceso,
                c.nombre AS cliente_nombre, c.email AS cliente_email,
                tec.email AS tecnico_email
         FROM tickets t
         INNER JOIN usuarios c ON c.id_usuario = t.id_usuario
         LEFT JOIN usuarios tec ON tec.id_usuario = t.id_tecnico
         WHERE t.id_ticket = :id_ticket AND t.id_usuario = :id_usuario
           AND t.activo = 1
         LIMIT 1 FOR UPDATE"
    );
    $stmt->execute(['id_ticket' => $idTicket, 'id_usuario' => $idUsuario]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        $pdo->rollBack();
        responderComentario('error', 'El ticket no existe o no pertenece a tu cuenta.');
    }
    if (!in_array($ticket['estado'], ['Abierto', 'En Proceso'], true)) {
        $pdo->rollBack();
        responderComentario('error', 'Solo puedes comentar tickets abiertos o en proceso.');
    }

    date_default_timezone_set('America/El_Salvador');
    $fecha = date('d/m/Y h:i A');
    $nombreCliente = trim((string) ($ticket['cliente_nombre'] ?? 'Cliente'));
    $entrada = "[{$fecha}] - Cliente: {$nombreCliente} - {$comentario}";
    $bitacora = trim((string) ($ticket['observacion_proceso'] ?? ''));
    $bitacora = $bitacora === '' ? $entrada : $bitacora . "\n" . $entrada;

    $update = $pdo->prepare(
        'UPDATE tickets SET observacion_proceso = :bitacora, fecha_actualizacion = NOW() WHERE id_ticket = :id_ticket'
    );
    $update->execute(['bitacora' => $bitacora, 'id_ticket' => $idTicket]);

    $tituloSeguro = htmlspecialchars((string) $ticket['titulo'], ENT_QUOTES, 'UTF-8');
    $nombreSeguro = htmlspecialchars($nombreCliente, ENT_QUOTES, 'UTF-8');
    $comentarioSeguro = nl2br(htmlspecialchars($comentario, ENT_QUOTES, 'UTF-8'));
    $asunto = "Nuevo comentario del cliente - Ticket #TK-{$idTicket}";
    $cuerpo = "<!doctype html><html lang='es'><body style='margin:0;background:#060913;color:#fff;font-family:Arial,sans-serif'>
        <div style='max-width:600px;margin:30px auto;padding:30px;background:#0D1425;border:1px solid #1E293B;border-top:4px solid #2A4094'>
            <h2 style='margin-top:0'>Nuevo comentario del cliente</h2>
            <p><strong>Ticket:</strong> #TK-{$idTicket}</p>
            <p><strong>Incidente:</strong> {$tituloSeguro}</p>
            <p><strong>Cliente:</strong> {$nombreSeguro}</p>
            <p><strong>Fecha:</strong> {$fecha}</p>
            <div style='margin-top:20px;padding:16px;background:#060913;border-left:4px solid #3C56C4;color:#cbd5e1'>{$comentarioSeguro}</div>
        </div></body></html>";

    $copias = [];
    if (filter_var($ticket['tecnico_email'] ?? null, FILTER_VALIDATE_EMAIL)) {
        $copias[] = (string) $ticket['tecnico_email'];
    }
    $replyTo = filter_var($ticket['cliente_email'] ?? null, FILTER_VALIDATE_EMAIL)
        ? (string) $ticket['cliente_email']
        : null;

    if (!enviarCorreoDirecto(
        'tickets@nordictech-corp.com',
        $asunto,
        $cuerpo,
        'soporte@nordictech-corp.com',
        'Portal de Clientes NordicTech',
        $replyTo,
        $copias
    )) {
        throw new RuntimeException('La función mail() no aceptó la notificación del comentario.');
    }

    $pdo->commit();
    responderComentario('success', 'Comentario agregado y equipo de soporte notificado.');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Landing NordicTech][comentario_cliente] ' . $e->getMessage());
    responderComentario('error', 'No fue posible guardar y notificar el comentario. Intenta nuevamente.');
}
