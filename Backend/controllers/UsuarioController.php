<?php
/**
 * ==================================================
 *  CONTROLADOR: USUARIO (Perfil / Roles)
 * ==================================================
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

    /** Devuelve nombre, email, cédula y rol de un usuario (para el panel). */
    public function obtenerPerfil(array $datos): array
    {
        if (empty($datos['email'])) {
            return ["status" => "error", "message" => "Email no proporcionado."];
        }

        $usuario = $this->usuarioModel->buscarPerfilPorEmail(trim($datos['email']));

        if ($usuario) {
            return ["status" => "success", "usuario" => $usuario];
        }

        return ["status" => "error", "message" => "Usuario no encontrado."];
    }

    /** Cambia el rol de un usuario. Solo debería invocarse desde el panel de Administrador. */
    public function actualizarRol(array $datos): array
    {
        if (empty($datos['email_destino']) || empty($datos['nuevo_rol'])) {
            return ["status" => "error", "message" => "Faltan datos requeridos en la solicitud."];
        }

        $email     = trim($datos['email_destino']);
        $nuevoRol  = strtolower(trim($datos['nuevo_rol']));

        // Permite que el Frontend mande 'admin' como atajo de 'administrador'.
        if ($nuevoRol === 'admin') {
            $nuevoRol = 'administrador';
        }

        if (!in_array($nuevoRol, $this->rolesPermitidos, true)) {
            return ["status" => "error", "message" => "El rol '$nuevoRol' no es válido."];
        }

        $usuario = $this->usuarioModel->buscarPorEmail($email);
        if (!$usuario) {
            return ["status" => "error", "message" => "El usuario $email no existe en la base de datos."];
        }

        $exito = $this->usuarioModel->actualizarRol($email, $nuevoRol);

        if ($exito) {
            return ["status" => "success", "message" => "El rol de $email se actualizó a '$nuevoRol' correctamente."];
        }

        return ["status" => "error", "message" => "No se pudo realizar el UPDATE en la base de datos."];
    }
}
