<?php
/**
 * ==================================================
 *  MODELO: USUARIO
 * ==================================================
 * Representa la tabla `usuarios` y encapsula TODAS las consultas
 * relacionadas a usuarios (login, registro, perfil y CRUD completo
 * para el backoffice de Administración). Ningún Controlador escribe
 * SQL directamente: siempre pasa por acá.
 */

class Usuario
{
    private PDO $pdo;
    private int $idCreado=0;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Busca un usuario completo (incluye password hasheado) para login. */
    public function buscarPorEmail(string $email)
    {
        $stmt = $this->pdo->prepare("SELECT id, nombre, email, password, rol, auth_version, estado_registro FROM usuarios WHERE email = ? AND activo=1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /** Busca los datos de perfil (sin password) por email. */
    public function buscarPerfilPorEmail(string $email)
    {
        $stmt = $this->pdo->prepare("SELECT id, nombre, email, cedula, rol, estado_registro FROM usuarios WHERE email = ? AND activo=1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /** Busca los datos de perfil (sin password) por id. Usado por el CRUD de Administración. */
    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT id, nombre, email, cedula, rol, estado_registro FROM usuarios WHERE id = ? AND activo=1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Devuelve todos los usuarios, de cualquier rol, para el backoffice. */
    public function listarTodos(): array
    {
        $stmt = $this->pdo->query("SELECT u.id, u.nombre, u.email, u.cedula, u.rol, u.estado_registro, o.centro_id, o.especialidad FROM usuarios u LEFT JOIN operarios o ON o.id=u.id WHERE u.activo=1 ORDER BY u.nombre ASC");
        return $stmt->fetchAll();
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
        $stmt->execute([$nombre, $email, $cedula, $passwordHash, $rol]);
        $this->idCreado=(int)$this->pdo->lastInsertId();
        $this->sincronizarPerfil($this->idCreado, $rol);
        return true;
    }

    /** Actualiza nombre, email y rol de un usuario existente (la cédula no se reasigna). */
    public function actualizar(int $id, string $nombre, string $email, string $rol): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ? AND activo=1");
        $stmt->execute([$nombre, $email, $rol, $id]);
        $this->sincronizarPerfil($id, $rol);
        return true;
    }

    public function guardarDatosOperario(int $id, ?int $centroId, ?string $especialidad): void
    {
        $stmt = $this->pdo->prepare('UPDATE operarios SET centro_id=?, especialidad=? WHERE id=?');
        $stmt->execute([$centroId, $especialidad, $id]);
    }

    public function centroExiste(int $centroId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM centros_acopio WHERE id=? AND activo=1');
        $stmt->execute([$centroId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Cambia la contraseña de un usuario (uso opcional desde el CRUD de Administración). */
    public function actualizarPassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET password = ?, auth_version=auth_version+1 WHERE id = ? AND activo=1");
        return $stmt->execute([$passwordHash, $id]);
    }

    /** Elimina un usuario por id. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET activo=0 WHERE id = ? AND activo=1");
        return $stmt->execute([$id]);
    }

    public function estadoRegistro(int $id,string $estado): void
    {
        if (!in_array($estado,['pendiente','aprobado','rechazado'],true)) throw new DomainException('Estado de registro inválido.');
        $this->pdo->prepare('UPDATE usuarios SET estado_registro=?,auth_version=auth_version+1 WHERE id=?')->execute([$estado,$id]);
    }
    public function ultimoId(): int { return $this->idCreado; }

    public function verificarIntentos(string $email): void
    {
        $q=$this->pdo->prepare('SELECT bloqueado_hasta FROM intentos_login WHERE email_hash=? AND bloqueado_hasta>CURRENT_TIMESTAMP');
        $q->execute([hash('sha256',strtolower($email))]);
        if ($q->fetch()) throw new DomainException('Demasiados intentos. Esperá 15 minutos.',429);
    }
    public function registrarIntento(string $email,bool $correcto): void
    {
        $hash=hash('sha256',strtolower($email));
        if ($correcto) { $this->pdo->prepare('DELETE FROM intentos_login WHERE email_hash=?')->execute([$hash]); return; }
        $this->pdo->prepare('INSERT INTO intentos_login(email_hash,intentos) VALUES (?,1) ON DUPLICATE KEY UPDATE intentos=IF(actualizado<DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 15 MINUTE),1,intentos+1)')->execute([$hash]);
        $this->pdo->prepare('UPDATE intentos_login SET bloqueado_hasta=DATE_ADD(CURRENT_TIMESTAMP,INTERVAL 15 MINUTE) WHERE email_hash=? AND intentos>=5')->execute([$hash]);
    }
    public function validarCambioRol(int $id): void
    {
        $q=$this->pdo->prepare("SELECT 1 FROM camiones WHERE chofer_id=? AND activo=1 UNION ALL SELECT 1 FROM recolectores r JOIN incidencias i ON i.cuadrilla_id=r.cuadrilla_id WHERE r.id=? AND i.estado_incidencia='en curso'");
        $q->execute([$id,$id]);
        if ($q->fetch()) throw new DomainException('Desasigná las tareas activas antes de cambiar el rol.',409);
        $this->pdo->prepare('UPDATE usuarios SET auth_version=auth_version+1 WHERE id=?')->execute([$id]);
        $this->pdo->prepare('UPDATE choferes SET cuadrilla_id=NULL WHERE id=?')->execute([$id]);
        $this->pdo->prepare('UPDATE recolectores SET cuadrilla_id=NULL WHERE id=?')->execute([$id]);
        $this->pdo->prepare('UPDATE operarios SET centro_id=NULL WHERE id=?')->execute([$id]);
    }

    public function sincronizarPerfil(int $id, string $rol): void
    {
        if ($rol !== 'vecino') {
            $this->pdo->prepare('INSERT IGNORE INTO funcionarios(id) VALUES (?)')->execute([$id]);
        }
        $tablas = ['vecino'=>'vecinos','administrador'=>'administradores','chofer'=>'choferes','recolector'=>'recolectores','operario'=>'operarios'];
        $tabla = $tablas[$rol];
        $this->pdo->prepare("INSERT IGNORE INTO $tabla(id) VALUES (?)")->execute([$id]);
    }

    /**
     * Valida el dígito verificador de una Cédula de Identidad uruguaya
     * usando el algoritmo Módulo 10.
     */
    public static function validarCedulaUruguaya(string $cedula): bool
    {
        $numeros = preg_replace('/[^0-9]/', '', $cedula);
        if (strlen($numeros) < 7 || strlen($numeros) > 8 || (int)$numeros === 0) return false;
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
