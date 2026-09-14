<?php
// Configuración

$host    = getenv('SIGERU_DB_HOST') ?: '127.0.0.1';
$port = getenv('SIGERU_DB_PORT') ?: '3306';
$db      = getenv('SIGERU_DB_NAME') ?: 'sigeru_db';
$user    = getenv('SIGERU_DB_USER') ?: 'root';
$pass    = getenv('SIGERU_DB_PASSWORD') ?: '';
$charset = 'utf8mb4';

// Depuración
if (!defined('SIGERU_DEBUG')) {
    define('SIGERU_DEBUG', false);
}

ini_set('display_errors', SIGERU_DEBUG ? '1' : '0');

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
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
