<?php
// Contenedores

class Contenedor
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM contenedores WHERE activo=1 ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM contenedores WHERE id = ? AND activo=1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function crear(string $ubicacion, string $estado, string $tipoResiduo, bool $enServicio=true): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO contenedores (ubicacion, estado, tipo_residuo, en_servicio) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$ubicacion, $estado, $tipoResiduo, (int)$enServicio]);
    }

    public function actualizar(int $id, string $ubicacion, string $estado, string $tipoResiduo, bool $enServicio=true): bool
    {
        $stmt = $this->pdo->prepare("UPDATE contenedores SET ubicacion = ?, estado = ?, tipo_residuo = ?, en_servicio=? WHERE id = ? AND activo=1");
        return $stmt->execute([$ubicacion, $estado, $tipoResiduo, (int)$enServicio, $id]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE contenedores SET activo=0, en_servicio=0, motivo_baja=? WHERE id = ? AND activo=1");
        return $stmt->execute([Sesion::datos()['motivo'] ?? ($_GET['motivo'] ?? 'Baja administrativa'), $id]);
    }

    public function asignadoEnCurso(int $id, ?int $cuadrillaId): bool
    {
        $q=$this->pdo->prepare("SELECT i.id FROM incidencias i JOIN sobre s ON s.incidencia_id=i.id WHERE s.contenedor_id=? AND i.cuadrilla_id=? AND i.estado_incidencia='en curso'");
        $q->execute([$id,$cuadrillaId]);
        return (bool)$q->fetch();
    }

    public function enServicio(int $id): bool
    {
        $q=$this->pdo->prepare('SELECT id FROM contenedores WHERE id=? AND activo=1 AND en_servicio=1');
        $q->execute([$id]);
        return (bool)$q->fetch();
    }

}
