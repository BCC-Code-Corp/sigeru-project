<?php
/**
 * ==================================================
 *  API RECOLECCIÓN · Incidencias
 * ==================================================
 * Endpoint : /Backend/api/recoleccion/incidencias.php
 * GET    ?usuario_id=X            -> incidencias reportadas por ese usuario (200 / 400)
 * GET    (sin parámetros)         -> lista todas las incidencias (200)
 * POST   body: { ubicacion, estado_contenedor, tipo_basura, usuario_id,
 *                latitud?, longitud? }                      -> crea una (201 / 400)
 * PUT    ?id=X&accion=asignar     body: { cuadrilla_id?, matricula_camion,
 *                                          comentario_operario? }  -> asigna logística (200 / 400 / 404)
 * PUT    ?id=X&accion=resolver                                -> marca resuelta (200 / 404)
 * Respuesta: { status, message?, data? }
 *
 * No se expone DELETE: una incidencia no se elimina, avanza de estado
 * a lo largo del flujo de recolección (reportada -> en curso ->
 * resuelta); por eso esos cambios de estado usan PUT sobre el mismo
 * recurso identificado por "id", con "accion" solo para distinguir
 * cuál transición de estado se está aplicando.
 */

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/IncidenciaController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $controlador = new IncidenciaController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['usuario_id'])) {
            Respuesta::enviarResultado($controlador->listarPorUsuario($_GET));
        }
        Respuesta::enviarResultado($controlador->listar());
    }

    if ($metodo === 'POST') {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        Respuesta::enviarResultado($controlador->crear($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id de la incidencia."], 400);
        }
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $datos['id'] = $_GET['id'];
        $accion = $_GET['accion'] ?? '';

        if ($accion === 'asignar') {
            Respuesta::enviarResultado($controlador->asignar($datos));
        } elseif ($accion === 'resolver') {
            Respuesta::enviarResultado($controlador->resolver($datos));
        } else {
            Respuesta::enviar(["status" => "error", "message" => "Falta indicar ?accion=asignar o ?accion=resolver."], 400);
        }
    }

    Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'incidencias.php');
}
