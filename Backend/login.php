<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['email']) || empty($input['password'])) {
    echo json_encode(["status" => "error", "message" => "Por favor, complete todos los campos."]);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

try {
    // Buscar usuario en la base de datos
    $stmt = $pdo->prepare("SELECT nombre, email, password FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Validar existencia y verificar password hash
    if ($usuario && password_verify($password, $usuario['password'])) {
        echo json_encode([
            "status" => "success",
            "message" => "Sesión iniciada con éxito.",
            "usuario" => [
                "nombre" => $usuario['nombre'],
                "email" => $usuario['email']
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Credenciales incorrectas o usuario no registrado."]);
    }
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de servidor: " . $e->getMessage()]);
}