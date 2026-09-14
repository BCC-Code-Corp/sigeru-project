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
require_once __DIR__ . '/../core/Validacion.php';

class UsuarioController
{
    private Usuario $usuarioModel;
    private array $rolesPermitidos = ['administrador', 'operario', 'chofer', 'recolector', 'vecino'];

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

        if (!Validacion::password($datos['password'])) {
            return ["status" => "error", "message" => Validacion::PASSWORD_MENSAJE, "_code" => 400];
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

        $this->validarDatosOperario($rol, $datos);

        $passwordHash = password_hash($datos['password'], PASSWORD_BCRYPT);
        $this->usuarioModel->crear(trim($datos['nombre']), $email, $cedula, $passwordHash, $rol);
        $this->guardarDatosOperario($this->usuarioModel->ultimoId(), $rol, $datos);
        if (isset($datos['estado_registro'])) $this->usuarioModel->estadoRegistro($this->usuarioModel->ultimoId(),$datos['estado_registro']);

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

        if ($email !== $usuario['email']) {
            $existente = $this->usuarioModel->buscarPorEmail($email);
            if ($existente && (int) $existente['id'] !== $id) {
                return ["status" => "error", "message" => "Ese correo electrónico ya está en uso por otro usuario.", "_code" => 409];
            }
        }

        if (!empty($datos['password']) && !Validacion::password($datos['password'])) {
            return ["status" => "error", "message" => Validacion::PASSWORD_MENSAJE, "_code" => 400];
        }

        if ($rol !== $usuario['rol']) $this->usuarioModel->validarCambioRol($id);
        $this->usuarioModel->actualizar($id, $nombre, $email, $rol);
        $this->guardarDatosOperario($id, $rol, $datos);
        if (isset($datos['estado_registro']) && $datos['estado_registro']!==$usuario['estado_registro']) $this->usuarioModel->estadoRegistro($id,$datos['estado_registro']);

        if (!empty($datos['password'])) {
            $this->usuarioModel->actualizarPassword($id, password_hash($datos['password'], PASSWORD_BCRYPT));
        }

        return ["status" => "success", "message" => "Usuario actualizado correctamente."];
    }

    private function guardarDatosOperario(int $id, string $rol, array $datos): void
    {
        if ($rol !== 'operario') {
            $this->usuarioModel->guardarDatosOperario($id, null, null);
            return;
        }

        $this->validarDatosOperario($rol, $datos);
        $this->usuarioModel->guardarDatosOperario($id, (int)$datos['centro_id'], trim((string)$datos['especialidad']));
    }

    private function validarDatosOperario(string $rol, array $datos): void
    {
        if ($rol !== 'operario') return;
        if (empty($datos['centro_id']) || !ctype_digit((string)$datos['centro_id']) || (int)$datos['centro_id'] <= 0) {
            throw new DomainException('El operario debe tener un centro asignado.');
        }
        if (!$this->usuarioModel->centroExiste((int)$datos['centro_id'])) {
            throw new DomainException('El centro asignado no existe o está inactivo.');
        }
        $especialidad = trim((string)($datos['especialidad'] ?? ''));
        if ($especialidad === '' || strlen($especialidad) > 100) throw new DomainException('La especialidad del operario es obligatoria.');
    }

    /** DELETE — elimina un usuario por id. */
    public function eliminar(int $id): array
    {
        $usuario = $this->usuarioModel->buscarPorId($id);
        if (!$usuario) {
            return ["status" => "error", "message" => "El usuario no existe.", "_code" => 404];
        }

        if ($id === (int)($GLOBALS['actor']['id'] ?? 0)) return ['status'=>'error','message'=>'No podés dar de baja tu propia cuenta.','_code'=>409];
        $this->usuarioModel->eliminar($id);

        return ["status" => "success", "message" => "Usuario eliminado correctamente."];
    }
}
