<?php
header('Content-Type: application/json');
require_once 'conexion.php';

// Obtener datos recibidos mediante JSON POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['nombre']) || empty($input['email']) || empty($input['password'])) {
    echo json_encode(["status" => "error", "message" => "Todos los campos son requeridos."]);
    exit;
}

$nombre = trim($input['nombre']);
$email = trim($input['email']);
$password = $input['password'];

try {
    // 1. Validar que el correo no esté duplicado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Este correo electrónico ya está registrado."]);
        exit;
    }

    // 2. Insertar nuevo usuario. Encriptamos la contraseña con BCRYPT por seguridad.
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'operador')");
    $stmt->execute([$nombre, $email, $passwordHash]);

    echo json_encode([
        "status" => "success", 
        "message" => "Usuario registrado con éxito.",
        "usuario" => ["nombre" => $nombre, "email" => $email]
    ]);
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error en la base de datos: " . $e->getMessage()]);
}