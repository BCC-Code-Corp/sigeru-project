<?php
require_once __DIR__.'/../models/Operacion.php';

final class OperacionController
{
    private Operacion $m;
    private const CAMPOS = [
        'cuadrillas'=>['nombre','disponibilidad'],
        'rutas'=>['nombre','zona','frecuencia','horario'],
        'residuos'=>['tipo_residuo'],
        'maquinaria'=>['nombre','estado','centro_id'],
        'asignaciones'=>['ruta_id','cuadrilla_id','matricula','fecha'],
        'recolecciones'=>['contenedor_id','ruta_id','cuadrilla_id','recolector_id','fecha','volumen','simulado'],
        'mantenimientos'=>['descripcion','tipo_man','fecha_man','prox_man'],
        'reparaciones'=>['tipo_reparacion','fecha_inicio','fecha_fin'],
        'recepciones'=>['centro_id','residuo_id','cantidad','simulado'],
        'reclamos'=>['descripcion','ubicacion','prioridad','incidencia_id'],
    ];

    public function __construct(PDO $pdo) { $this->m=new Operacion($pdo); }

    public function atender(string $recurso, string $metodo, array $d, array $f): array
    {
        $u=$GLOBALS['actor'];
        if ($recurso==='capacidad') {
            Sesion::exigir(['administrador','operario']);
            if ($metodo!=='PUT') throw new DomainException('Usá PUT para actualizar la capacidad.',405);
            $centro=$this->centro((int)($d['centro_id'] ?? 0),$u);
            if (!isset($d['capacidad_ocupada']) || !is_numeric($d['capacidad_ocupada']) || $d['capacidad_ocupada']<0) throw new DomainException('La capacidad ocupada debe ser cero o mayor.');
            if (!in_array($d['estado'] ?? '',['Operativo','Mantenimiento','Cerrado'],true)) throw new DomainException('Estado inválido.');
            if ($centro['capacidad']===null) throw new DomainException('El administrador debe registrar la capacidad máxima.');
            $this->m->actualizar('centros_acopio',(int)$centro['id'],['capacidad_ocupada'=>$d['capacidad_ocupada'],'estado'=>$d['estado']]);
            $this->m->insertar('historial_capacidad',['centro_id'=>$centro['id'],'usuario_id'=>$u['id'],'capacidad'=>$centro['capacidad'],'capacidad_ocupada'=>$d['capacidad_ocupada'],'estado'=>$d['estado']]);
            $alerta=$d['capacidad_ocupada']>$centro['capacidad'] ? 'Alerta: se superó la capacidad máxima.' : null;
            return ['status'=>'success','message'=>$alerta ?: 'Capacidad y recepción actualizadas.','alerta'=>$alerta];
        }
        if ($recurso==='reportes') {
            Sesion::exigir(['administrador']);
            if ($metodo!=='GET') throw new DomainException('Método no soportado.',405);
            return ['status'=>'success','data'=>$this->reportes($f)];
        }
        if ($recurso==='relaciones') return $this->relaciones($metodo,$d,$u);
        if (!isset(self::CAMPOS[$recurso])) throw new DomainException('Recurso inexistente.',404);
        if ($metodo==='GET') return ['status'=>'success','data'=>$this->listar($recurso,$u,$f)];
        if (!in_array($metodo,['POST','PUT','DELETE'],true)) throw new DomainException('Método no soportado.',405);
        $roles=['administrador'];
        if ($recurso==='recolecciones' && $metodo==='POST') $roles[]='recolector';
        if ($recurso==='reclamos' && $metodo==='POST') $roles[]='vecino';
        if (in_array($recurso,['recepciones','maquinaria'],true) && $metodo!=='DELETE') $roles[]='operario';
        Sesion::exigir($roles);
        if ($metodo==='DELETE') {
            if (!in_array($recurso,['rutas','maquinaria'],true)) throw new DomainException('El historial de este recurso se conserva.',405);
            $this->m->registroActivo($recurso,[$f['id'] ?? 0],'Registro inexistente.');
            $baja=['activo'=>0];
            if ($recurso==='maquinaria') {
                $motivo=trim($f['motivo'] ?? '');
                if ($motivo==='' || strlen($motivo)>255) throw new DomainException('Indicá un motivo de baja de hasta 255 caracteres.');
                $baja['motivo_baja']=$motivo;
            }
            $this->m->actualizar($recurso,(int)$f['id'],$baja);
            return ['status'=>'success','message'=>'Registro dado de baja; historial conservado.'];
        }
        if ($metodo==='PUT' && !in_array($recurso,['cuadrillas','rutas','maquinaria','residuos'],true)) throw new DomainException('El registro histórico no se sobrescribe.',405);
        $datos=[];
        $opcionales=['volumen','simulado','prox_man','fecha_fin'];
        foreach (self::CAMPOS[$recurso] as $c) {
            if (!isset($d[$c]) || $d[$c]==='') {
                if (in_array($c,$opcionales,true)) continue;
                throw new DomainException("Falta el campo $c.");
            }
            $v=is_string($d[$c]) ? trim($d[$c]) : $d[$c];
            if ($v==='' || (is_string($v) && strlen($v)>1000)) throw new DomainException("Valor inválido: $c.");
            if (str_ends_with($c,'_id') && (!ctype_digit((string)$v) || (int)$v<=0)) throw new DomainException("Identificador inválido: $c.");
            if (in_array($c,['volumen','cantidad'],true) && (!is_numeric($v) || $v<=0)) throw new DomainException("$c debe ser mayor a cero.");
            if (in_array($c,['simulado','disponibilidad'],true) && !in_array((string)$v,['0','1'],true)) throw new DomainException("$c debe ser 0 o 1.");
            if (str_starts_with($c,'fecha') || $c==='prox_man') $this->fecha((string)$v,$c);
            $datos[$c]=$v;
        }
        if (isset($datos['fecha_fin']) && $datos['fecha_fin']<$datos['fecha_inicio']) throw new DomainException('La fecha final es anterior al inicio.');
        if (isset($datos['prox_man']) && $datos['prox_man']<$datos['fecha_man']) throw new DomainException('El próximo mantenimiento es anterior al realizado.');
        if ($recurso==='maquinaria') {
            $this->centro((int)$datos['centro_id'],$u);
            if (!in_array($datos['estado'],['Operativo','Mantenimiento','Cerrado'],true)) throw new DomainException('Estado inválido.');
            if ($metodo==='PUT' && $u['rol']==='operario') $this->m->maquinariaDelCentro([$f['id'] ?? 0,$u['centro_id']],'Maquinaria ajena.');
        }
        if ($recurso==='asignaciones') $this->asignacion($datos);
        if ($recurso==='recolecciones') {
            if ($u['rol']==='recolector' && ((int)$datos['recolector_id']!==(int)$u['id'] || (int)$datos['cuadrilla_id']!==(int)$u['cuadrilla_id'])) throw new DomainException('Recolección ajena.',403);
            $this->m->recolectorDeCuadrilla([$datos['recolector_id'],$datos['cuadrilla_id']],'Recolector no pertenece a la cuadrilla.');
            $this->m->asignacionDelContenedor([$datos['ruta_id'],$datos['cuadrilla_id'],$datos['fecha'],$datos['contenedor_id']],'No existe asignación para ese contenedor, cuadrilla y fecha.');
        }
        $alerta=null;
        if ($recurso==='recepciones') {
            $centro=$this->centro((int)$datos['centro_id'],$u);
            if ($centro['estado']!=='Operativo' || $centro['capacidad']===null) throw new DomainException('El centro debe estar operativo y tener capacidad registrada.');
            $this->m->residuoHabilitado([$datos['centro_id'],$datos['residuo_id']],'Tipo de residuo no habilitado.');
            $datos['operario_id']=$u['id'];
            $ocupada=(float)$centro['capacidad_ocupada']+(float)$datos['cantidad'];
            $this->m->actualizar('centros_acopio',(int)$datos['centro_id'],['capacidad_ocupada'=>$ocupada]);
            $this->m->insertar('historial_capacidad',['centro_id'=>$centro['id'],'usuario_id'=>$u['id'],'capacidad'=>$centro['capacidad'],'capacidad_ocupada'=>$ocupada,'estado'=>$centro['estado']]);
            if ($ocupada>(float)$centro['capacidad']) $alerta='La capacidad máxima de la instalación fue superada.';
        }
        if ($recurso==='reclamos') {
            $this->m->vecinoExistente([$u['id']],'Solo un vecino puede registrar un reclamo.');
            $this->m->incidenciaPropiaAbierta([$datos['incidencia_id'],$u['id']],'Seleccioná una incidencia propia abierta.');
            if (!in_array($datos['prioridad'],['baja','media','alta'],true)) throw new DomainException('Prioridad inválida.');
            $datos['usuario_id']=$u['id'];
        }
        if ($recurso==='mantenimientos') $this->m->camionExistente([$d['matricula'] ?? ''],'Camión inexistente.');
        if ($recurso==='reparaciones') $this->m->incidenciaConContenedor([$d['incidencia_id'] ?? 0],'La reparación requiere una incidencia asociada a un contenedor.');
        if ($metodo==='PUT') {
            $id=(int)($f['id'] ?? 0);
            if (!$this->m->registroEditable($recurso,[$id])) throw new DomainException('Registro inexistente.',404);
            $this->m->actualizar($recurso,$id,$datos);
        } else $id=$this->m->insertar($recurso,$datos);
        if ($recurso==='mantenimientos') $this->m->insertar('recibe',['matricula'=>$d['matricula'],'mantenimiento_id'=>$id]);
        if ($recurso==='reparaciones') $this->m->insertar('necesita',['incidencia_id'=>$d['incidencia_id'],'reparacion_id'=>$id]);
        return ['status'=>'success','message'=>$alerta ?: 'Registro guardado.','alerta'=>$alerta,'id'=>$id,'_code'=>$metodo==='POST'?201:200];
    }

