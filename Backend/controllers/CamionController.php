<?php
/**
 * ==================================================
 *  CONTROLADOR: CAMIONES
 * ==================================================
 */

require_once __DIR__ . '/../models/Camion.php';

class CamionController
{
    private Camion $camionModel;

    public function __construct(PDO $pdo)
    {
        $this->camionModel = new Camion($pdo);
    }

    public function listar(): array
    {
        return ["status" => "success", "data" => $this->camionModel->listarTodos()];
    }

    public function crear(array $datos): array
    {
        if (empty($datos['matricula'])) {
            return ["status" => "error", "message" => "Matrícula obligatoria."];
        }

        $matricula = strtoupper(trim($datos['matricula']));
        $this->camionModel->crear($matricula);

        return ["status" => "success", "message" => "Camión registrado de forma persistente."];
    }
}
