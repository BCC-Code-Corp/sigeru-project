<?php
/**
 * ==================================================
 *  MODELO: CAMIÓN
 * ==================================================
 * Representa la tabla `camiones` (flota de recolección).
 * La matrícula es la clave natural: no usamos un id numérico
 * para identificar al camión en la API porque ya es única
 * y es lo que efectivamente identifica al vehículo.
 */

class Camion
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Devuelve todos los camiones ordenados por matrícula, incluyendo el
     * nombre de la cuadrilla que tengan asignada de forma persistente
     * (columna `camiones.cuadrilla_id`), si la tienen. `cuadrilla_nombre`
     * queda en null cuando el camión no tiene cuadrilla asignada.
     */
    public function listarTodos(): array
    {
        $stmt = $this->pdo->query(
            "SELECT camiones.*, usuarios.nombre AS cuadrilla_nombre
             FROM camiones
             LEFT JOIN usuarios ON usuarios.id = camiones.cuadrilla_id
             ORDER BY camiones.matricula ASC"
        );
        return $stmt->fetchAll();
    }

    /** Busca un camión puntual por matrícula (incluye el nombre de su cuadrilla, si tiene). */
    public function buscarPorMatricula(string $matricula)
    {
        $stmt = $this->pdo->prepare(
            "SELECT camiones.*, usuarios.nombre AS cuadrilla_nombre
             FROM camiones
             LEFT JOIN usuarios ON usuarios.id = camiones.cuadrilla_id
             WHERE camiones.matricula = ?"
        );
        $stmt->execute([$matricula]);
        return $stmt->fetch();
    }

    /** Busca el camión (si existe) que tiene asignada una cuadrilla puntual. */
    public function buscarPorCuadrilla(int $cuadrillaId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM camiones WHERE cuadrilla_id = ? LIMIT 1");
        $stmt->execute([$cuadrillaId]);
        return $stmt->fetch();
    }

    /** Registra un camión nuevo con estado inicial "Disponible". */
    public function crear(string $matricula, ?float $capacidad = null): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO camiones (matricula, capacidad_carga, estado) VALUES (?, ?, 'Disponible')"
        );
        return $stmt->execute([$matricula, $capacidad]);
    }

    /** Actualiza capacidad y estado de un camión existente. */
    public function actualizar(string $matricula, ?float $capacidad, string $estado): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET capacidad_carga = ?, estado = ? WHERE matricula = ?");
        return $stmt->execute([$capacidad, $estado, $matricula]);
    }

    /** Cambia solo el estado de un camión (Disponible, En Ruta, Mantenimiento, etc). */
    public function actualizarEstado(string $matricula, string $estado): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET estado = ? WHERE matricula = ?");
        return $stmt->execute([$estado, $matricula]);
    }

    /** Elimina un camión de la flota. */
    public function eliminar(string $matricula): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM camiones WHERE matricula = ?");
        return $stmt->execute([$matricula]);
    }

    /** Asigna (o reemplaza) la cuadrilla persistente de un camión. */
    public function asignarCuadrilla(string $matricula, int $cuadrillaId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET cuadrilla_id = ? WHERE matricula = ?");
        return $stmt->execute([$cuadrillaId, $matricula]);
    }

    /** Quita la cuadrilla persistente asignada a un camión. */
    public function desasignarCuadrilla(string $matricula): bool
    {
        $stmt = $this->pdo->prepare("UPDATE camiones SET cuadrilla_id = NULL WHERE matricula = ?");
        return $stmt->execute([$matricula]);
    }
}
