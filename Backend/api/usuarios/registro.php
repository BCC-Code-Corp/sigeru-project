<?php
/**
 * ==================================================
 *  API USUARIOS · Registro público (siempre rol "vecino")
 * ==================================================
 * Endpoint : /Backend/api/usuarios/registro.php
 * Método   : POST
 * Body     : { "nombre": string, "cedula": string, "email": string, "password": string }
 * Respuesta: { status, message, usuario? }  ->  201 Created / 400 Bad Request / 409 Conflict
 *
 * Este endpoint es el que usa la Interfaz de Usuarios (pública/opcional)
 * para que cualquier vecino se autoregistre. Para dar de alta usuarios
 * con otros roles se usa el CRUD de administración: usuarios.php.
 */

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
    }

    $datos = json_decode(file_get_contents('php://input'), true) ?? [];

    $controlador = new AuthController($pdo);
    Respuesta::enviarResultado($controlador->registro($datos), 201);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'registro.php');
}
