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

    /**
     * Envía el resultado de un Controlador respetando el código HTTP
     * de estado que el propio Controlador haya definido en la clave
     * interna "_code" (por ejemplo 201 al crear, 404 si el recurso no
     * existe, 409 si hay un conflicto de datos únicos).
     *
     * Si el Controlador no definió un código explícito, se usa
     * $exitoDefault en caso de éxito (200 por defecto, o 201 cuando el
     * propio endpoint lo indica para un alta) o 400 en caso de error
     * de validación genérico. Así los cuatro CRUD (usuarios, camiones,
     * contenedores, centros de acopio) devuelven siempre códigos de
     * estado HTTP correctos y consistentes: 200, 201, 400, 404, 405, 409.
     */
    public static function enviarResultado(array $resultado, int $exitoDefault = 200): void
    {
        $codigo = $resultado['_code'] ?? (($resultado['status'] === 'success') ? $exitoDefault : 400);
        unset($resultado['_code']);
        self::enviar($resultado, $codigo);
    }

    /**
     * Envía un error 500 estandarizado a partir de una excepción atrapada.
     * Con SIGERU_DEBUG=true muestra el mensaje real (para poder diagnosticar
     * en desarrollo); con SIGERU_DEBUG=false (recomendado para la entrega
     * final) devuelve un mensaje genérico y nunca expone detalles internos.
     */
    public static function enviarErrorInterno(\Throwable $e, string $contexto = ''): void
    {
        error_log(($contexto ? "$contexto: " : '') . $e->getMessage());

        $mensaje = (defined('SIGERU_DEBUG') && SIGERU_DEBUG)
            ? 'Error interno del servidor: ' . $e->getMessage()
            : 'Error interno del servidor.';

        self::enviar(["status" => "error", "message" => $mensaje], 500);
    }
}
