<?php


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
        $lista=$this->centroModel->listarTodos();
        if ($GLOBALS['actor']['rol']==='operario') $lista=array_values(array_filter($lista,fn($c)=>(int)$c['id']===(int)$GLOBALS['actor']['centro_id']));
        return ['status'=>'success','data'=>$lista];
    }

    public function obtener(int $id): array
    {
        if ($GLOBALS['actor']['rol']==='operario' && $id!==(int)$GLOBALS['actor']['centro_id']) return ['status'=>'error','message'=>'Centro ajeno.','_code'=>403];
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

        if (!isset($datos['capacidad']) || !is_numeric($datos['capacidad']) || $datos['capacidad']<=0) return 'La capacidad máxima debe ser mayor a cero.';
        if (!in_array($datos['tipo_centro'] ?? '', ['acopio','vertedero'],true)) return 'Tipo de instalación inválido.';
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

        $this->centroModel->guardarCapacidad($this->centroModel->ultimoId(),$datos);
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

        $this->centroModel->guardarCapacidad($id,$datos);
        $centro=$this->centroModel->buscarPorId($id);
        if ($centro['capacidad_ocupada']>$centro['capacidad']) return ['status'=>'success','message'=>'Centro actualizado. Alerta: la capacidad ocupada supera la máxima.','alerta'=>true];
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
