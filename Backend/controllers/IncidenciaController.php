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

class IncidenciaController
{
    private Incidencia $incidenciaModel;
    private Camion $camionModel;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->incidenciaModel = new Incidencia($pdo);
        $this->camionModel = new Camion($pdo);
    }

    /** Vecino reporta una incidencia nueva. */
    public function crear(array $datos): array
    {
        $ubicacion        = $datos['ubicacion'] ?? null;
        $estadoContenedor = $datos['estado_contenedor'] ?? null;
        $tipoBasura       = $datos['tipo_basura'] ?? null;

        if (!$ubicacion || !$estadoContenedor || !$tipoBasura) {
            return ["status" => "error", "message" => "Todos los campos son requeridos."];
        }

        $this->incidenciaModel->crear($ubicacion, $estadoContenedor, $tipoBasura);

        return ["status" => "success", "message" => "Reporte ciudadano registrado con éxito."];
    }

    /** Operario y Cuadrilla consultan el listado completo. */
    public function listar(): array
    {
        return ["status" => "success", "data" => $this->incidenciaModel->listarTodas()];
    }

    /** Operario asigna cuadrilla + camión y pone la incidencia "en curso". */
    public function asignar(array $datos): array
    {
        if (empty($datos['id']) || empty($datos['matricula_camion'])) {
            return ["status" => "error", "message" => "Faltan datos para asignar la incidencia."];
        }

        $id          = (int) $datos['id'];
        $cuadrillaId = $datos['cuadrilla_id'] ?? null;
        $matricula   = $datos['matricula_camion'];
        $comentario  = $datos['comentario_operario'] ?? '';

        try {
            $this->pdo->beginTransaction();
            $this->incidenciaModel->asignar($id, $cuadrillaId, $matricula, $comentario);
            $this->camionModel->actualizarEstado($matricula, 'En Ruta');
            $this->pdo->commit();

            return ["status" => "success", "message" => "Logística asignada correctamente. Estado: En curso."];
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ["status" => "error", "message" => "Error al asignar logística: " . $e->getMessage()];
        }
    }

    /** Cuadrilla marca la incidencia como resuelta y libera el camión asignado. */
    public function resolver(array $datos): array
    {
        if (empty($datos['id'])) {
            return ["status" => "error", "message" => "Falta el ID de la incidencia."];
        }

        $id = (int) $datos['id'];
        $incidencia = $this->incidenciaModel->buscarPorId($id);

        try {
            $this->pdo->beginTransaction();
            $this->incidenciaModel->resolver($id);

            if ($incidencia && !empty($incidencia['matricula_camion'])) {
                $this->camionModel->actualizarEstado($incidencia['matricula_camion'], 'Disponible');
            }

            $this->pdo->commit();

            return ["status" => "success", "message" => "Incidencia marcada como solucionada y camión liberado."];
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ["status" => "error", "message" => "Error al resolver incidencia: " . $e->getMessage()];
        }
    }
}
