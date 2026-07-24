<?php
// Permitir solicitudes e indicar que el contenido devuelto es JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Incluir la conexión PDO a la Base de Datos
require_once "conexion.php"; 

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || empty($data['email_destino']) || empty($data['nuevo_rol'])) {
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos en la solicitud."]);
    exit();
}

$email = trim($data['email_destino']);
$nuevo_rol = strtolower(trim($data['nuevo_rol']));

// Asegurar que si viene 'admin', se convierta a 'administrador' (que es el valor real del ENUM)
if ($nuevo_rol === 'admin') {
    $nuevo_rol = 'administrador';
}

// Validar que el rol exista en el ENUM de la tabla usuarios
$roles_permitidos = ['administrador', 'operario', 'cuadrilla', 'vecino'];
if (!in_array($nuevo_rol, $roles_permitidos)) {
    echo json_encode(["status" => "error", "message" => "El rol '" . $nuevo_rol . "' no es válido."]);
    exit();
}

if (!isset($pdo)) {
    echo json_encode(["status" => "error", "message" => "Error de conexión con la base de datos."]);
    exit();
}

try {
    // 1. Verificar si el usuario existe en la BDD
    $stmtCheck = $pdo->prepare("SELECT id, rol FROM usuarios WHERE email = :email");
    $stmtCheck->execute([':email' => $email]);
    $usuario = $stmtCheck->fetch();

    if (!$usuario) {
        echo json_encode(["status" => "error", "message" => "El usuario " . $email . " no existe en la base de datos."]);
        exit();
    }

    // 2. Ejecutar la actualización a MySQL
    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET rol = :rol WHERE email = :email");
    $exito = $stmtUpdate->execute([
        ':rol'   => $nuevo_rol,
        ':email' => $email
    ]);

    if ($exito) {
        echo json_encode([
            "status" => "success", 
            "message" => "El rol de " . $email . " se actualizó a '" . $nuevo_rol . "' correctamente."
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se pudo realizar el UPDATE en la base de datos."]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error en la base de datos: " . $e->getMessage()]);
}
?>