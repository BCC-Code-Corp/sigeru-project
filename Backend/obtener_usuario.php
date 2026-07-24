<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['email'])) {
    echo json_encode(["status" => "error", "message" => "Email no proporcionado."]);
    exit;
}

try {
    // Consulta directa a MySQL para obtener la cédula real
    $stmt = $pdo->prepare("SELECT nombre, email, cedula, rol FROM usuarios WHERE email = ?");
    $stmt->execute([$input['email']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        echo json_encode([
            "status" => "success",
            "usuario" => $usuario
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Usuario no encontrado."]);
    }
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>