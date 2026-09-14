<?php
// Registro

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

require_once __DIR__ . '/../../core/Sesion.php';

try {
    Sesion::proteger($pdo, 'registro');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
    }

    $datos = Sesion::datos();

    $controlador = new AuthController($pdo);
    Respuesta::enviarResultado($controlador->registro($datos), 201);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'registro.php');
}
