<?php
/**
 * ==================================================
 *  CONTROLADOR: INCIDENCIAS
 * ==================================================
 * Coordina el ciclo de vida de una incidencia (crear, listar,
 * asignar logística, resolver). "asignar" y "resolver" tocan
 * dos tablas a la vez (incidencias + camiones), por lo que usan
 * una transacción PDO para garantizar que ambas se actualicen
 * juntas o ninguna lo haga.
 */

require_once __DIR__ . '/../models/Incidencia.php';
require_once __DIR__ . '/../models/Camion.php';
require_once __DIR__ . '/../models/Notificacion.php';

class IncidenciaController
{
    private Incidencia $incidenciaModel;
    private Camion $camionModel;
    private Notificacion $notificacionModel;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->incidenciaModel = new Incidencia($pdo);
        $this->camionModel = new Camion($pdo);
        $this->notificacionModel = new Notificacion($pdo);
    }

    /** Vecino reporta una incidencia nueva. */
    public function crear(array $datos): array
    {
        $ubicacion        = $datos['ubicacion'] ?? null;
        $estadoContenedor = $datos['estado_contenedor'] ?? null;
        $tipoBasura       = $datos['tipo_basura'] ?? null;
        $usuarioId        = $datos['usuario_id'] ?? null;

        if (!$ubicacion || !$estadoContenedor || !$tipoBasura) {
            return ["status" => "error", "message" => "Todos los campos son requeridos.", "_code" => 400];
        }

        // Latitud/Longitud son opcionales: llegan del pin que el vecino
        // confirma en el mapa al reportar. Se validan solo si vienen informadas.
        $latitud  = (isset($datos['latitud'])  && $datos['latitud']  !== '' && $datos['latitud']  !== null) ? (float) $datos['latitud']  : null;
        $longitud = (isset($datos['longitud']) && $datos['longitud'] !== '' && $datos['longitud'] !== null) ? (float) $datos['longitud'] : null;

        if (($latitud !== null && $longitud === null) || ($latitud === null && $longitud !== null)) {
            return ["status" => "error", "message" => "Latitud y longitud deben informarse juntas.", "_code" => 400];
        }
        if ($latitud !== null && ($latitud < -90 || $latitud > 90 || $longitud < -180 || $longitud > 180)) {
            return ["status" => "error", "message" => "Las coordenadas seleccionadas no son válidas.", "_code" => 400];
        }

        $this->incidenciaModel->crear($ubicacion, $estadoContenedor, $tipoBasura, $usuarioId, $latitud, $longitud);

        return ["status" => "success", "message" => "Reporte ciudadano registrado con éxito.", "_code" => 201];
    }

    /** Operario y Cuadrilla consultan el listado completo. */
    public function listar(): array
    {
        return ["status" => "success", "data" => $this->incidenciaModel->listarTodas()];
    }

    /** Vecino consulta únicamente las incidencias que él mismo reportó. */
    public function listarPorUsuario(array $datos): array
    {
        if (empty($datos['usuario_id'])) {
            return ["status" => "error", "message" => "Falta el ID del usuario.", "_code" => 400];
        }
        return ["status" => "success", "data" => $this->incidenciaModel->listarPorUsuario((int) $datos['usuario_id'])];
    }

    /** Operario asigna cuadrilla + camión y pone la incidencia "en curso". */
    public function asignar(array $datos): array
    {
        if (empty($datos['id']) || empty($datos['matricula_camion'])) {
            return ["status" => "error", "message" => "Faltan datos para asignar la incidencia.", "_code" => 400];
        }

        $id          = (int) $datos['id'];
        $cuadrillaId = $datos['cuadrilla_id'] ?? null;
        $matricula   = $datos['matricula_camion'];
        $comentario  = $datos['comentario_operario'] ?? '';

        $incidencia = $this->incidenciaModel->buscarPorId($id);
        if (!$incidencia) {
            return ["status" => "error", "message" => "La incidencia no existe.", "_code" => 404];
        }

        try {
            $this->pdo->beginTransaction();
            $this->incidenciaModel->asignar($id, $cuadrillaId, $matricula, $comentario);
            $this->camionModel->actualizarEstado($matricula, 'En Ruta');
            $this->pdo->commit();

            if ($incidencia && !empty($incidencia['usuario_id'])) {
                $this->notificacionModel->crear(
                    $incidencia['usuario_id'],
                    'incidencia',
                    "Tu incidencia #{$id} en {$incidencia['ubicacion']} fue asignada a una cuadrilla y está en curso.",
                    $id
                );
            }

            return ["status" => "success", "message" => "Logística asignada correctamente. Estado: En curso."];
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ["status" => "error", "message" => "Error al asignar logística: " . $e->getMessage(), "_code" => 500];
        }
    }

    /** Cuadrilla marca la incidencia como resuelta y libera el camión asignado. */
    public function resolver(array $datos): array
    {
        if (empty($datos['id'])) {
            return ["status" => "error", "message" => "Falta el ID de la incidencia.", "_code" => 400];
        }

        $id = (int) $datos['id'];
        $incidencia = $this->incidenciaModel->buscarPorId($id);
        if (!$incidencia) {
            return ["status" => "error", "message" => "La incidencia no existe.", "_code" => 404];
        }

        try {
            $this->pdo->beginTransaction();
            $this->incidenciaModel->resolver($id);

            if ($incidencia && !empty($incidencia['matricula_camion'])) {
                $this->camionModel->actualizarEstado($incidencia['matricula_camion'], 'Disponible');
            }

            $this->pdo->commit();

            if ($incidencia && !empty($incidencia['usuario_id'])) {
                $this->notificacionModel->crear(
                    $incidencia['usuario_id'],
                    'incidencia',
                    "Tu incidencia #{$id} en {$incidencia['ubicacion']} fue marcada como solucionada.",
                    $id
                );
            }

            return ["status" => "success", "message" => "Incidencia marcada como solucionada y camión liberado."];
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ["status" => "error", "message" => "Error al resolver incidencia: " . $e->getMessage(), "_code" => 500];
        }
    }
}
