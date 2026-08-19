<?php
/**
 * ==================================================
 *  CONTROLADOR: CONTENEDORES
 * ==================================================
 */

require_once __DIR__ . '/../models/Contenedor.php';

class ContenedorController
{
    private Contenedor $contenedorModel;

    public function __construct(PDO $pdo)
    {
        $this->contenedorModel = new Contenedor($pdo);
    }

    public function listar(): array
    {
        return ["status" => "success", "data" => $this->contenedorModel->listarTodos()];
    }

    public function crear(array $datos): array
    {
        if (empty($datos['ubicacion']) || empty($datos['estado'])) {
            return ["status" => "error", "message" => "Todos los campos son requeridos."];
        }

        $this->contenedorModel->crear(trim($datos['ubicacion']), trim($datos['estado']));

        return ["status" => "success", "message" => "Contenedor registrado con éxito."];
    }
}
