<?php
/**
 * ==================================================
 *  MODELO: INCIDENCIA
 * ==================================================
 * Representa la tabla `incidencias` (reclamos ciudadanos y su
 * ciclo de vida: abierta -> en curso -> incidencia solucionada).
 */

class Incidencia
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Crea una incidencia reportada por un vecino. Nace con estado 'abierta'. */
    public function crear(string $ubicacion, string $estadoContenedor, string $tipoBasura): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO incidencias (ubicacion, estado_contenedor, tipo_basura, estado_incidencia)
             VALUES (?, ?, ?, 'abierta')"
        );
        return $stmt->execute([$ubicacion, $estadoContenedor, $tipoBasura]);
    }

    /** Devuelve todas las incidencias, las más nuevas primero. */
    public function listarTodas(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM incidencias ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /** Busca una incidencia puntual por su ID. */
    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM incidencias WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** El Operario asigna cuadrilla + camión a una incidencia y la pasa a "en curso". */
    public function asignar(int $id, $cuadrillaId, string $matricula, string $comentario): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE incidencias
             SET cuadrilla_id = ?, matricula_camion = ?, comentario_operario = ?, estado_incidencia = 'en curso'
             WHERE id = ?"
        );
        return $stmt->execute([$cuadrillaId, $matricula, $comentario, $id]);
    }

    /** La Cuadrilla marca la incidencia como resuelta. */
    public function resolver(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE incidencias SET estado_incidencia = 'incidencia solucionada' WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
