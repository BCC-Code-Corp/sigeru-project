<?php
// Centros de acopio

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/CentroAcopioController.php';

require_once __DIR__ . '/../../core/Sesion.php';

try {
    Sesion::proteger($pdo, 'centros_acopio');
    $controlador = new CentroAcopioController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['id'])) {
            Respuesta::enviarResultado($controlador->obtener((int) $_GET['id']));
        }
        Respuesta::enviarResultado($controlador->listar());
    }

    if ($metodo === 'POST') {
        $datos = Sesion::datos();
        Respuesta::enviarResultado($controlador->crear($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id del centro de acopio."], 400);
        }
        $datos = Sesion::datos();
        Respuesta::enviarResultado($controlador->actualizar((int) $_GET['id'], $datos));
    }

    if ($metodo === 'DELETE') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id del centro de acopio."], 400);
        }
        Respuesta::enviarResultado($controlador->eliminar((int) $_GET['id']));
    }

    Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'centros_acopio.php');
}
