<?php
/**
 * ==================================================
 *  CONTROLADOR: AUTENTICACIÓN (Login / Registro)
 * ==================================================
 * Recibe datos ya decodificados desde el archivo de API,
 * valida las reglas de negocio, habla con el Modelo Usuario
 * y devuelve un array listo para convertirse en JSON.
 */

require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    private Usuario $usuarioModel;

    public function __construct(PDO $pdo)
    {
        $this->usuarioModel = new Usuario($pdo);
    }

    /** Valida credenciales y devuelve los datos públicos del usuario si son correctas. */
    public function login(array $datos): array
    {
        if (empty($datos['email']) || empty($datos['password'])) {
            return ["status" => "error", "message" => "Por favor, complete todos los campos.", "_code" => 400];
        }

        $email    = trim($datos['email']);
        $password = $datos['password'];

        $usuario = $this->usuarioModel->buscarPorEmail($email);

        if ($usuario && password_verify($password, $usuario['password'])) {
            return [
                "status"  => "success",
                "message" => "Sesión iniciada con éxito.",
                "usuario" => [
                    "id"     => $usuario['id'],
                    "nombre" => $usuario['nombre'],
                    "email"  => $usuario['email'],
                    "rol"    => $usuario['rol'],
                ],
            ];
        }

        return ["status" => "error", "message" => "Credenciales incorrectas o usuario no registrado.", "_code" => 401];
    }

    /** Valida cédula uruguaya, unicidad de email/cédula, hashea password y registra. */
    public function registro(array $datos): array
    {
        if (empty($datos['nombre']) || empty($datos['email']) || empty($datos['cedula']) || empty($datos['password'])) {
            return ["status" => "error", "message" => "Todos los campos (nombre, cédula, email y contraseña) son requeridos.", "_code" => 400];
        }

        $nombre   = trim($datos['nombre']);
        $email    = trim($datos['email']);
        $cedula   = preg_replace('/[^0-9]/', '', $datos['cedula']);
        $password = $datos['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["status" => "error", "message" => "El correo electrónico no tiene un formato válido.", "_code" => 400];
        }

        if (strlen($password) < 6) {
            return ["status" => "error", "message" => "La contraseña debe tener al menos 6 caracteres.", "_code" => 400];
        }

        if (!Usuario::validarCedulaUruguaya($cedula)) {
            return ["status" => "error", "message" => "La Cédula de Identidad ingresada no es válida o es falsa.", "_code" => 400];
        }

        if ($this->usuarioModel->existeEmailOCedula($email, $cedula)) {
            return ["status" => "error", "message" => "El correo electrónico o la cédula ya se encuentran registrados.", "_code" => 409];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $this->usuarioModel->crear($nombre, $email, $cedula, $passwordHash, 'vecino');

        return [
            "status"  => "success",
            "message" => "Usuario registrado con éxito.",
            "usuario" => [
                "nombre" => $nombre,
                "email"  => $email,
                "cedula" => $cedula,
                "rol"    => "vecino",
            ],
            "_code" => 201,
        ];
    }
}
