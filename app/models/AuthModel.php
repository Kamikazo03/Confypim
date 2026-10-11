<?php

declare(strict_types=1);

/**
 * ==========================================================
 * AuthModel
 * ==========================================================
 * Modelo encargado de consultar y registrar usuarios
 * en la base de datos.
 * ==========================================================
 */

require_once ROOT_PATH . '/config/database.php';

class AuthModel
{
    /**
     * Conexión a la base de datos.
     */
    private PDO $db;

    /**
     * Inicializar el modelo.
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /* ======================================================
       CONSULTAR ADMINISTRADOR
       ====================================================== */

    /**
     * Buscar un administrador mediante su correo electrónico.
     */
    public function obtenerAdministradorPorEmail(string $email): ?array
    {
        $sql = "
            SELECT
                id_usuario,
                nombre,
                apellido,
                descripcion,
                avatar,
                foto_perfil,
                foto_pos_x,
                foto_pos_y,
                email,
                password_hash,
                fecha_registro,
                rol
            FROM usuario
            WHERE email = :email
              AND rol = 'administrador'
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    /* ======================================================
       CONSULTAR CORREO ELECTRÓNICO
       ====================================================== */

    /**
     * Comprobar si el correo ya está registrado.
     */
    public function existeEmail(string $email): bool
    {
        $sql = "
            SELECT id_usuario
            FROM usuario
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /* ======================================================
       REGISTRAR USUARIO
       ====================================================== */

    /**
     * Insertar un usuario nuevo.
     *
     * La contraseña debe llegar previamente cifrada
     * mediante password_hash().
     */
    public function crearUsuario(
        string $nombre,
        string $apellido,
        string $email,
        string $passwordHash,
        string $rol
    ): bool {
        $sql = "
            INSERT INTO usuario (
                nombre,
                apellido,
                email,
                password_hash,
                rol
            )
            VALUES (
                :nombre,
                :apellido,
                :email,
                :password_hash,
                :rol
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido !== '' ? $apellido : null,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':rol' => $rol
        ]);
    }
}