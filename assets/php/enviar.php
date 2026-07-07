<?php
// Configurar cabeceras para responder en formato JSON a la Fetch API
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitizar y recibir los datos del formulario
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
    $email  = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $mensaje = filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_SPECIAL_CHARS);

    // Validar campos requeridos
    if (!$nombre || !$email || !$mensaje) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son requeridos o el formato de email es inválido.']);
        exit;
    }

    // CONFIGURACIÓN DEL CORREO CORPORATIVO
    $destinatario = "info@nordictech-corp.com"; // El correo donde recibirás las solicitudes
    $asunto = "Nueva Solicitud Técnica / Comercial - NordicTech Website";

    // Construcción del cuerpo del mensaje (HTML Estructurado)
    $cuerpoMensaje = "
    <!DOCTYPE html>
<html lang='es'>

<head>
    <meta charset='UTF-8'>
    <title>Notificación de Contacto | NordicTech</title>
</head>

<body
    style='margin: 0; padding: 0; background-color: #101729; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: #ffffff;-webkit-font-smoothing: antialiased;'>

    <!-- Contenedor Principal Centrado -->
    <table border='0' cellpadding='0' cellspacing='0' width='100%'
        style='background-color: #060913; padding: 40px 20px;'>
        <tr>
            <td align='center'>

                <!-- Tarjeta del Correo -->
                <table border='0' cellpadding='0' cellspacing='0' width='100%'
                    style='max-width: 600px; background-color: #0D1425; border: 1px solid #1E293B; border-top: 4px solid #2A4094; padding: 40px 30px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);'>

                    <!-- Encabezado / Identidad Colectiva -->
                    <tr>

                        <td align='center' style='padding-bottom: 30px; border-bottom: 1px solid #1E293B;'>
                            <div
                                style='font-family: "Space Grotesk", "Segoe UI", sans-serif; font-weight: bold; font-size: 24px; letter-spacing: -0.02em; color: #ffffff; text-transform: uppercase; line-height: 24px;'>

                                <img src="/assets/img/Marca de agua black.png" alt="Logo"
                                    style='display: inline-block; height: 84px; width: auto; vertical-align: middle; margin-left: 6px; border: 0;'>
                            </div>
                            <div
                                style='font-size: 9px; color: #94A3B8; text-transform: uppercase; letter-spacing: 3px; margin-top: 6px;'>

                            </div>
                        </td>
                        
                    </tr>

                    <!-- Título de la Solicitud -->
                    <tr>
                        <td style='padding-top: 35px; padding-bottom: 15px;'>
                            <span
                                style='font-size: 10px; font-weight: bold; letter-spacing: 2px; color: #3C56C4; text-transform: uppercase; display: block; margin-bottom: 6px;'>
                                Atención de Sistemas
                            </span>
                            <h2
                                style='margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: -0.01em;'>
                                NUEVA SOLICITUD DE REQUERIMIENTO
                            </h2>
                        </td>
                    </tr>

                    <!-- Tabla de Datos del Remitente -->
                    <tr>
                        <td style='padding-bottom: 25px;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%'
                                style='background-color: #060913; border: 1px solid #1E293B; padding: 20px; margin-top: 10px;'>
                                <tr>
                                    <td style='padding-bottom: 10px; font-size: 12px; color: #94A3B8;'>
                                        <strong
                                            style='color: #ffffff; text-transform: uppercase; font-size: 10px; letter-spacing: 1px; display: block; margin-bottom: 2px;'>Razón
                                            Social / Nombre:</strong>
                                        {$nombre}
                                    </td>
                                </tr>
                                <tr>
                                    <td style='font-size: 12px; color: #94A3B8;'>
                                        <strong
                                            style='color: #ffffff; text-transform: uppercase; font-size: 10px; letter-spacing: 1px; display: block; margin-bottom: 2px;'>Email
                                            de Contacto:</strong>
                                        <a href='mailto:{$email}'
                                            style='color: #3C56C4; text-decoration: none;'>{$email}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Especificaciones / Mensaje -->
                    <tr>
                        <td>
                            <h3
                                style='margin: 0 0 10px 0; font-size: 11px; font-weight: bold; letter-spacing: 1.5px; color: #ffffff; text-transform: uppercase;'>
                                Especificaciones del Proyecto:
                            </h3>
                            <div
                                style='background-color: #060913; padding: 20px; border-left: 3px solid #2A4094; border-top: 1px solid #1E293B; border-right: 1px solid #1E293B; border-bottom: 1px solid #1E293B; color: #E2E8F0; font-size: 13px; line-height: 1.6; white-space: pre-wrap;'>
                                " . nl2br($mensaje) . "
                            </div>
                        </td>
                    </tr>

                    <!-- Pie de la Tarjeta (Footer Interno) -->
                    <tr>
                        <td align='center'
                            style='padding-top: 40px; border-top: 1px solid #1E293B; margin-top: 30px; font-size: 11px; color: #64748B; font-style: italic;'>
                            Este es un mensaje automatizado generado por el Nodo Central de NordicTech El Salvador.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
               
    ";

    // Cabeceras HTTP obligatorias para el envío seguro de correos HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    // El "From" debe ser un correo de tu propio dominio en Bluehost para evitar caer en Spam
    $headers .= "From: c.acosta@nordictech-corp.com" . "\r\n"; 
    $headers .= "Reply-To: {$email}" . "\r\n";

    // Ejecutar el envío usando la función nativa del servidor de Bluehost
    if (mail($destinatario, $asunto, $cuerpoMensaje, $headers)) {
        echo json_encode(['status' => 'success', 'message' => 'Conexión enviada con éxito al nodo de NordicTech El Salvador.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'El servidor no pudo procesar el correo. Verifica los servicios de mail corporativo.']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de transferencia no permitido.']);
}