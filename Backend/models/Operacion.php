<?php
final class Operacion
{
    public function __construct(private PDO $pdo) {}

    public function consultar(string $sql, array $valores=[]): array
    {
        $q=$this->pdo->prepare($sql); $q->execute($valores); return $q->fetchAll();
    }

    public function ejecutar(string $sql, array $valores=[]): void
    {
        $q=$this->pdo->prepare($sql); $q->execute($valores);
    }

    public function insertar(string $tabla, array $datos): int
    {
        $campos=implode(',',array_keys($datos));
        $marcas=implode(',',array_fill(0,count($datos),'?'));
        $this->ejecutar("INSERT INTO $tabla ($campos) VALUES ($marcas)",array_values($datos));
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar(string $tabla, int $id, array $datos): void
    {
        $campos=implode(',',array_map(fn($c)=>"$c=?",array_keys($datos)));
        $this->ejecutar("UPDATE $tabla SET $campos WHERE id=?",[...array_values($datos),$id]);
    }

    public function exigir(string $sql, array $valores, string $mensaje): array
    {
        $rows=$this->consultar($sql,$valores);
        if (!$rows) throw new DomainException($mensaje,409);
        return $rows[0];
    }

    public function reportes(array $args): array
    {
        return [
            'incidencias'=>$this->consultar('SELECT estado_incidencia,COUNT(*) total FROM incidencias WHERE fecha_creacion BETWEEN ? AND ? GROUP BY estado_incidencia',$args),
            'residuos'=>$this->consultar('SELECT r.tipo_residuo,p.simulado,SUM(p.cantidad) cantidad FROM recepciones p JOIN residuos r ON r.id=p.residuo_id WHERE p.fecha_creacion BETWEEN ? AND ? GROUP BY r.tipo_residuo,p.simulado',$args),
            'capacidad'=>$this->consultar('SELECT id,nombre,tipo_centro,capacidad,capacidad_ocupada,capacidad-capacidad_ocupada disponible,ROUND(100*capacidad_ocupada/NULLIF(capacidad,0),2) ocupacion_porcentaje FROM centros_acopio'),
            'capacidad_en_periodo'=>$this->consultar('SELECT * FROM historial_capacidad WHERE fecha BETWEEN ? AND ? ORDER BY fecha DESC',$args),
            'contenedores_baja'=>$this->consultar('SELECT * FROM contenedores WHERE activo=0'),
            'camiones_baja'=>$this->consultar('SELECT * FROM camiones WHERE activo=0'),
            'centros_baja'=>$this->consultar('SELECT * FROM centros_acopio WHERE activo=0'),
            'recolecciones'=>$this->consultar('SELECT contenedor_id,simulado,COUNT(*) total,SUM(volumen) volumen FROM recolecciones WHERE fecha BETWEEN ? AND ? GROUP BY contenedor_id,simulado',$args),
            'historial'=>$this->consultar('SELECT * FROM historial_incidencias WHERE fecha BETWEEN ? AND ? ORDER BY id DESC',$args),
        ];
    }

    public function registroActivo(string $recurso, array $valores, string $mensaje): array
    {
        return $this->exigir("SELECT id FROM $recurso WHERE id=? AND activo=1",$valores,$mensaje);
    }

    public function maquinariaDelCentro(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM maquinaria WHERE id=? AND centro_id=?',$valores,$mensaje);
    }

    public function recolectorDeCuadrilla(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT r.id FROM recolectores r JOIN usuarios u ON u.id=r.id WHERE r.id=? AND r.cuadrilla_id=? AND u.activo=1 AND u.rol=\'recolector\'',$valores,$mensaje);
    }

    public function asignacionDelContenedor(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT a.id FROM asignaciones a JOIN contiene c ON c.ruta_id=a.ruta_id JOIN contenedores t ON t.id=c.contenedor_id WHERE a.ruta_id=? AND a.cuadrilla_id=? AND a.fecha=DATE(?) AND c.contenedor_id=? AND t.en_servicio=1',$valores,$mensaje);
    }

    public function residuoHabilitado(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT centro_id FROM gestionan WHERE centro_id=? AND residuo_id=?',$valores,$mensaje);
    }

    public function vecinoExistente(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM vecinos WHERE id=?',$valores,$mensaje);
    }

