<?php
/**
 * ==================================================
 *  API USUARIOS · CRUD completo (todos los roles)
 * ==================================================
 * Endpoint : /Backend/api/usuarios/usuarios.php
 * GET    ?id=1 | ?email=x@x.com   -> obtiene uno (200 / 404) | sin parámetros -> lista todos (200)
 * POST   body: { nombre, cedula, email, password, rol }         -> crea uno (201 / 400 / 409)
 * PUT    ?id=1  body: { nombre?, email?, rol?, password? }      -> actualiza (200 / 400 / 404 / 409)
 * DELETE ?id=1                                                    -> elimina (200 / 404)
 * Respuesta: { status, message?, usuario?/data? }
 *
 * Usado por el backoffice de Administración para gestionar vecinos,
 * operarios, cuadrillas y otros administradores. El panel también lo
 * usa en modo lectura (GET ?email=) para completar el perfil tras el login.
 */

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/UsuarioController.php';

try {
    $controlador = new UsuarioController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['id']) || !empty($_GET['email'])) {
            Respuesta::enviarResultado($controlador->obtener($_GET));
        }
        Respuesta::enviarResultado($controlador->listar());
    }

    if ($metodo === 'POST') {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        Respuesta::enviarResultado($controlador->crear($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id del usuario."], 400);
        }
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
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
