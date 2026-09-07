<?php
/**
 * ==================================================
 *  API USUARIOS · Notificaciones
 * ==================================================
 * Endpoint : /Backend/api/notificaciones/notificaciones.php
 * GET    ?usuario_id=X   -> notificaciones privadas + anuncios de ese usuario (200 / 400)
 * GET    ?anuncios=1     -> historial completo de anuncios publicados (200)
 * POST   body: { mensaje }   -> el Administrador publica un anuncio público (201 / 400)
 * PUT    ?id=X               -> marca una notificación como leída (200 / 400 / 404)
 * Respuesta: { status, message?, data? }
 *
 * No se expone DELETE: las notificaciones no se eliminan, solo
 * cambian de estado (leída/no leída), de ahí que ese cambio use PUT.
 */

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../core/Respuesta.php';
require_once __DIR__ . '/../../controllers/NotificacionController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $controlador = new NotificacionController($pdo);
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        if (!empty($_GET['anuncios'])) {
            Respuesta::enviar($controlador->listarAnuncios());
        }
        Respuesta::enviarResultado($controlador->listar($_GET));
    }

    if ($metodo === 'POST') {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        Respuesta::enviarResultado($controlador->crearAnuncio($datos), 201);
    }

    if ($metodo === 'PUT') {
        if (empty($_GET['id'])) {
            Respuesta::enviar(["status" => "error", "message" => "Falta el id de la notificación."], 400);
        }
        Respuesta::enviarResultado($controlador->marcarLeida($_GET));
    }

    Respuesta::enviar(["status" => "error", "message" => "Método no soportado."], 405);
} catch (\Throwable $e) {
    Respuesta::enviarErrorInterno($e, 'notificaciones.php');
}
