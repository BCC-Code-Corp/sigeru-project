<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/CamionController.php';

require_once __DIR__ . '/../../core/Sesion.php';

try {
    Sesion::proteger($pdo, 'camiones');
    $controlador = new CamionController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['matricula'])) {
            Respuesta::enviarResultado($controlador->obtener($_GET['matricula']));
        }
        Respuesta::enviarResultado($controlador->listar());
    }

    if ($metodo === 'POST') {
        $datos = Sesion::datos();
        Respuesta::enviarResultado($controlador->crear($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['matricula'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta la matrícula del camión."], 400);
        }
        $datos = Sesion::datos();
        $accion = $_GET['accion'] ?? '';

        if ($accion === 'asignar_cuadrilla') {
            Respuesta::enviarResultado($controlador->asignarCuadrilla($_GET['matricula'], $datos));
        } elseif ($accion === 'desasignar_cuadrilla') {
            Respuesta::enviarResultado($controlador->desasignarCuadrilla($_GET['matricula']));
        } else {
            Respuesta::enviarResultado($controlador->actualizar($_GET['matricula'], $datos));
        }
    }

    if ($metodo === 'DELETE') {
        if (empty($_GET['matricula'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta la matrícula del camión."], 400);
        }
        Respuesta::enviarResultado($controlador->eliminar($_GET['matricula']));
    }

    Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'camiones.php');
}
