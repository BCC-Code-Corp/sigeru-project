<?php
/**
 * ==================================================
 *  API USUARIOS · Autenticación (Login)
 * ==================================================
 * Endpoint : /Backend/api/usuarios/login.php
 * Método   : POST
 * Body     : { "email": string, "password": string }
 * Respuesta: { status, message, usuario? }  ->  200 OK / 400 Bad Request / 401 Unauthorized
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
    Respuesta::enviarResultado($controlador->login($datos));
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'login.php');
}
