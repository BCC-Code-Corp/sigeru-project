<?php
/**
 * ==================================================
 *  API · Autenticación (Login)
 * ==================================================
 * Endpoint : /Backend/login.php
 * Método   : POST
 * Body     : { "email": string, "password": string }
 * Respuesta: { status, message, usuario? }
 */

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/core/Respuesta.php';
require_once __DIR__ . '/controllers/AuthController.php';

$datos = json_decode(file_get_contents('php://input'), true) ?? [];

$controlador = new AuthController($pdo);
Respuesta::enviar($controlador->login($datos));
