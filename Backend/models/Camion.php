<?php
/**
 * ==================================================
 *  MODELO: CAMIÓN
 * ==================================================
 * Representa la tabla `camiones` (flota de recolección).
 */

class Camion
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Devuelve todos los camiones ordenados por matrícula. */
    public function listarTodos(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM camiones ORDER BY matricula ASC");
        return $stmt->fetchAll();
    }

    /** Registra un camión nuevo con estado inicial "Disponible". */
    public function crear(string $matricula): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO camiones (matricula, estado) VALUES (?, 'Disponible')");
        return $stmt->execute([$matricula]);
    }

    /** Cambia el estado de un camión (Disponible, En Ruta, Mantenimiento, etc). */
    public function actualizarEstado(string $matricula, string $estado): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET estado = ? WHERE matricula = ?");
        return $stmt->execute([$estado, $matricula]);
    }
}
