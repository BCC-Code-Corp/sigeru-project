<?php
// Usuarios

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/UsuarioController.php';

require_once __DIR__ . '/../../core/Sesion.php';

try {
    Sesion::proteger($pdo, 'usuarios');
    $controlador = new UsuarioController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['id']) || !empty($_GET['email'])) {
            Respuesta::enviarResultado($controlador->obtener($_GET));
        }
        Respuesta::enviarResultado($controlador->listar());
    }

    if ($metodo === 'POST') {
        $datos = Sesion::datos();
        Respuesta::enviarResultado($controlador->crear($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id del usuario."], 400);
        }
        $datos = Sesion::datos();
        Respuesta::enviarResultado($controlador->actualizar((int) $_GET['id'], $datos));
    }

    if ($metodo === 'DELETE') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id del usuario."], 400);
        }
        Respuesta::enviarResultado($controlador->eliminar((int) $_GET['id']));
    }

    Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'usuarios.php');
}
