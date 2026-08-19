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

$host    = '127.0.0.1';
$db      = 'sigeru_db';
$user    = 'root';
$pass    = ''; // Por defecto en XAMPP suele estar vacío
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

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
        "message" => "Error de conexión a la base de datos: " . $e->getMessage()
    ]);
    exit();
}
