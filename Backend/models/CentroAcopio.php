<?php
/**
 * ==================================================
 *  MODELO: CENTRO DE ACOPIO
 * ==================================================
 * Representa la tabla `centros_acopio`: puntos donde se
 * concentran residuos y la maquinaria básica asociada a
 * cada centro (compactadoras, cintas, grúas, etc).
 */

class CentroAcopio
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Devuelve todos los centros de acopio. */
    public function listarTodos(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM centros_acopio ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }

    /** Busca un centro de acopio puntual por id. */
    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM centros_acopio WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Registra un centro de acopio nuevo. */
    public function crear(string $nombre, string $ubicacion, string $maquinaria, string $estado): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO centros_acopio (nombre, ubicacion, maquinaria, estado) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$nombre, $ubicacion, $maquinaria, $estado]);
    }

    /** Actualiza un centro de acopio existente. */
    public function actualizar(int $id, string $nombre, string $ubicacion, string $maquinaria, string $estado): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE centros_acopio SET nombre = ?, ubicacion = ?, maquinaria = ?, estado = ? WHERE id = ?"
        );
        return $stmt->execute([$nombre, $ubicacion, $maquinaria, $estado, $id]);
    }

    /** Elimina un centro de acopio. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM centros_acopio WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
