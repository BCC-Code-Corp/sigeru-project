<?php
/**
 * ==================================================
 *  CONTROLADOR: CENTROS DE ACOPIO (API Gestión)
 * ==================================================
 */

require_once __DIR__ . '/../models/CentroAcopio.php';

class CentroAcopioController
{
    private CentroAcopio $centroModel;
    private array $estadosPermitidos = ['Operativo', 'Mantenimiento', 'Cerrado'];

    public function __construct(PDO $pdo)
    {
        $this->centroModel = new CentroAcopio($pdo);
    }

    public function listar(): array
    {
        return ["status" => "success", "data" => $this->centroModel->listarTodos()];
    }

    public function obtener(int $id): array
    {
        $centro = $this->centroModel->buscarPorId($id);

        if ($centro) {
            return ["status" => "success", "data" => $centro];
        }

        return ["status" => "error", "message" => "Centro de acopio no encontrado.", "_code" => 404];
    }

    private function validarDatos(array $datos): ?string
    {
        if (empty($datos['nombre']) || empty($datos['ubicacion'])) {
            return "Nombre y ubicación son requeridos.";
        }

        if (strlen(trim($datos['nombre'])) < 3) {
            return "El nombre debe tener al menos 3 caracteres.";
        }

        if (strlen(trim($datos['ubicacion'])) < 5) {
            return "La ubicación debe tener al menos 5 caracteres.";
        }

        $estado = trim($datos['estado'] ?? 'Operativo');
        if (!in_array($estado, $this->estadosPermitidos, true)) {
            return "El estado '$estado' no es válido.";
        }

        return null;
    }

    public function crear(array $datos): array
    {
        $error = $this->validarDatos($datos);
        if ($error) {
            return ["status" => "error", "message" => $error, "_code" => 400];
        }

        $this->centroModel->crear(
            trim($datos['nombre']),
            trim($datos['ubicacion']),
            trim($datos['maquinaria'] ?? ''),
            trim($datos['estado'] ?? 'Operativo')
        );

        return ["status" => "success", "message" => "Centro de acopio registrado con éxito.", "_code" => 201];
    }

    public function actualizar(int $id, array $datos): array
    {
        if (!$this->centroModel->buscarPorId($id)) {
            return ["status" => "error", "message" => "El centro de acopio no existe.", "_code" => 404];
        }

        $error = $this->validarDatos($datos);
        if ($error) {
            return ["status" => "error", "message" => $error, "_code" => 400];
        }

        $this->centroModel->actualizar(
            $id,
            trim($datos['nombre']),
            trim($datos['ubicacion']),
            trim($datos['maquinaria'] ?? ''),
            trim($datos['estado'] ?? 'Operativo')
        );

        return ["status" => "success", "message" => "Centro de acopio actualizado correctamente."];
    }

    public function eliminar(int $id): array
    {
        if (!$this->centroModel->buscarPorId($id)) {
            return ["status" => "error", "message" => "El centro de acopio no existe.", "_code" => 404];
        }

        $this->centroModel->eliminar($id);

        return ["status" => "success", "message" => "Centro de acopio eliminado correctamente."];
    }
}
