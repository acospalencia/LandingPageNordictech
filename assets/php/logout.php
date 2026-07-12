<?php
session_start();

// 1. Limpiar todas las variables de sesión en memoria
$_SESSION = array();

// 2. Destruir la cookie de sesión en el navegador del cliente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Responder con éxito para que el frontend redirija
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Sesión finalizada exitosamente.']);
exit;