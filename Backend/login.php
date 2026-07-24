<?php
header('Content-Type: application/json');
// CORRECCIÓN: Subimos dos niveles para encontrar el archivo de conexión
require_once 'conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['email']) || empty($input['password'])) {
    echo json_encode(["status" => "error", "message" => "Por favor, complete todos los campos."]);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

try {
    // MODIFICACIÓN MÍNIMA: Agregamos "rol" e "id" a la consulta de selección[cite: 3]
    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Validar existencia y verificar password hash
    if ($usuario && password_verify($password, $usuario['password'])) {
        echo json_encode([
            "status" => "success",
            "message" => "Sesión iniciada con éxito.",
            "usuario" => [
                "id" => $usuario['id'], // Necesario para validar acciones de admin[cite: 3]
                "nombre" => $usuario['nombre'],
                "email" => $usuario['email'],
                "rol" => $usuario['rol'] // MODIFICACIÓN MÍNIMA: Enviamos el rol al cliente[cite: 3]
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Credenciales incorrectas o usuario no registrado."]);
    }
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de servidor: " . $e->getMessage()]);
}