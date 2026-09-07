<?php
/**
 * ==================================================
 *  MODELO: NOTIFICACION
 * ==================================================
 * Representa la tabla `notificaciones`.
 *
 *  - usuario_id = NULL  ->  anuncio público (lo ve cualquier usuario logueado)
 *  - usuario_id = X     ->  notificación privada para el usuario X
 *                           (ej: cambio de estado de su incidencia)
 */

class Notificacion
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Crea una notificación. Si $usuarioId es null, es un anuncio público. */
    public function crear($usuarioId, string $tipo, string $mensaje, $incidenciaId = null): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notificaciones (usuario_id, tipo, mensaje, incidencia_id)
             VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$usuarioId, $tipo, $mensaje, $incidenciaId]);
    }

    /** Notificaciones privadas de un usuario + todos los anuncios públicos. */
    public function listarParaUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM notificaciones
             WHERE usuario_id = ? OR usuario_id IS NULL
             ORDER BY fecha_creacion DESC"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    /** Historial de anuncios enviados (para que el administrador vea lo que mandó). */
    public function listarAnuncios(): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM notificaciones WHERE tipo = 'anuncio' ORDER BY fecha_creacion DESC"
        );
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM notificaciones WHERE id = ?");
        $stmt->execute([$id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function marcarLeida(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
