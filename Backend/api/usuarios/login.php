<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

require_once __DIR__ . '/../../core/Sesion.php';

try {
    Sesion::proteger($pdo, 'login');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
    }

    $datos = Sesion::datos();

    $controlador = new AuthController($pdo);
    Respuesta::enviarResultado($controlador->login($datos));
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'login.php');
}
