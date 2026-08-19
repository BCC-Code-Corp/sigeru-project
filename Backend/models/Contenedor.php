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

    /** Registra un contenedor nuevo. */
    public function crear(string $ubicacion, string $estado): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO contenedores (ubicacion, estado) VALUES (?, ?)");
        return $stmt->execute([$ubicacion, $estado]);
    }
}