    private function listar(string $r,array $u,array $f): array
    {
        $alcance=[];
        if ($u['rol']!=='administrador') {
            if ($r==='reclamos') $alcance=['usuario_id'=>$u['id']];
            elseif (in_array($r,['recepciones','maquinaria'],true) && $u['rol']==='operario') $alcance=['centro_id'=>$u['centro_id']];
            elseif (in_array($r,['asignaciones','recolecciones','rutas'],true) && in_array($u['rol'],['chofer','recolector'],true)) $alcance=['cuadrilla_id'=>$u['cuadrilla_id']];
            elseif ($r!=='residuos') throw new DomainException('No tenés permiso.',403);
        }
        $rows=$this->m->listar($r,$alcance,$f['id'] ?? null);
        if (isset($f['id']) && !$rows) throw new DomainException('Registro inexistente.',404);
        return $rows;
    }

    private function fecha(string $v,string $campo): void
    {
        $formato=strlen($v)===10 ? 'Y-m-d' : 'Y-m-d H:i:s';
        $fecha=DateTimeImmutable::createFromFormat('!'.$formato,$v);
        if (!$fecha || $fecha->format($formato)!==$v) throw new DomainException("Fecha inválida: $campo.");
    }

    private function centro(int $id,array $u): array
    {
        if ($u['rol']==='operario' && $id!==(int)$u['centro_id']) throw new DomainException('Esta instalación no está a tu cargo.',403);
        return $this->m->centroDisponible([$id],'Centro no disponible.');
    }

