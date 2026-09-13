<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/IncidenciaController.php';


require_once __DIR__ . '/../../core/Sesion.php';

try {
    Sesion::proteger($pdo, 'incidencias');
    $controlador = new IncidenciaController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['usuario_id']) && $GLOBALS['actor']['rol'] === 'administrador') {
            Respuesta::enviarResultado($controlador->listarPorUsuario($_GET));
        }
        Respuesta::enviarResultado($controlador->listar());
    }

    if ($metodo === 'POST') {
        $datos = Sesion::datos();
        Respuesta::enviarResultado($controlador->crear($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id de la incidencia."], 400);
        }
        $datos = Sesion::datos();
        $datos['id'] = $_GET['id'];
        $accion = $_GET['accion'] ?? '';

        if ($accion === 'asignar') {
            Respuesta::enviarResultado($controlador->asignar($datos));
        } elseif ($accion === 'resolver') {
            Respuesta::enviarResultado($controlador->resolver($datos));
        } else {
            Respuesta::enviar(["status" => "error", "message" => "Falta indicar ?accion=asignar o ?accion=resolver."], 400);
        }
    }

    Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'incidencias.php');
}
