<?php


class Notificacion
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crear($usuarioId, string $tipo, string $mensaje, $incidenciaId = null): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notificaciones (usuario_id, tipo, mensaje, incidencia_id)
             VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$usuarioId, $tipo, $mensaje, $incidenciaId]);
    }

    public function listarParaUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.*, (l.usuario_id IS NOT NULL) AS leida FROM notificaciones n LEFT JOIN notificaciones_leidas l ON l.notificacion_id=n.id AND l.usuario_id=?
             WHERE n.usuario_id = ? OR n.usuario_id IS NULL
             ORDER BY fecha_creacion DESC"
        );
        $stmt->execute([$usuarioId,$usuarioId]);
        return $stmt->fetchAll();
    }

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
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO notificaciones_leidas(notificacion_id,usuario_id) VALUES (?,?)");
        return $stmt->execute([$id,$GLOBALS['actor']['id']]);
    }
}
