<?php
header('Content-Type: application/json');
require_once 'conexion.php';

/**
 * Función que valida la legitimidad de la Cédula de Identidad de Uruguay (Módulo 10).
 * Multiplica los 7 primeros dígitos por los factores [2, 9, 8, 7, 6, 3, 4]
 * y comprueba si el resto coincide con el dígito verificador.
 */
function validarCedulaUruguaya($cedula) {
    $numbers = preg_replace('/[^0-9]/', '', $cedula);
    $numbers = str_pad($numbers, 8, '0', STR_PAD_LEFT);
    
    if (strlen($numbers) !== 8) return false;

    $factors = [2, 9, 8, 7, 6, 3, 4];
    $sum = 0;
    for ($i = 0; $i < 7; $i++) {
        $sum += intval($numbers[$i]) * $factors[$i];
    }

    $remainder = $sum % 10;
    $checkDigit = ($remainder === 0) ? 0 : (10 - $remainder);

    return $checkDigit === intval($numbers[7]);
}

// Recibir datos POST en formato JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['nombre']) || empty($input['email']) || empty($input['cedula']) || empty($input['password'])) {
    echo json_encode(["status" => "error", "message" => "Todos los campos (nombre, cédula, email y contraseña) son requeridos."]);
    exit;
}

$nombre = trim($input['nombre']);
$email = trim($input['email']);
$cedula = preg_replace('/[^0-9]/', '', $input['cedula']);
$password = $input['password'];

// 1. Verificar si la cédula es numéricamente válida según el algoritmo de Uruguay
if (!validarCedulaUruguaya($cedula)) {
    echo json_encode(["status" => "error", "message" => "La Cédula de Identidad ingresada no es válida o es falsa."]);
    exit;
}

try {
    // 2. Verificar si el email o la cédula ya existen en MySQL
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR cedula = ?");
    $stmt->execute([$email, $cedula]);
    if ($stmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "El correo electrónico o la cédula ya se encuentran registrados."]);
        exit;
    }

    // 3. Encriptar contraseña e insertar el nuevo registro
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, cedula, password, rol) VALUES (?, ?, ?, ?, 'vecino')");
    $stmt->execute([$nombre, $email, $cedula, $passwordHash]);

    // 4. Retornar los datos del usuario recién registrado para iniciar sesión en localStorage
    echo json_encode([
        "status" => "success", 
        "message" => "Usuario registrado con éxito.",
        "usuario" => [
            "nombre" => $nombre, 
            "email" => $email, 
            "cedula" => $cedula,
            "rol" => "vecino"
        ]
    ]);
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error en la base de datos: " . $e->getMessage()]);
}
?>