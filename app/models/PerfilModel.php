<?php

declare(strict_types=1);

require_once ROOT_PATH . '/config/database.php';

/**
 * ==========================================================
 * Perfil Model
 * ==========================================================
 *
 * Modelo encargado de consultar y actualizar
 * la información del perfil del usuario.
 *
 * ==========================================================
 */

class PerfilModel
{
    /**
     * Conexión a la base de datos.
     */
    private PDO $db;

    /**
     * Constructor.
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Obtiene el perfil completo de un usuario.
     */
    public function obtenerPorId(int $idUsuario): ?array
    {
        $sql = "
            SELECT
                id_usuario,
                nombre,
                apellido,
                descripcion,
                email,
                rol,
                avatar,
                foto_perfil,
                foto_pos_x,
                foto_pos_y,
                fecha_registro
            FROM usuario
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    /**
     * Actualiza los datos personales del usuario.
     */
    public function actualizar(
        int $idUsuario,
        string $nombre,
        string $apellido,
        ?string $descripcion
    ): bool {
        $sql = "
            UPDATE usuario
            SET
                nombre = :nombre,
                apellido = :apellido,
                descripcion = :descripcion
            WHERE id_usuario = :id_usuario
              AND rol = 'administrador'
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':descripcion' => $descripcion,
            ':id_usuario' => $idUsuario
        ]);
    }

    /**
     * Guarda el avatar seleccionado.
     */
    public function actualizarAvatar(
        int $idUsuario,
        ?string $avatar
    ): bool {
        $sql = "
            UPDATE usuario
            SET avatar = :avatar
            WHERE id_usuario = :id_usuario
              AND rol = 'administrador'
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':avatar' => $avatar,
            ':id_usuario' => $idUsuario
        ]);
    }

    /**
     * Guarda la ruta de la fotografía de perfil.
     */
    public function actualizarFoto(
        int $idUsuario,
        ?string $fotoPerfil
    ): bool {
        $sql = "
            UPDATE usuario
            SET foto_perfil = :foto_perfil
            WHERE id_usuario = :id_usuario
              AND rol = 'administrador'
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':foto_perfil' => $fotoPerfil,
            ':id_usuario' => $idUsuario
        ]);
    }

    /**
     * Guarda la posición horizontal y vertical de la fotografía.
     *
     * Los valores permitidos van de 0 a 100.
     */
    public function actualizarEncuadre(
        int $idUsuario,
        int $posicionX,
        int $posicionY
    ): bool {
        if (
            $posicionX < 0 || $posicionX > 100 ||
            $posicionY < 0 || $posicionY > 100
        ) {
            return false;
        }

        $sql = "
            UPDATE usuario
            SET
                foto_pos_x = :posicion_x,
                foto_pos_y = :posicion_y
            WHERE id_usuario = :id_usuario
              AND rol = 'administrador'
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':posicion_x' => $posicionX,
            ':posicion_y' => $posicionY,
            ':id_usuario' => $idUsuario
        ]);
    }
}