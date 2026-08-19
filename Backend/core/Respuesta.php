<?php
/**
 * ==================================================
 *  CLASE AUXILIAR: RESPUESTA
 * ==================================================
 * Estandariza el formato de salida de TODAS las APIs de SiGeRU.
 * Así, cada endpoint devuelve siempre la misma "forma" de JSON:
 *
 *   { "status": "success" | "error", "message": "...", ...datos }
 *
 * Usarla evita repetir header() + json_encode() + exit() en cada
 * archivo de API y hace que el Frontend siempre sepa qué esperar.
 */

class Respuesta
{
    public static function enviar(array $datos, int $codigoHttp = 200): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($codigoHttp);
        echo json_encode($datos);
        exit();
    }
}
