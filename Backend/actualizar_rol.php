<?php
/**
 * ==================================================
 *  API · Actualizar rol de usuario (solo Administrador)
 * ==================================================
 * Endpoint : /Backend/actualizar_rol.php
 * Método   : POST
 * Body     : { "email_destino": string, "nuevo_rol": string }
 * Respuesta: { status, message }
 */

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/core/Respuesta.php';
require_once __DIR__ . '/controllers/UsuarioController.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

$datos = json_decode(file_get_contents('php://input'), true) ?? [];

$controlador = new UsuarioController($pdo);
Respuesta::enviar($controlador->actualizarRol($datos));
