<?php

class CentroAcopio
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query("SELECT c.*, (SELECT GROUP_CONCAT(nombre SEPARATOR ', ') FROM maquinaria WHERE centro_id=c.id AND activo=1) maquinaria FROM centros_acopio c WHERE activo=1 ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM centros_acopio WHERE id = ? AND activo=1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function crear(string $nombre, string $ubicacion, string $maquinaria, string $estado): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO centros_acopio (nombre, ubicacion, maquinaria, estado) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$nombre, $ubicacion, $maquinaria, $estado]);
    }

    public function actualizar(int $id, string $nombre, string $ubicacion, string $maquinaria, string $estado): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE centros_acopio SET nombre = ?, ubicacion = ?, maquinaria = ?, estado = ? WHERE id = ? AND activo=1"
        );
        return $stmt->execute([$nombre, $ubicacion, $maquinaria, $estado, $id]);
    }

    public function guardarCapacidad(int $id, array $datos): void
    {
        $q=$this->pdo->prepare('UPDATE centros_acopio SET capacidad=?,tipo_centro=? WHERE id=?');
        $q->execute([$datos['capacidad'],$datos['tipo_centro'],$id]);
    }

    public function ultimoId(): int { return (int)$this->pdo->lastInsertId(); }

    /** Elimina un centro de acopio. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE centros_acopio SET activo=0, motivo_baja=? WHERE id = ? AND activo=1");
        return $stmt->execute([Sesion::datos()['motivo'] ?? ($_GET['motivo'] ?? 'Baja administrativa'), $id]);
    }
}
