<?php
/**
 * ==================================================
 *  CONTROLADOR: USUARIO (CRUD completo, todos los roles)
 * ==================================================
 * Usado por el backoffice de Administración para dar de alta,
 * listar, editar y eliminar usuarios de cualquier rol. También
 * resuelve la consulta de perfil que hace el panel al iniciar sesión.
 */

require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController
{
    private Usuario $usuarioModel;
    private array $rolesPermitidos = ['administrador', 'operario', 'cuadrilla', 'vecino'];

    public function __construct(PDO $pdo)
    {
        $this->usuarioModel = new Usuario($pdo);
    }

    /** READ (todos) — listado completo para el backoffice de Administración. */
    public function listar(): array
    {
        return ["status" => "success", "data" => $this->usuarioModel->listarTodos()];
    }

    /** READ (uno) — por id o por email, según lo que venga en el filtro. */
    public function obtener(array $filtro): array
    {
        if (!empty($filtro['id'])) {
            $usuario = $this->usuarioModel->buscarPorId((int) $filtro['id']);
        } elseif (!empty($filtro['email'])) {
            $usuario = $this->usuarioModel->buscarPerfilPorEmail(trim($filtro['email']));
        } else {
            return ["status" => "error", "message" => "Debe indicar id o email.", "_code" => 400];
        }

        if ($usuario) {
            return ["status" => "success", "usuario" => $usuario];
        }

        return ["status" => "error", "message" => "Usuario no encontrado.", "_code" => 404];
    }

    /** CREATE — el Administrador da de alta un usuario con el rol que corresponda. */
    public function crear(array $datos): array
    {
        if (empty($datos['nombre']) || empty($datos['email']) || empty($datos['cedula']) || empty($datos['password'])) {
            return ["status" => "error", "message" => "Nombre, cédula, email y contraseña son requeridos.", "_code" => 400];
        }

        if (!filter_var(trim($datos['email']), FILTER_VALIDATE_EMAIL)) {
            return ["status" => "error", "message" => "El correo electrónico no tiene un formato válido.", "_code" => 400];
        }

        if (strlen($datos['password']) < 6) {
            return ["status" => "error", "message" => "La contraseña debe tener al menos 6 caracteres.", "_code" => 400];
        }

        $cedula = preg_replace('/[^0-9]/', '', $datos['cedula']);
        if (!Usuario::validarCedulaUruguaya($cedula)) {
            return ["status" => "error", "message" => "La Cédula de Identidad ingresada no es válida.", "_code" => 400];
        }

        $email = trim($datos['email']);
        if ($this->usuarioModel->existeEmailOCedula($email, $cedula)) {
            return ["status" => "error", "message" => "El correo electrónico o la cédula ya se encuentran registrados.", "_code" => 409];
        }

        $rol = strtolower(trim($datos['rol'] ?? 'vecino'));
        if (!in_array($rol, $this->rolesPermitidos, true)) {
            return ["status" => "error", "message" => "El rol '$rol' no es válido.", "_code" => 400];
        }

        $passwordHash = password_hash($datos['password'], PASSWORD_BCRYPT);
        $this->usuarioModel->crear(trim($datos['nombre']), $email, $cedula, $passwordHash, $rol);

        return ["status" => "success", "message" => "Usuario creado con éxito.", "_code" => 201];
    }

    /** UPDATE — nombre, email y rol. La contraseña solo se cambia si viene informada. */
    public function actualizar(int $id, array $datos): array
    {
        $usuario = $this->usuarioModel->buscarPorId($id);
        if (!$usuario) {
            return ["status" => "error", "message" => "El usuario no existe.", "_code" => 404];
        }

        $nombre = trim($datos['nombre'] ?? $usuario['nombre']);
        $email  = trim($datos['email'] ?? $usuario['email']);
        $rol    = strtolower(trim($datos['rol'] ?? $usuario['rol']));

        if ($nombre === '' || $email === '') {
            return ["status" => "error", "message" => "Nombre y email no pueden quedar vacíos.", "_code" => 400];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["status" => "error", "message" => "El correo electrónico no tiene un formato válido.", "_code" => 400];
        }

        if ($rol === 'admin') {
            $rol = 'administrador';
        }

        if (!in_array($rol, $this->rolesPermitidos, true)) {
            return ["status" => "error", "message" => "El rol '$rol' no es válido.", "_code" => 400];
        }

        // Si el email cambió, verificamos que no choque con el de otro usuario.
        if ($email !== $usuario['email']) {
            $existente = $this->usuarioModel->buscarPorEmail($email);
            if ($existente && (int) $existente['id'] !== $id) {
                return ["status" => "error", "message" => "Ese correo electrónico ya está en uso por otro usuario.", "_code" => 409];
            }
        }

        if (!empty($datos['password']) && strlen($datos['password']) < 6) {
            return ["status" => "error", "message" => "La contraseña debe tener al menos 6 caracteres.", "_code" => 400];
        }

        $this->usuarioModel->actualizar($id, $nombre, $email, $rol);

        if (!empty($datos['password'])) {
            $this->usuarioModel->actualizarPassword($id, password_hash($datos['password'], PASSWORD_BCRYPT));
        }

        return ["status" => "success", "message" => "Usuario actualizado correctamente."];
    }

    /** DELETE — elimina un usuario por id. */
    public function eliminar(int $id): array
    {
        $usuario = $this->usuarioModel->buscarPorId($id);
        if (!$usuario) {
            return ["status" => "error", "message" => "El usuario no existe.", "_code" => 404];
        }

        $this->usuarioModel->eliminar($id);

        return ["status" => "success", "message" => "Usuario eliminado correctamente."];
    }
}
