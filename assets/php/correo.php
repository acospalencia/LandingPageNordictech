<?php

/** Envía inmediatamente un correo HTML usando mail() del hosting. */
function enviarCorreoDirecto(
    string $destinatario,
    string $asunto,
    string $cuerpo,
    string $remitente = 'no-reply@nordictech-corp.com',
    string $nombreRemitente = 'NordicTech',
    ?string $responderA = null,
    array $copiasOcultas = []
): bool {
    $limpiarCabecera = static function (string $valor): string {
        return trim(str_replace(["\r", "\n"], '', $valor));
    };

    $destinatario = $limpiarCabecera($destinatario);
    $remitente = $limpiarCabecera($remitente);
    $nombreRemitente = $limpiarCabecera($nombreRemitente);
    $responderA = $limpiarCabecera($responderA ?? $remitente);

    $copiasOcultas = array_values(array_unique(array_filter(array_map(
        $limpiarCabecera,
        $copiasOcultas
    ))));

    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)
        || !filter_var($remitente, FILTER_VALIDATE_EMAIL)
        || !filter_var($responderA, FILTER_VALIDATE_EMAIL)
        || count(array_filter($copiasOcultas, static function (string $correo): bool {
            return !filter_var($correo, FILTER_VALIDATE_EMAIL);
        })) > 0) {
        return false;
    }

    $cabeceras = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        "From: {$nombreRemitente} <{$remitente}>",
        "Reply-To: {$responderA}",
        "Return-Path: {$remitente}",
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    if ($copiasOcultas !== []) {
        $cabeceras[] = 'Bcc: ' . implode(', ', $copiasOcultas);
    }

    return mail($destinatario, $limpiarCabecera($asunto), $cuerpo, implode("\r\n", $cabeceras));
}
