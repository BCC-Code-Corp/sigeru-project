<?php

require_once __DIR__ . '/../models/Notificacion.php';

class NotificacionController
{
    private Notificacion $notificacionModel;

    public function __construct(PDO $pdo)
    {
        $this->notificacionModel = new Notificacion($pdo);
    }

    public function listar(array $datos): array
    {
        $datos['usuario_id'] = $GLOBALS['actor']['id'];
        if (empty($datos['usuario_id'])) {
            return ["status" => "error", "message" => "Falta el ID del usuario.", "_code" => 400];
        }
        return ["status" => "success", "data" => $this->notificacionModel->listarParaUsuario((int) $datos['usuario_id'])];
    }

    public function listarAnuncios(): array
    {
        return ["status" => "success", "data" => $this->notificacionModel->listarAnuncios()];
    }

    public function crearAnuncio(array $datos): array
    {
        $mensaje = trim($datos['mensaje'] ?? '');
        if (!$mensaje) {
            return ["status" => "error", "message" => "El mensaje del anuncio no puede estar vacío.", "_code" => 400];
        }

        $this->notificacionModel->crear(null, 'anuncio', $mensaje);
        return ["status" => "success", "message" => "Anuncio publicado correctamente.", "_code" => 201];
    }

    public function marcarLeida(array $datos): array
    {
        if (empty($datos['id'])) {
            return ["status" => "error", "message" => "Falta el ID de la notificación.", "_code" => 400];
        }
        $id = (int) $datos['id'];
        if (!$this->notificacionModel->buscarPorId($id)) {
            return ["status" => "error", "message" => "La notificación no existe.", "_code" => 404];
        }
        $n=$this->notificacionModel->buscarPorId($id);
        if ($n['usuario_id'] !== null && (int)$n['usuario_id'] !== (int)$GLOBALS['actor']['id']) return ['status'=>'error','message'=>'Notificación ajena.','_code'=>403];
        $this->notificacionModel->marcarLeida($id);
        return ["status" => "success", "message" => "Notificación marcada como leída."];
    }
}