    private function asignacion(array $d): void
    {
        $this->m->rutaDisponible([$d['ruta_id']],'Ruta no disponible.');
        $this->m->cuadrillaDisponible([$d['cuadrilla_id']],'Cuadrilla no disponible.');
        $this->m->camionDisponibleDeCuadrilla([$d['matricula'],$d['cuadrilla_id']],'Camión no disponible para la cuadrilla.');
        $contenedores=$this->m->contenedoresDeRuta([$d['ruta_id']]);
        if (!$contenedores) throw new DomainException('La ruta no tiene contenedores.');
        foreach ($contenedores as $c) {
            if (!$c['en_servicio']) throw new DomainException('La ruta contiene un contenedor dado de baja.');
            $this->m->incidenciaAbiertaDelContenedor([$c['id']],'Cada contenedor debe tener una incidencia abierta (RF-28.1).');
        }
        $this->m->vincularCuadrillaRuta([$d['cuadrilla_id'],$d['ruta_id']]);
        $this->m->vincularCamionRuta([$d['matricula'],$d['ruta_id']]);
        $this->m->planificarIncidencias([$d['ruta_id'],$d['cuadrilla_id'],$d['matricula'],$d['ruta_id']]);
    }

    private function relaciones(string $metodo,array $d,array $u): array
    {
        Sesion::exigir(['administrador']);
        if ($metodo!=='POST') throw new DomainException('Usá POST para guardar relaciones.',405);
        switch ($d['accion'] ?? '') {
            case 'miembro':
                $persona=$this->m->usuarioActivo([$d['usuario_id'] ?? 0],'Usuario no disponible.');
                if (!in_array($persona['rol'],['chofer','recolector'],true)) throw new DomainException('El integrante debe ser chofer o recolector.');
                $this->m->cuadrillaExistente([$d['cuadrilla_id'] ?? 0],'Cuadrilla inexistente.');
                $tabla=$persona['rol']==='chofer'?'choferes':'recolectores';
                $this->m->actualizar($tabla,(int)$persona['id'],['cuadrilla_id'=>$d['cuadrilla_id']]);
                break;
            case 'chofer':
                $this->m->choferActivo([$d['chofer_id'] ?? 0],'Chofer no disponible.');
                $this->m->camionActivo([$d['matricula'] ?? ''],'Camión no disponible.');
                $this->m->asignarChofer([$d['chofer_id'],$d['modelo'] ?? null,$d['matricula']]);
                $this->m->actualizar('choferes',(int)$d['chofer_id'],['lic_conducir'=>$d['lic_conducir'] ?? null]);
                break;
            case 'operario':
                $this->centro((int)($d['centro_id'] ?? 0),$u);
                $this->m->operarioActivo([$d['usuario_id'] ?? 0],'Operario no disponible.');
                $this->m->actualizar('operarios',(int)$d['usuario_id'],['centro_id'=>$d['centro_id'],'especialidad'=>$d['especialidad'] ?? null]);
                break;
            case 'contenedor':
                $this->m->contenedorEnServicio([$d['contenedor_id'] ?? 0],'Contenedor fuera de servicio.');
                $this->m->rutaActiva([$d['ruta_id'] ?? 0],'Ruta fuera de servicio.');
                $this->m->vincularContenedorRuta([$d['ruta_id'],$d['contenedor_id']]);
                break;
            case 'residuo':
                $this->centro((int)($d['centro_id'] ?? 0),$u);
                $this->m->habilitarResiduo([$d['centro_id'],$d['residuo_id'] ?? 0]);
                break;
            default: throw new DomainException('Relación no reconocida.');
        }
        return ['status'=>'success','message'=>'Relación guardada.'];
    }

    private function reportes(array $f): array
    {
        $desde=$f['desde'] ?? '1970-01-01'; $hasta=$f['hasta'] ?? date('Y-m-d');
        $this->fecha($desde,'desde'); $this->fecha($hasta,'hasta');
        if ($desde>$hasta) throw new DomainException('Período inválido.');
        $args=[$desde,$hasta.' 23:59:59'];
        return $this->m->reportes($args);
    }
}
