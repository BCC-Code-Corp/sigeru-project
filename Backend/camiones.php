<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];

// 1. OPERACIÓN DE LECTURA (GET) - Trae todos los camiones de la BDD
if ($metodo === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM camiones ORDER BY id DESC");
        $camiones = $stmt->fetchAll();
        echo json_encode(["status" => "success", "data" => $camiones]);
    } catch (\PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// 2. OPERACIÓN DE ESCRITURA (POST) - Inserta un camión nuevo evitando inyección SQL
if ($metodo === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['matricula']) || empty($input['capacidad_carga'])) {
        echo json_encode(["status" => "error", "message" => "Todos los campos son requeridos."]);
        exit;
    }

    $matricula = trim($input['matricula']);
    $capacidad = floatval($input['capacidad_carga']);

    try {
        // Verificar si la matrícula ya existe
        $stmt = $pdo->prepare("SELECT id FROM camiones WHERE matricula = ?");
        $stmt->execute([$matricula]);
        if ($stmt->fetch()) {
            echo json_encode(["status" => "error", "message" => "Esta matrícula ya está registrada."]);
            exit;
        }

        // Insertar usando Consulta Preparada (Seguridad contra inyección SQL)
        $stmt = $pdo->prepare("INSERT INTO camiones (matricula, capacidad_carga) VALUES (?, ?)");
        $stmt->execute([$matricula, $capacidad]);

        echo json_encode(["status" => "success", "message" => "Camión registrado con éxito."]);
    } catch (\PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error en la base de datos: " . $e->getMessage()]);
    }
    exit;
}