<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../../core/Sesion.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') Sesion::rechazar('Método no soportado.',405);
    Sesion::proteger($pdo,'logout');
    $_SESSION=[];
    $params=session_get_cookie_params();
    setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>$params['path'],'httponly'=>true,'secure'=>$params['secure'],'samesite'=>'Strict']);
    session_destroy();
    Respuesta::enviar(['status'=>'success','message'=>'Sesión cerrada.']);
} catch (Throwable $e) { Respuesta::enviarErrorInterno($e); }
