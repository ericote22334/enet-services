<?php
/*
 * bot.php
 * Avisa al chatbot de WhatsApp cuando pasa algo en el sistema.
 *
 * Se incluye con include("bot.php") y se usa así:
 *     avisarBot($id_proyecto, "delegado");
 *
 * Eventos válidos: delegado | devuelto | finalizado | reabierto
 *
 * Si el bot está apagado la función no rompe nada: falla en silencio
 * y el sistema sigue funcionando igual que antes.
 */

define("BOT_URL", "http://127.0.0.1:3001");
define("BOT_TOKEN", "Chupala_461"); // debe coincidir con API_TOKEN del .env

function avisarBot($idProyecto, $evento)
{
    $datos = json_encode([
        "id_proyecto" => intval($idProyecto),
        "evento"      => $evento
    ]);

    $ch = curl_init(BOT_URL . "/evento");

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-token: " . BOT_TOKEN
    ]);

    $respuesta = curl_exec($ch);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        error_log("avisarBot: " . $error);
        return false;
    }

    return $respuesta;
}

/*
 * Aviso libre a un teléfono puntual, por si lo necesitás en otra pantalla.
 *     avisarTelefono("2257602642", "Recordatorio: ...");
 */
function avisarTelefono($telefono, $mensaje)
{
    $datos = json_encode([
        "telefono" => $telefono,
        "mensaje"  => $mensaje
    ]);

    $ch = curl_init(BOT_URL . "/notificar");

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-token: " . BOT_TOKEN
    ]);

    $respuesta = curl_exec($ch);
    curl_close($ch);

    return $respuesta;
}
?>
