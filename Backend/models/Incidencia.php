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

    /** Crea una incidencia reportada por un vecino. Nace con estado 'abierta'.
     *  $latitud/$longitud son opcionales: vienen del pin que el vecino
     *  confirma en el mapa al reportar (Frontend, con Leaflet + Nominatim);
     *  si por algún motivo no llegan, quedan en null y la incidencia sigue
     *  guardándose igual, solo que el mapa la va a tener que geolocalizar
     *  por texto como al resto de los puntos sin coordenadas. */
    public function crear(string $ubicacion, string $estadoContenedor, string $tipoBasura, $usuarioId = null, $latitud = null, $longitud = null): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO incidencias (ubicacion, latitud, longitud, estado_contenedor, tipo_basura, estado_incidencia, usuario_id)
             VALUES (?, ?, ?, ?, ?, 'abierta', ?)"
        );
        return $stmt->execute([$ubicacion, $latitud, $longitud, $estadoContenedor, $tipoBasura, $usuarioId]);
    }

    /** Devuelve todas las incidencias, las más nuevas primero. */
    public function listarTodas(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM incidencias ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /** Devuelve únicamente las incidencias reportadas por un vecino puntual. */
    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM incidencias WHERE usuario_id = ? ORDER BY id DESC");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public function contarActivasPorUsuario(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM incidencias WHERE usuario_id = ? AND estado_incidencia <> 'cerrada'");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    /** Busca una incidencia puntual por su ID. */
    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM incidencias WHERE id = ? FOR UPDATE");
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
    public function resolver(int $id, string $solucion): bool
    {
        $stmt = $this->pdo->prepare("UPDATE incidencias SET estado_incidencia = 'cerrada', solucion=?, fecha_cierre=CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$solucion,$id]);
    }

    /**
     * Indica si un camión (por matrícula) tiene alguna incidencia "en
     * curso" asignada en este momento. Se usa antes de desasignar o
     * reasignar su cuadrilla persistente, para no dejar a una cuadrilla
     * que está trabajando en una incidencia sin el camión que está usando.
     */
    public function tieneIncidenciasEnCurso(string $matricula): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM incidencias WHERE matricula_camion = ? AND estado_incidencia = 'en curso'"
        );
        $stmt->execute([$matricula]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function describir(int $id, string $descripcion): void
    {
        $this->pdo->prepare('UPDATE incidencias SET descripcion=? WHERE id=?')->execute([$descripcion,$id]);
    }

    public function asociarContenedor(int $id, int $contenedorId): void
    {
        $this->pdo->prepare('INSERT INTO sobre(contenedor_id,incidencia_id) VALUES (?,?)')->execute([$contenedorId,$id]);
    }

    public function listarPorCuadrilla(?int $id): array
    {
        $q=$this->pdo->prepare('SELECT * FROM incidencias WHERE cuadrilla_id=? ORDER BY id DESC');
        $q->execute([$id]);
        return $q->fetchAll();
    }

    public function cerrarReclamos(int $id): void
    {
        $this->pdo->prepare("UPDATE reclamos SET estado='cerrado' WHERE incidencia_id=?")->execute([$id]);
    }

    public function registrarHistorial(int $id, int $autor, ?string $anterior, string $nuevo, string $observacion): void
    {
        $this->pdo->prepare('INSERT INTO historial_incidencias(incidencia_id,usuario_id,estado_anterior,estado_nuevo,observacion) VALUES (?,?,?,?,?)')->execute([$id,$autor,$anterior,$nuevo,$observacion]);
    }

}
