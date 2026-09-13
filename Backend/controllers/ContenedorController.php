<?php


require_once __DIR__ . '/../models/Contenedor.php';

class ContenedorController
{
    private Contenedor $contenedorModel;
    private PDO $pdo;
    private array $estadosPermitidos = ['funcional', 'roto', 'desbordado'];

    public function __construct(PDO $pdo)
    {
        $this->pdo=$pdo;
        $this->contenedorModel = new Contenedor($pdo);
    }

    public function listar(): array
    {
        $lista=$this->contenedorModel->listarTodos();
        if (isset($_GET['repuestos'])) $lista=array_values(array_filter($lista,fn($c)=>(int)$c['en_servicio']===0));
        if ($GLOBALS['actor']['rol']!=='administrador') $lista=array_values(array_filter($lista,fn($c)=>(int)$c['en_servicio']===1));
        return ['status'=>'success','data'=>$lista];
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

        if (empty(trim($datos['tipo_residuo'] ?? ''))) return 'El tipo de residuo es obligatorio.';
        if (isset($datos['en_servicio']) && !in_array((string)$datos['en_servicio'],['0','1'],true)) return 'En servicio debe ser 0 o 1.';
        return null;
    }

    public function crear(array $datos): array
    {
        $error = $this->validarDatos($datos);
        if ($error) {
            return ["status" => "error", "message" => $error, "_code" => 400];
        }

        $this->contenedorModel->crear(trim($datos['ubicacion']), trim($datos['estado']), trim($datos['tipo_residuo']), (bool)($datos['en_servicio'] ?? 1));

        return ["status" => "success", "message" => "Contenedor registrado con éxito.", "_code" => 201];
    }

    public function actualizar(int $id, array $datos): array
    {
        if (!$this->contenedorModel->buscarPorId($id)) {
            return ["status" => "error", "message" => "El contenedor no existe.", "_code" => 404];
        }

        $actual=$this->contenedorModel->buscarPorId($id);
        if ($GLOBALS['actor']['rol']!=='administrador') {
            if (!$this->contenedorModel->asignadoEnCurso($id,$GLOBALS['actor']['cuadrilla_id'])) throw new DomainException('El contenedor no está asignado a tu cuadrilla.',403);
            $actual=$this->contenedorModel->buscarPorId($id);
            $datos=['estado'=>$datos['estado'] ?? '', 'ubicacion'=>$actual['ubicacion'],'tipo_residuo'=>$actual['tipo_residuo']];
        }
        $error = $this->validarDatos($datos);
        if ($error) {
            return ["status" => "error", "message" => $error, "_code" => 400];
        }

        $this->contenedorModel->actualizar($id, trim($datos['ubicacion']), trim($datos['estado']), trim($datos['tipo_residuo']), (bool)($datos['en_servicio'] ?? $actual['en_servicio']));

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
