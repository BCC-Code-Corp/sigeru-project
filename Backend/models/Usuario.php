<?php
/**
 * ==================================================
 *  MODELO: USUARIO
 * ==================================================
 * Representa la tabla `usuarios` y encapsula TODAS las consultas
 * relacionadas a usuarios (login, registro, roles, perfil).
 * Ningún Controlador escribe SQL directamente: siempre pasa por acá.
 */

class Usuario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Busca un usuario completo (incluye password hasheado) para login/roles. */
    public function buscarPorEmail(string $email)
    {
        $stmt = $this->pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /** Busca los datos de perfil (sin password) para mostrar en el panel. */
    public function buscarPerfilPorEmail(string $email)
    {
        $stmt = $this->pdo->prepare("SELECT nombre, email, cedula, rol FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /** Verifica si el email o la cédula ya están registrados. */
    public function existeEmailOCedula(string $email, string $cedula): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR cedula = ?");
        $stmt->execute([$email, $cedula]);
        return (bool) $stmt->fetch();
    }

    /** Inserta un nuevo usuario. La contraseña ya debe venir hasheada. */
    public function crear(string $nombre, string $email, string $cedula, string $passwordHash, string $rol = 'vecino'): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (nombre, email, cedula, password, rol) VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$nombre, $email, $cedula, $passwordHash, $rol]);
    }

    /** Actualiza el rol de un usuario existente. */
    public function actualizarRol(string $email, string $rol): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET rol = :rol WHERE email = :email");
        return $stmt->execute([':rol' => $rol, ':email' => $email]);
    }

    /**
     * Valida el dígito verificador de una Cédula de Identidad uruguaya
     * usando el algoritmo Módulo 10.
     */
    public static function validarCedulaUruguaya(string $cedula): bool
    {
        $numeros = preg_replace('/[^0-9]/', '', $cedula);
        $numeros = str_pad($numeros, 8, '0', STR_PAD_LEFT);

        if (strlen($numeros) !== 8) {
            return false;
        }

        $factores = [2, 9, 8, 7, 6, 3, 4];
        $suma = 0;
        for ($i = 0; $i < 7; $i++) {
            $suma += intval($numeros[$i]) * $factores[$i];
        }

        $resto = $suma % 10;
        $digitoVerificador = ($resto === 0) ? 0 : (10 - $resto);

        return $digitoVerificador === intval($numeros[7]);
    }
}
