<?php
// Ejecutar desde CLI. No está disponible por HTTP.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/conexion.php';
$modo=$argv[1] ?? '';
if (!in_array($modo,['nueva','migrar'],true)) exit("Uso: php Backend/database/instalar.php nueva|migrar\n");
$archivo=$modo==='nueva' ? 'sigeru_db.sql' : 'migracion_der.sql';
// DDL hace commit implícito en MySQL. Se registra cada paso completado.
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (archivo varchar(100), paso int, checksum char(64) NOT NULL, PRIMARY KEY(archivo,paso))');
$sql=preg_replace('/^\s*--.*$/m','',file_get_contents(__DIR__.'/'.$archivo));
$pasos=array_values(array_filter(array_map('trim',explode(';',$sql))));
$lock=$pdo->query("SELECT GET_LOCK('sigeru_schema_migration',10)")->fetchColumn();
if (!$lock) exit("Otra migración está en curso.\n");
try {
    if ($modo==='nueva') {
        $hechos=$pdo->query("SELECT COUNT(*) FROM schema_migrations WHERE archivo='sigeru_db.sql'")->fetchColumn();
        $tablas=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if (!$hechos && count($tablas)>1) throw new RuntimeException('La instalación nueva requiere una base vacía. Usá migrar para actualizar.');
    }
    foreach ($pasos as $i=>$consulta) {
        $hash=hash('sha256',$consulta);
        $q=$pdo->prepare('SELECT checksum FROM schema_migrations WHERE archivo=? AND paso=?');
        $q->execute([$archivo,$i]); $previo=$q->fetchColumn();
        if ($previo) {
            if ($previo!==$hash) throw new RuntimeException("El paso $i cambió después de aplicarse; requiere revisión.");
            continue;
        }
        $pdo->exec($consulta);
        $pdo->prepare('INSERT INTO schema_migrations VALUES (?,?,?)')->execute([$archivo,$i,$hash]);
    }
    echo "Esquema actualizado: $archivo.\n";
} catch (Throwable $e) {
    fwrite(STDERR,"Migración detenida: {$e->getMessage()}\n"); exit(1);
} finally { $pdo->query("SELECT RELEASE_LOCK('sigeru_schema_migration')"); }
