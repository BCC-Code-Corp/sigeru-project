<?php
/**
 * ==================================================
 *  API · Registro de usuario
 * ==================================================
 * Endpoint : /Backend/registro.php
 * Método   : POST
 * Body     : { "nombre": string, "cedula": string, "email": string, "password": string }
 * Respuesta: { status, message, usuario? }
 */

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/core/Respuesta.php';
require_once __DIR__ . '/controllers/AuthController.php';

$datos = json_decode(file_get_contents('php://input'), true) ?? [];

$controlador = new AuthController($pdo);
Respuesta::enviar($controlador->registro($datos));
