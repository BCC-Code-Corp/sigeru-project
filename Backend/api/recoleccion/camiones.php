<?php
/**
 * ==================================================
 *  API RECOLECCIÓN · Camiones (CRUD de la flota)
 * ==================================================
 * Endpoint : /Backend/api/recoleccion/camiones.php
 * GET    ?matricula=XXX   -> obtiene un camión (200 / 404) | sin parámetro -> lista todos (200)
 * POST   body: { matricula, capacidad_carga? }               -> crea uno (201 / 400 / 409)
 * PUT    ?matricula=XXX   body: { capacidad_carga?, estado }  -> actualiza (200 / 400 / 404)
 * PUT    ?matricula=XXX&accion=asignar_cuadrilla    body: { cuadrilla_id } -> asigna/reasigna (200 / 400 / 404 / 409)
 * PUT    ?matricula=XXX&accion=desasignar_cuadrilla                       -> desasigna       (200 / 400 / 404 / 409)
 * DELETE ?matricula=XXX                                        -> elimina (200 / 404)
 * Respuesta: { status, message?, data? }
 */

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/CamionController.php';

try {
    $controlador = new CamionController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['matricula'])) {
            Respuesta::enviarResultado($controlador->obtener($_GET['matricula']));
        }
        Respuesta::enviarResultado($controlador->listar());
    }

    if ($metodo === 'POST') {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        Respuesta::enviarResultado($controlador->crear($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['matricula'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta la matrícula del camión."], 400);
        }
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $accion = $_GET['accion'] ?? '';

        if ($accion === 'asignar_cuadrilla') {
            Respuesta::enviarResultado($controlador->asignarCuadrilla($_GET['matricula'], $datos));
        } elseif ($accion === 'desasignar_cuadrilla') {
            Respuesta::enviarResultado($controlador->desasignarCuadrilla($_GET['matricula']));
        } else {
            Respuesta::enviarResultado($controlador->actualizar($_GET['matricula'], $datos));
        }
    }

    if ($metodo === 'DELETE') {
        if (empty($_GET['matricula'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta la matrícula del camión."], 400);
        }
        Respuesta::enviarResultado($controlador->eliminar($_GET['matricula']));
    }

    Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'camiones.php');
}
