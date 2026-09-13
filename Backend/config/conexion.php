<?php
/**
 * ==================================================
 *  CONFIGURACIÓN Y CONEXIÓN A LA BASE DE DATOS
 * ==================================================
 * Único lugar del sistema donde se configuran las credenciales
 * de acceso a MySQL. Todos los Modelos reciben la conexión ($pdo)
 * generada aquí a través de los Controladores.
 *
 * Para cambiar servidor, usuario o contraseña, modificar solo
 * este archivo.
 */

$host    = getenv('SIGERU_DB_HOST') ?: '127.0.0.1';
$port = getenv('SIGERU_DB_PORT') ?: '3306';
$db      = getenv('SIGERU_DB_NAME') ?: 'sigeru_db';
$user    = getenv('SIGERU_DB_USER') ?: 'root';
$pass    = getenv('SIGERU_DB_PASSWORD') ?: ''; // Por defecto en XAMPP suele estar vacío
$charset = 'utf8mb4';

// ==================================================
//  MODO DEBUG
// ==================================================
// true  -> los errores del servidor (try/catch de cada endpoint) devuelven
//          el mensaje real de PHP/MySQL en el JSON, para poder diagnosticar
//          rápido en desarrollo (¿columna que falta? ¿tabla que no existe?
//          ¿tipo de dato incorrecto?).
// false -> se devuelve un mensaje genérico ("Error interno del servidor"),
//          sin exponer detalles internos. USAR ASÍ PARA LA ENTREGA FINAL.
if (!defined('SIGERU_DEBUG')) {
    define('SIGERU_DEBUG', false);
}

ini_set('display_errors', SIGERU_DEBUG ? '1' : '0');

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Consultas preparadas reales -> previene inyección SQL
];

try {
    $pdo = new PDO($dsn, $user, $pass, $opciones);
} catch (\PDOException $e) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => SIGERU_DEBUG
            ? "Error de conexión a la base de datos: " . $e->getMessage()
            : "Error interno del servidor."
    ]);
    exit();
}
