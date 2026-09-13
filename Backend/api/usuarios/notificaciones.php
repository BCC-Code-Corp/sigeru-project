<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/NotificacionController.php';


require_once __DIR__ . '/../../core/Sesion.php';

try {
    Sesion::proteger($pdo, 'notificaciones');
    $controlador = new NotificacionController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['anuncios'])) {
            Respuesta::enviar($controlador->listarAnuncios());
        }
        Respuesta::enviarResultado($controlador->listar($_GET));
    }

    if ($metodo === 'POST') {
        $datos = Sesion::datos();
        Respuesta::enviarResultado($controlador->crearAnuncio($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id de la notificación."], 400);
        }
        Respuesta::enviarResultado($controlador->marcarLeida($_GET));
    }

    Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'notificaciones.php');
}
