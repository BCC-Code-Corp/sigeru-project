<?php
/**
 * ==================================================
 *  MODELO: CONTENEDOR
 * ==================================================
 * Representa la tabla `contenedores` (puntos de recolección).
 */

class Contenedor
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Devuelve todos los contenedores, los más nuevos primero. */
    public function listarTodos(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM contenedores ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /** Busca un contenedor puntual por id. */
    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM contenedores WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Registra un contenedor nuevo. */
    public function crear(string $ubicacion, string $estado): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO contenedores (ubicacion, estado) VALUES (?, ?)");
        return $stmt->execute([$ubicacion, $estado]);
    }

    /** Actualiza ubicación y estado de un contenedor existente. */
    public function actualizar(int $id, string $ubicacion, string $estado): bool
    {
        $stmt = $this->pdo->prepare("UPDATE contenedores SET ubicacion = ?, estado = ? WHERE id = ?");
        return $stmt->execute([$ubicacion, $estado, $id]);
    }

    /** Elimina un contenedor. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM contenedores WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
