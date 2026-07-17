<?php
// Configuración de la base de datos
$host = '127.0.0.1';
$db   = 'sigeru_db';
$user = 'root';
$pass = ''; // Por defecto en XAMPP suele estar vacío
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Enviar error JSON en caso de falla de conexión
     header('Content-Type: application/json');
     echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos: " . $e->getMessage()]);
     exit;
}