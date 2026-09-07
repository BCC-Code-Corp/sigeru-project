<?php
/**
 * ==================================================
 *  CONTROLADOR: CAMIONES (API Recolección)
 * ==================================================
 */

require_once __DIR__ . '/../models/Camion.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Incidencia.php';

class CamionController
{
    private Camion $camionModel;
    private Usuario $usuarioModel;
    private Incidencia $incidenciaModel;
    private array $estadosPermitidos = ['Disponible', 'En Ruta', 'Mantenimiento'];

    public function __construct(PDO $pdo)
    {
        $this->camionModel = new Camion($pdo);
        $this->usuarioModel = new Usuario($pdo);
        $this->incidenciaModel = new Incidencia($pdo);
    }

    public function listar(): array
    {
        return ["status" => "success", "data" => $this->camionModel->listarTodos()];
    }

    public function obtener(string $matricula): array
    {
        $camion = $this->camionModel->buscarPorMatricula($matricula);

        if ($camion) {
            return ["status" => "success", "data" => $camion];
        }

        return ["status" => "error", "message" => "Camión no encontrado.", "_code" => 404];
    }

    public function crear(array $datos): array
    {
        if (empty($datos['matricula'])) {
            return ["status" => "error", "message" => "Matrícula obligatoria.", "_code" => 400];
        }

        $matricula = strtoupper(trim($datos['matricula']));

        if (strlen($matricula) < 5) {
            return ["status" => "error", "message" => "La matrícula ingresada no es válida.", "_code" => 400];
        }

        if ($this->camionModel->buscarPorMatricula($matricula)) {
            return ["status" => "error", "message" => "Ya existe un camión registrado con esa matrícula.", "_code" => 409];
        }

        if (isset($datos['capacidad_carga']) && $datos['capacidad_carga'] !== '' && (float) $datos['capacidad_carga'] <= 0) {
            return ["status" => "error", "message" => "La capacidad de carga debe ser un número mayor a cero.", "_code" => 400];
        }

        $capacidad = (isset($datos['capacidad_carga']) && $datos['capacidad_carga'] !== '')
            ? (float) $datos['capacidad_carga']
            : null;

        $this->camionModel->crear($matricula, $capacidad);

        return ["status" => "success", "message" => "Camión registrado de forma persistente.", "_code" => 201];
    }

    public function actualizar(string $matricula, array $datos): array
    {
        if (!$this->camionModel->buscarPorMatricula($matricula)) {
            return ["status" => "error", "message" => "El camión no existe.", "_code" => 404];
        }

        if (empty($datos['estado'])) {
            return ["status" => "error", "message" => "El estado es requerido.", "_code" => 400];
        }

        $estado = trim($datos['estado']);
        if (!in_array($estado, $this->estadosPermitidos, true)) {
            return ["status" => "error", "message" => "El estado '$estado' no es válido.", "_code" => 400];
        }

        if (isset($datos['capacidad_carga']) && $datos['capacidad_carga'] !== '' && (float) $datos['capacidad_carga'] <= 0) {
            return ["status" => "error", "message" => "La capacidad de carga debe ser un número mayor a cero.", "_code" => 400];
        }

        $capacidad = (isset($datos['capacidad_carga']) && $datos['capacidad_carga'] !== '')
            ? (float) $datos['capacidad_carga']
            : null;

        $this->camionModel->actualizar($matricula, $capacidad, $estado);

        return ["status" => "success", "message" => "Camión actualizado correctamente."];
    }

    public function eliminar(string $matricula): array
    {
        if (!$this->camionModel->buscarPorMatricula($matricula)) {
            return ["status" => "error", "message" => "El camión no existe.", "_code" => 404];
        }

        $this->camionModel->eliminar($matricula);

        return ["status" => "success", "message" => "Camión eliminado correctamente."];
    }

    /**
     * Asigna (o reasigna) una cuadrilla a un camión de forma persistente.
     * Reglas:
     *  - El camión destino tiene que estar "Disponible".
     *  - La cuadrilla tiene que ser un usuario con rol 'cuadrilla'.
     *  - Si esa cuadrilla ya estaba en OTRO camión, se la libera de ahí
     *    primero — pero solo si ese otro camión no tiene ninguna
     *    incidencia "en curso" en este momento.
     */
    public function asignarCuadrilla(string $matricula, array $datos): array
    {
        $camion = $this->camionModel->buscarPorMatricula($matricula);
        if (!$camion) {
            return ["status" => "error", "message" => "El camión no existe.", "_code" => 404];
        }

        if ($camion['estado'] !== 'Disponible') {
            return ["status" => "error", "message" => "El camión debe estar Disponible para asignarle una cuadrilla.", "_code" => 400];
        }

        $cuadrillaId = isset($datos['cuadrilla_id']) ? (int) $datos['cuadrilla_id'] : 0;
        if ($cuadrillaId <= 0) {
            return ["status" => "error", "message" => "Debe indicar la cuadrilla a asignar.", "_code" => 400];
        }

        $cuadrilla = $this->usuarioModel->buscarPorId($cuadrillaId);
        if (!$cuadrilla || $cuadrilla['rol'] !== 'cuadrilla') {
            return ["status" => "error", "message" => "La cuadrilla indicada no es válida.", "_code" => 400];
        }

        // Si esa cuadrilla ya estaba en otro camión, hay que liberarla de
        // ahí antes de asignarla acá (esto es lo que permite "reasignar").
        $camionActual = $this->camionModel->buscarPorCuadrilla($cuadrillaId);
        if ($camionActual && $camionActual['matricula'] !== $matricula) {
            if ($this->incidenciaModel->tieneIncidenciasEnCurso($camionActual['matricula'])) {
                return [
                    "status" => "error",
                    "message" => "Esa cuadrilla está trabajando en una incidencia en curso con el camión {$camionActual['matricula']}; no se puede reasignar hasta que se resuelva.",
                    "_code" => 409
                ];
            }
            $this->camionModel->desasignarCuadrilla($camionActual['matricula']);
        }

        $this->camionModel->asignarCuadrilla($matricula, $cuadrillaId);

        return ["status" => "success", "message" => "Cuadrilla asignada al camión correctamente."];
    }

    /**
     * Quita la cuadrilla persistente de un camión. Solo se permite si ese
     * camión no tiene ninguna incidencia "en curso" en este momento.
     */
    public function desasignarCuadrilla(string $matricula): array
    {
        $camion = $this->camionModel->buscarPorMatricula($matricula);
        if (!$camion) {
            return ["status" => "error", "message" => "El camión no existe.", "_code" => 404];
        }

        if (empty($camion['cuadrilla_id'])) {
            return ["status" => "error", "message" => "Este camión no tiene una cuadrilla asignada.", "_code" => 400];
        }

        if ($this->incidenciaModel->tieneIncidenciasEnCurso($matricula)) {
            return ["status" => "error", "message" => "No se puede desasignar: el camión tiene incidencias en curso.", "_code" => 409];
        }

        $this->camionModel->desasignarCuadrilla($matricula);

        return ["status" => "success", "message" => "Cuadrilla desasignada del camión correctamente."];
    }
}
