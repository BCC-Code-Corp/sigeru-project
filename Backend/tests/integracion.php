<?php
// Integración
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[2] ?? '') !== '--base-de-prueba') {
    exit("Uso: php Backend/tests/integracion.php http://127.0.0.1:8092 --base-de-prueba\nCrea registros: no usar con datos reales.\n");
}
$base=rtrim($argv[1],'/').'/Backend/api/';
final class ClientePrueba {
    public string $csrf='';
    private array $cookies=[];
    public static int $total=0;
    public function pedir(string $ruta,string $metodo='GET',?array $datos=null,int $esperado=200,bool $token=true): array {
        global $base;
        $headers=['Content-Type: application/json'];
        if($token) $headers[]='X-CSRF-Token: '.$this->csrf;
        if($this->cookies) $headers[]='Cookie: '.implode('; ', $this->cookies);
        $opciones=['method'=>$metodo,'header'=>implode("\r\n",$headers),'ignore_errors'=>true,'timeout'=>15];
        if($metodo!=='GET') $opciones['content']=json_encode((object)($datos ?? []));
        $body=file_get_contents($base.$ruta,false,stream_context_create(['http'=>$opciones]));
        if($body===false) throw new RuntimeException('No se pudo conectar a '.$ruta);
        $codigo=0;
        foreach($http_response_header as $h) {
            if(preg_match('/^HTTP\/\S+ (\d+)/',$h,$m)) $codigo=(int)$m[1];
            if(preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i',$h,$m)) $this->cookies[$m[1]]=$m[1].'='.$m[2];
        }
        $r=json_decode($body,true,512,JSON_THROW_ON_ERROR);
        if($codigo!==$esperado) throw new RuntimeException("$metodo $ruta: esperado $esperado, recibido $codigo: ".($r['message'] ?? ''));
        self::$total++;
        return $r;
    }
    public function login(string $email,string $password): void {
        $this->csrf=$this->pedir('usuarios/login.php','POST',compact('email','password'))['csrf'];
    }
}
function cedulaPrueba(int $numero): string {
    $digitos=str_pad((string)$numero,7,'0',STR_PAD_LEFT);$suma=0;
    foreach([2,9,8,7,6,3,4] as $i=>$peso) $suma+=(int)$digitos[$i]*$peso;
    return $digitos.((10-$suma%10)%10);
}
function idPorCampo(ClientePrueba $cliente,string $ruta,string $campo,string $valor): int {
    foreach($cliente->pedir($ruta)['data'] as $fila) if((string)$fila[$campo]===$valor) return (int)$fila['id'];
    throw new RuntimeException('No se encontró el registro creado.');
}
try {
    $run=bin2hex(random_bytes(4)); $admin=new ClientePrueba(); $anon=new ClientePrueba(); $vecino=new ClientePrueba();
    $anon->pedir('usuarios/usuarios.php','GET',null,401);
    $admin->login('admin@sigeru.uy','admin123'); $vecino->login('vecino@sigeru.uy','vecino123');
    $vecino->pedir('usuarios/usuarios.php','GET',null,403);
    $admin->pedir('usuarios/usuarios.php','POST',[],403,false);
    foreach(['usuarios/usuarios.php','gestion/contenedores.php','gestion/centros_acopio.php','gestion/maquinaria.php','recoleccion/camiones.php'] as $ruta) {
        $admin->pedir($ruta); $admin->pedir($ruta,'PATCH',[],405); $admin->pedir($ruta,'POST',[],400);
    }
    foreach(['administrador','vecino','chofer','recolector','operario'] as $rol) {
        $email="$rol.$run@example.test";
        $datos=['nombre'=>'Cuenta de prueba','email'=>$email,'cedula'=>cedulaPrueba(random_int(3000000,9999999)),'password'=>'Prueba!234','rol'=>$rol];
        if($rol==='operario') { $datos['centro_id']=1; $datos['especialidad']='Clasificación'; }
        $admin->pedir('usuarios/usuarios.php','POST',$datos,201);
        $admin->pedir('usuarios/usuarios.php','POST',$datos,409);
        $id=idPorCampo($admin,'usuarios/usuarios.php','email',$email);
        $admin->pedir("usuarios/usuarios.php?id=$id",'PUT',array_merge($datos,['nombre'=>'Cuenta modificada']));
        $cuenta=new ClientePrueba();$cuenta->login($email,'Prueba!234');
        $admin->pedir("usuarios/usuarios.php?id=$id",'DELETE');
        $cuenta->pedir('usuarios/sesion.php','GET',null,401);
    }
    $email="registro.$run@example.test";
    $datos=['nombre'=>'Registro pendiente','email'=>$email,'cedula'=>cedulaPrueba(random_int(3000000,9999999)),'password'=>'Prueba!234'];
    $anon->pedir('usuarios/registro.php','POST',$datos,201);
    $anon->pedir('usuarios/login.php','POST',['email'=>$email,'password'=>'Prueba!234'],403);
    $id=idPorCampo($admin,'usuarios/usuarios.php','email',$email);
    $admin->pedir("usuarios/usuarios.php?id=$id",'PUT',['estado_registro'=>'aprobado']);
    $anon->login($email,'Prueba!234');
    $centro=['nombre'=>'Centro '.$run,'ubicacion'=>'Calle prueba 123','capacidad'=>100,'tipo_centro'=>'acopio','estado'=>'Operativo'];
    $admin->pedir('gestion/centros_acopio.php','POST',$centro,201);
    $cid=idPorCampo($admin,'gestion/centros_acopio.php','nombre',$centro['nombre']);
    $admin->pedir("gestion/centros_acopio.php?id=$cid",'PUT',array_merge($centro,['capacidad'=>200]));
    $maquina=['nombre'=>'Máquina '.$run,'estado'=>'Operativo','centro_id'=>$cid];
    $mid=$admin->pedir('gestion/maquinaria.php','POST',$maquina,201)['id'];
    $admin->pedir("gestion/maquinaria.php?id=$mid");
    $admin->pedir("gestion/maquinaria.php?id=$mid",'PUT',array_merge($maquina,['estado'=>'Mantenimiento']));
    $admin->pedir("gestion/maquinaria.php?id=$mid&motivo=Prueba",'DELETE');
    $contenedor=['ubicacion'=>'Prueba '.$run,'estado'=>'funcional','tipo_residuo'=>'Secos','en_servicio'=>0];
    $admin->pedir('gestion/contenedores.php','POST',$contenedor,201);
    $tid=idPorCampo($admin,'gestion/contenedores.php','ubicacion',$contenedor['ubicacion']);
    $admin->pedir("gestion/contenedores.php?id=$tid",'PUT',array_merge($contenedor,['en_servicio'=>1]));
    $admin->pedir("gestion/contenedores.php?id=$tid&motivo=Prueba",'DELETE');
    $admin->pedir("gestion/contenedores.php?id=$tid",'GET',null,404);
    $matricula='T'.strtoupper(substr($run,0,6));
    $admin->pedir('recoleccion/camiones.php','POST',['matricula'=>$matricula,'capacidad_carga'=>20],201);
    $admin->pedir("recoleccion/camiones.php?matricula=$matricula",'PUT',['capacidad_carga'=>30,'estado'=>'Disponible']);
    $admin->pedir("recoleccion/camiones.php?matricula=$matricula&motivo=Prueba",'DELETE');
    $admin->pedir("gestion/centros_acopio.php?id=$cid&motivo=Prueba",'DELETE');
    $admin->pedir('usuarios/notificaciones.php');
    $admin->pedir('usuarios/logout.php','POST');$admin->pedir('usuarios/sesion.php','GET',null,401);
    echo ClientePrueba::$total." comprobaciones HTTP correctas.\n";
} catch(Throwable $error) { fwrite(STDERR,$error->getMessage()."\n");exit(1); }
