<?php
/**
 * ==================================================
 *  API GESTIÓN · Centros de Acopio y Maquinaria (CRUD)
 * ==================================================
 * Endpoint : /Backend/api/gestion/centros_acopio.php
 * GET    ?id=1          -> obtiene uno (200 / 404) | sin parámetro -> lista todos (200)
 * POST   body: { nombre, ubicacion, maquinaria?, estado? }  -> crea uno (201 / 400)
 * PUT    ?id=1  body: { nombre, ubicacion, maquinaria?, estado? } -> actualiza (200 / 400 / 404)
 * DELETE ?id=1                                                -> elimina (200 / 404)
 * Respuesta: { status, message?, data? }
 */

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/CentroAcopioController.php';

try {
    $controlador = new CentroAcopioController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['id'])) {
            Respuesta::enviarResultado($controlador->obtener((int) $_GET['id']));
        }
        Respuesta::enviarResultado($controlador->listar());
    }

    if ($metodo === 'POST') {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        Respuesta::enviarResultado($controlador->crear($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id del centro de acopio."], 400);
        }
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        Respuesta::enviarResultado($controlador->actualizar((int) $_GET['id'], $datos));
    }

    if ($metodo === 'DELETE') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id del centro de acopio."], 400);
        }
        Respuesta::enviarResultado($controlador->eliminar((int) $_GET['id']));
    }

    Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'centros_acopio.php');
}
