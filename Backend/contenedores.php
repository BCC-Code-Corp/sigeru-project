<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];

// 1. OPERACIÓN DE LECTURA (GET) - Trae todos los contenedores de la BDD
if ($metodo === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM contenedores ORDER BY id DESC");
        $contenedores = $stmt->fetchAll();
        echo json_encode(["status" => "success", "data" => $contenedores]);
    } catch (\PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// 2. OPERACIÓN DE ESCRITURA (POST) - Inserta un contenedor nuevo evitando inyección SQL
if ($metodo === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['ubicacion']) || empty($input['estado'])) {
        echo json_encode(["status" => "error", "message" => "Todos los campos son requeridos."]);
        exit;
    }

    $ubicacion = trim($input['ubicacion']);
    $estado = trim($input['estado']); // 'lleno', 'vacio', 'mantenimiento'

    try {
        // Insertar usando Consulta Preparada
        $stmt = $pdo->prepare("INSERT INTO contenedores (ubicacion, estado) VALUES (?, ?)");
        $stmt->execute([$ubicacion, $estado]);

        echo json_encode(["status" => "success", "message" => "Contenedor registrado con éxito."]);
    } catch (\PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error en la base de datos: " . $e->getMessage()]);
    }
    exit;
}