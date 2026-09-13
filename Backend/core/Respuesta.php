<?php

class Respuesta
{
    public static function enviar(array $datos, int $codigoHttp = 200): void
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!empty($GLOBALS['api_transaccion']) && $pdo && $pdo->inTransaction()) {
            if ($codigoHttp < 400 && ($datos['status'] ?? '') === 'success') {
                $detalle = $GLOBALS['api_datos'] ?? [];
                unset($detalle['password'], $detalle['cedula'], $detalle['email']);
                $stmt = $pdo->prepare('INSERT INTO auditoria(usuario_id,recurso,accion,referencia,detalle) VALUES (?,?,?,?,?)');
                $stmt->execute([$GLOBALS['actor']['id'] ?? null, $GLOBALS['api_recurso'], $_SERVER['REQUEST_METHOD'], $_GET['id'] ?? $_GET['matricula'] ?? null, json_encode($detalle,JSON_UNESCAPED_UNICODE)]);
                $pdo->commit();
            } else $pdo->rollBack();
        }
        header('Cache-Control: no-store');
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($codigoHttp);
        echo json_encode($datos);
        exit();
    }

   
    public static function enviarResultado(array $resultado, int $exitoDefault = 200): void
    {
        $codigo = $resultado['_code'] ?? (($resultado['status'] === 'success') ? $exitoDefault : 400);
        unset($resultado['_code']);
        self::enviar($resultado, $codigo);
    }


    public static function enviarErrorInterno(\Throwable $e, string $contexto = ''): void
    {
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo']->inTransaction()) $GLOBALS['pdo']->rollBack();
        if ($e instanceof DomainException) self::enviar(['status'=>'error','message'=>$e->getMessage()], $e->getCode() ?: 400);
        if ($e instanceof PDOException && $e->getCode() === '23000') self::enviar(['status'=>'error','message'=>'El registro está relacionado con otros datos o ya existe.'],409);
        error_log(($contexto ? "$contexto: " : '') . $e->getMessage());

        $mensaje = (defined('SIGERU_DEBUG') && SIGERU_DEBUG)
            ? 'Error interno del servidor: ' . $e->getMessage()
            : 'Error interno del servidor.';

        self::enviar(["status" => "error", "message" => $mensaje], 500);
    }
}
