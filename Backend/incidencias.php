<?php
/**
 * ==================================================
 *  API · Gestión de Incidencias
 * ==================================================
 * Endpoint : /Backend/incidencias.php
 * Método   : POST
 * Body     : { "accion": "crear"|"listar"|"asignar"|"resolver", ...datos }
 * Respuesta: { status, message?, data? }
 *
 * Se usa "accion" en vez de GET/POST porque una misma incidencia
 * pasa por varios pasos (vecino crea, operario asigna, cuadrilla
 * resuelve) y todos esos pasos son escrituras sobre la misma tabla.
 */

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/core/Respuesta.php';
require_once __DIR__ . '/controllers/IncidenciaController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

$datos  = json_decode(file_get_contents('php://input'), true) ?? [];
$accion = $datos['accion'] ?? ($_POST['accion'] ?? '');

$controlador = new IncidenciaController($pdo);

switch ($accion) {
    case 'crear':
        Respuesta::enviar($controlador->crear($datos));
        break;
    case 'listar':
        Respuesta::enviar($controlador->listar());
        break;
    case 'asignar':
        Respuesta::enviar($controlador->asignar($datos));
        break;
    case 'resolver':
        Respuesta::enviar($controlador->resolver($datos));
        break;
    default:
        Respuesta::enviar(["status" => "error", "message" => "Acción no válida."], 400);
}
