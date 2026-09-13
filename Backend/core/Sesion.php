<?php
require_once __DIR__ . '/Respuesta.php';
require_once __DIR__ . '/Validacion.php';

final class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Strict', 'path'=>'/']);
            if (!session_start()) throw new RuntimeException('No se pudo iniciar la sesión.');
        }
    }

    public static function usuario(PDO $pdo): array
    {
        self::iniciar();
        $q = $pdo->prepare('SELECT u.id,u.nombre,u.email,u.cedula,u.rol,u.auth_version,COALESCE(c.cuadrilla_id,r.cuadrilla_id) cuadrilla_id,o.centro_id FROM usuarios u LEFT JOIN choferes c ON c.id=u.id LEFT JOIN recolectores r ON r.id=u.id LEFT JOIN operarios o ON o.id=u.id WHERE u.id=? AND u.activo=1 AND u.estado_registro=\'aprobado\'');
        $q->execute([$_SESSION['usuario_id'] ?? 0]);
        $usuario = $q->fetch();
        if (!$usuario) self::rechazar('Iniciá sesión para continuar.',401);
        if (($_SESSION['actividad'] ?? 0)<time()-1800 || (int)($_SESSION['auth_version'] ?? 0)!==(int)$usuario['auth_version']) { $_SESSION=[]; self::rechazar('La sesión venció. Iniciá sesión nuevamente.',401); }
        $_SESSION['actividad']=time();
        unset($usuario['auth_version']);
        return $usuario;
    }

    public static function exigir(array $roles): array
    {
        $u = self::usuario($GLOBALS['pdo']);
        if (!in_array($u['rol'],$roles,true)) self::rechazar('No tenés permiso para esta operación.',403);
        return $u;
    }

    public static function rechazar(string $mensaje, int $codigo=400): void
    {
        Respuesta::enviar(['status'=>'error','message'=>$mensaje],$codigo);
    }

    public static function datos(): array
    {
        return $GLOBALS['api_datos'] ?? [];
    }

    public static function proteger(PDO $pdo, string $recurso): void
    {
        self::iniciar();
        $metodo = $_SERVER['REQUEST_METHOD'];
        foreach (['id','usuario_id'] as $clave) {
            if (isset($_GET[$clave]) && (!ctype_digit((string)$_GET[$clave]) || (int)$_GET[$clave]<=0 || (float)$_GET[$clave]>2147483647)) self::rechazar('Identificador inválido.');
        }
        $publico = in_array($recurso,['login','registro'],true);
        if ($metodo !== 'GET') {
            if (in_array($metodo,['POST','PUT','PATCH'],true)) {
                if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) self::rechazar('Se requiere JSON.',415);
                $raw = file_get_contents('php://input');
                $obj = json_decode($raw ?: '{}');
                if (!is_object($obj) || json_last_error() !== JSON_ERROR_NONE) self::rechazar('El cuerpo debe ser un objeto JSON válido.');
                $GLOBALS['api_datos'] = json_decode($raw ?: '{}',true);
                foreach ($GLOBALS['api_datos'] as $v) if (is_array($v) || is_object($v)) self::rechazar('Los campos deben contener valores simples.');
                Validacion::entrada($recurso,$GLOBALS['api_datos']);
            }
            if (!$publico && !hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) self::rechazar('Token de sesión inválido.',403);
        }
        if (!$publico) {
            $u = self::usuario($pdo);
            $GLOBALS['actor'] = $u;
            if ($recurso === 'usuarios') {
                $propio = in_array($metodo,['GET','PUT'],true) && (isset($_GET['id']) ? (string)$_GET['id'] === (string)$u['id'] : $metodo==='GET' && ($_GET['email'] ?? '') === $u['email']);
                if (!$propio) self::exigir(['administrador']);
                if ($propio && $metodo==='PUT' && $u['rol']!=='administrador' && isset($GLOBALS['api_datos']['rol']) && $GLOBALS['api_datos']['rol']!==$u['rol']) self::rechazar('Solo el administrador puede cambiar roles.',403);
                if ($metodo==='PUT' && $u['rol']!=='administrador' && isset($GLOBALS['api_datos']['estado_registro'])) self::rechazar('Solo el administrador puede aprobar registros.',403);
            } elseif (in_array($recurso,['camiones','contenedores','centros_acopio'],true)) {
                if ($metodo !== 'GET') self::exigir($recurso==='contenedores' && $metodo==='PUT' ? ['administrador','chofer','recolector'] : ['administrador']);
            } elseif ($recurso === 'incidencias') {
                if ($metodo === 'POST') self::exigir(['administrador','vecino','chofer','recolector']);
                if ($metodo === 'PUT') self::exigir(($_GET['accion'] ?? '') === 'asignar' ? ['administrador'] : ['administrador','chofer','recolector']);
            } elseif ($recurso === 'notificaciones' && $metodo === 'POST') self::exigir(['administrador']);
        }
        if ($metodo==='DELETE' && in_array($recurso,['camiones','contenedores','centros_acopio'],true)) {
            $motivo=trim($_GET['motivo'] ?? '');
            if ($motivo==='' || strlen($motivo)>255) self::rechazar('Indicá el motivo de la baja (hasta 255 caracteres).');
            $GLOBALS['api_datos']=['motivo'=>$motivo];
        }
        if (in_array($metodo,['POST','PUT','DELETE','PATCH'],true) && !in_array($recurso,['login','logout'],true)) {
            $pdo->beginTransaction();
            $GLOBALS['api_transaccion'] = true;
            $GLOBALS['api_recurso'] = $recurso;
        }
    }
}
