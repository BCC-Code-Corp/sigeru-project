<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$input = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM camiones ORDER BY matricula ASC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST') {
        if (empty($input['matricula'])) {
            echo json_encode(["status" => "error", "message" => "Matrícula obligatoria."]);
            exit;
        }
        $matricula = strtoupper(trim($input['matricula']));
        
        $stmt = $pdo->prepare("INSERT INTO camiones (matricula, estado) VALUES (?, 'Disponible')");
        $stmt->execute([$matricula]);
        
        echo json_encode(["status" => "success", "message" => "Camión registrado de forma persistente."]);
        exit;
    }
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de BDD: " . $e->getMessage()]);
}