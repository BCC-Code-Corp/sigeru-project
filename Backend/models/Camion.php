<?php
// Camiones

class Camion
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query(
            "SELECT camiones.*, cuadrillas.nombre AS cuadrilla_nombre
             FROM camiones
             LEFT JOIN cuadrillas ON cuadrillas.id = camiones.cuadrilla_id
             WHERE camiones.activo=1 ORDER BY camiones.matricula ASC"
        );
        return $stmt->fetchAll();
    }

    public function buscarPorMatricula(string $matricula)
    {
        $stmt = $this->pdo->prepare(
            "SELECT camiones.*, cuadrillas.nombre AS cuadrilla_nombre
             FROM camiones
             LEFT JOIN cuadrillas ON cuadrillas.id = camiones.cuadrilla_id
             WHERE camiones.matricula = ? AND camiones.activo=1 FOR UPDATE"
        );
        $stmt->execute([$matricula]);
        return $stmt->fetch();
    }

    public function buscarPorCuadrilla(int $cuadrillaId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM camiones WHERE cuadrilla_id = ? AND activo=1 LIMIT 1");
        $stmt->execute([$cuadrillaId]);
        return $stmt->fetch();
    }

    public function crear(string $matricula, ?float $capacidad = null): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO camiones (matricula, capacidad_carga, estado) VALUES (?, ?, 'Disponible')"
        );
        return $stmt->execute([$matricula, $capacidad]);
    }

    public function actualizar(string $matricula, ?float $capacidad, string $estado): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET capacidad_carga = ?, estado = ? WHERE matricula = ?");
        return $stmt->execute([$capacidad, $estado, $matricula]);
    }

    public function actualizarEstado(string $matricula, string $estado): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET estado = ? WHERE matricula = ?");
        return $stmt->execute([$estado, $matricula]);
    }

    public function eliminar(string $matricula): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET activo=0, motivo_baja=? WHERE matricula = ?");
        return $stmt->execute([Sesion::datos()['motivo'] ?? ($_GET['motivo'] ?? 'Baja administrativa'), $matricula]);
    }

    public function asignarCuadrilla(string $matricula, int $cuadrillaId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET cuadrilla_id = ? WHERE matricula = ?");
        return $stmt->execute([$cuadrillaId, $matricula]);
    }

    public function desasignarCuadrilla(string $matricula): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET cuadrilla_id = NULL WHERE matricula = ?");
        return $stmt->execute([$matricula]);
    }

    public function buscarCuadrillaDisponible(int $id): array|false
    {
        $q=$this->pdo->prepare('SELECT * FROM cuadrillas WHERE id=? AND disponibilidad=1 FOR UPDATE');
        $q->execute([$id]);
        return $q->fetch();
    }

}
