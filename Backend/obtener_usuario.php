<?php
/**
 * ==================================================
 *  API · Perfil de usuario
 * ==================================================
 * Endpoint : /Backend/obtener_usuario.php
 * Método   : POST
 * Body     : { "email": string }
 * Respuesta: { status, message?, usuario? }
 */

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/core/Respuesta.php';
require_once __DIR__ . '/controllers/UsuarioController.php';

$datos = json_decode(file_get_contents('php://input'), true) ?? [];

$controlador = new UsuarioController($pdo);
Respuesta::enviar($controlador->obtenerPerfil($datos));
