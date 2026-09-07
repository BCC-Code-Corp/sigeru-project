<?php
/**
 * ==================================================
 *  CONTROLADOR: NOTIFICACIONES
 * ==================================================
 * - Los avisos de cambio de estado de una incidencia los genera
 *   automáticamente IncidenciaController (asignar/resolver).
 * - Este controlador expone lo que necesita el Frontend:
 *   listar las notificaciones de un usuario, marcar como leída,
 *   y que el Administrador publique anuncios para todo el público.
 */

require_once __DIR__ . '/../models/Notificacion.php';

class NotificacionController
{
    private Notificacion $notificacionModel;

    public function __construct(PDO $pdo)
    {
        $this->notificacionModel = new Notificacion($pdo);
    }

    /** Notificaciones privadas de un usuario + anuncios públicos. */
    public function listar(array $datos): array
    {
        if (empty($datos['usuario_id'])) {
            return ["status" => "error", "message" => "Falta el ID del usuario.", "_code" => 400];
        }
        return ["status" => "success", "data" => $this->notificacionModel->listarParaUsuario((int) $datos['usuario_id'])];
    }

    /** Historial de anuncios enviados (para el panel del Administrador). */
    public function listarAnuncios(): array
    {
        return ["status" => "success", "data" => $this->notificacionModel->listarAnuncios()];
    }

    /** El Administrador publica un anuncio visible para todo el público. */
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
        $this->notificacionModel->marcarLeida($id);
        return ["status" => "success", "message" => "Notificación marcada como leída."];
    }
}
