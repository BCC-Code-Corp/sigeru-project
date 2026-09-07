<?php
/**
 * ==================================================
 *  CONTROLADOR: CONTENEDORES (API Gestión)
 * ==================================================
 */

require_once __DIR__ . '/../models/Contenedor.php';

class ContenedorController
{
    private Contenedor $contenedorModel;
    private array $estadosPermitidos = ['lleno', 'vacio', 'mantenimiento'];

    public function __construct(PDO $pdo)
    {
        $this->contenedorModel = new Contenedor($pdo);
    }

    public function listar(): array
    {
        return ["status" => "success", "data" => $this->contenedorModel->listarTodos()];
    }

    public function obtener(int $id): array
    {
        $contenedor = $this->contenedorModel->buscarPorId($id);

        if ($contenedor) {
            return ["status" => "success", "data" => $contenedor];
        }

        return ["status" => "error", "message" => "Contenedor no encontrado.", "_code" => 404];
    }

    private function validarDatos(array $datos): ?string
    {
        if (empty($datos['ubicacion']) || empty($datos['estado'])) {
            return "Ubicación y estado son requeridos.";
        }

        if (strlen(trim($datos['ubicacion'])) < 5) {
            return "La ubicación debe tener al menos 5 caracteres.";
        }

        if (!in_array($datos['estado'], $this->estadosPermitidos, true)) {
            return "El estado '{$datos['estado']}' no es válido.";
        }

        return null;
    }

    public function crear(array $datos): array
    {
        $error = $this->validarDatos($datos);
        if ($error) {
            return ["status" => "error", "message" => $error, "_code" => 400];
        }

        $this->contenedorModel->crear(trim($datos['ubicacion']), trim($datos['estado']));

        return ["status" => "success", "message" => "Contenedor registrado con éxito.", "_code" => 201];
    }

    public function actualizar(int $id, array $datos): array
    {
        if (!$this->contenedorModel->buscarPorId($id)) {
            return ["status" => "error", "message" => "El contenedor no existe.", "_code" => 404];
        }

        $error = $this->validarDatos($datos);
        if ($error) {
            return ["status" => "error", "message" => $error, "_code" => 400];
        }

        $this->contenedorModel->actualizar($id, trim($datos['ubicacion']), trim($datos['estado']));

        return ["status" => "success", "message" => "Contenedor actualizado correctamente."];
    }

    public function eliminar(int $id): array
    {
        if (!$this->contenedorModel->buscarPorId($id)) {
            return ["status" => "error", "message" => "El contenedor no existe.", "_code" => 404];
        }

        $this->contenedorModel->eliminar($id);

        return ["status" => "success", "message" => "Contenedor eliminado correctamente."];
    }
}
