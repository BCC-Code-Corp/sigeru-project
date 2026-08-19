<?php
/**
 * ==================================================
 *  API · Gestión de Contenedores
 * ==================================================
 * Endpoint : /Backend/contenedores.php
 * Métodos  : GET (listar todos) | POST (registrar uno nuevo)
 * Body POST: { "ubicacion": string, "estado": "lleno"|"vacio"|"mantenimiento" }
 * Respuesta: { status, message?, data? }
 */

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/core/Respuesta.php';
require_once __DIR__ . '/controllers/ContenedorController.php';

$controlador = new ContenedorController($pdo);
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    Respuesta::enviar($controlador->listar());
}

if ($metodo === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    Respuesta::enviar($controlador->crear($datos));
}

Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
