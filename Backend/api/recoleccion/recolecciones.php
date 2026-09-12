<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../../core/Sesion.php';
require_once __DIR__.'/../../controllers/OperacionController.php';
try {
    Sesion::proteger($pdo,'recolecciones');
    $controller=new OperacionController($pdo);
    Respuesta::enviarResultado($controller->atender('recolecciones',$_SERVER['REQUEST_METHOD'],Sesion::datos(),$_GET));
} catch (Throwable $e) { Respuesta::enviarErrorInterno($e,'recolecciones'); }