    public function incidenciaPropiaAbierta(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM incidencias WHERE id=? AND usuario_id=? AND estado_incidencia<>\'cerrada\'',$valores,$mensaje);
    }

    public function camionExistente(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT matricula FROM camiones WHERE matricula=?',$valores,$mensaje);
    }

    public function incidenciaConContenedor(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT incidencia_id FROM sobre WHERE incidencia_id=?',$valores,$mensaje);
    }

    public function registroEditable(string $recurso, array $valores=[]): array
    {
        $activo=in_array($recurso,['rutas','maquinaria'],true)?' AND activo=1':'';
        return $this->consultar("SELECT id FROM $recurso WHERE id=?$activo",$valores);
    }

    public function centroDisponible(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT * FROM centros_acopio WHERE id=? AND activo=1 FOR UPDATE',$valores,$mensaje);
    }

    public function rutaDisponible(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM rutas WHERE id=? AND activo=1 FOR UPDATE',$valores,$mensaje);
    }

    public function cuadrillaDisponible(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM cuadrillas WHERE id=? AND disponibilidad=1 FOR UPDATE',$valores,$mensaje);
    }

    public function camionDisponibleDeCuadrilla(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM camiones WHERE matricula=? AND activo=1 AND estado=\'Disponible\' AND cuadrilla_id=? FOR UPDATE',$valores,$mensaje);
    }

    public function contenedoresDeRuta(array $valores=[]): array
    {
        return $this->consultar('SELECT c.id,c.en_servicio FROM contiene t JOIN contenedores c ON c.id=t.contenedor_id WHERE t.ruta_id=?',$valores);
    }

    public function incidenciaAbiertaDelContenedor(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT i.id FROM incidencias i JOIN sobre s ON s.incidencia_id=i.id WHERE s.contenedor_id=? AND i.estado_incidencia=\'abierta\' FOR UPDATE',$valores,$mensaje);
    }

    public function vincularCuadrillaRuta(array $valores=[]): void
    {
        $this->ejecutar('INSERT IGNORE INTO sigue(cuadrilla_id,ruta_id) VALUES (?,?)',$valores);
    }

    public function vincularCamionRuta(array $valores=[]): void
    {
        $this->ejecutar('INSERT IGNORE INTO realiza(matricula,ruta_id) VALUES (?,?)',$valores);
    }

    public function planificarIncidencias(array $valores=[]): void
    {
        $this->ejecutar("UPDATE incidencias i JOIN sobre s ON s.incidencia_id=i.id JOIN contiene c ON c.contenedor_id=s.contenedor_id SET i.ruta_id=?,i.cuadrilla_id=?,i.matricula_camion=? WHERE c.ruta_id=? AND i.estado_incidencia='abierta'",$valores);
    }

    public function usuarioActivo(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id,rol FROM usuarios WHERE id=? AND activo=1',$valores,$mensaje);
    }

    public function cuadrillaExistente(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM cuadrillas WHERE id=?',$valores,$mensaje);
    }

    public function choferActivo(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT c.id FROM choferes c JOIN usuarios u ON u.id=c.id WHERE c.id=? AND u.activo=1 AND u.rol=\'chofer\'',$valores,$mensaje);
    }

    public function camionActivo(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM camiones WHERE matricula=? AND activo=1',$valores,$mensaje);
    }

    public function asignarChofer(array $valores=[]): void
    {
        $this->ejecutar('UPDATE camiones SET chofer_id=?,modelo=? WHERE matricula=?',$valores);
    }

    public function operarioActivo(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM usuarios WHERE id=? AND rol=\'operario\' AND activo=1',$valores,$mensaje);
    }

    public function contenedorEnServicio(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM contenedores WHERE id=? AND en_servicio=1',$valores,$mensaje);
    }

    public function rutaActiva(array $valores, string $mensaje): array
    {
        return $this->exigir('SELECT id FROM rutas WHERE id=? AND activo=1',$valores,$mensaje);
    }

    public function vincularContenedorRuta(array $valores=[]): void
    {
        $this->ejecutar('INSERT IGNORE INTO contiene(ruta_id,contenedor_id) VALUES (?,?)',$valores);
    }

    public function habilitarResiduo(array $valores=[]): void
    {
        $this->ejecutar('INSERT IGNORE INTO gestionan(centro_id,residuo_id) VALUES (?,?)',$valores);
    }

    public function listar(string $r,array $alcance,?int $id): array
    {
        $where=[]; $args=[];
        foreach ($alcance as $campo=>$valor) {
            if (!in_array($campo,['usuario_id','centro_id','cuadrilla_id'],true)) throw new LogicException('Filtro inválido.');
            $where[]=($r==='rutas' && $campo==='cuadrilla_id') ? 'id IN (SELECT ruta_id FROM sigue WHERE cuadrilla_id=?)' : "$campo=?";
            $args[]=$valor;
        }
        if (in_array($r,['rutas','maquinaria'],true)) $where[]='activo=1';
        if ($id!==null) { $where[]='id=?'; $args[]=$id; }
        return $this->consultar("SELECT * FROM $r".($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY id DESC',$args);
    }
}
