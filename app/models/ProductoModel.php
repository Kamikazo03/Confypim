<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

/**
 * ==========================================================
 * ProductoModel
 * ==========================================================
 * Modelo encargado de gestionar todas las operaciones
 * relacionadas con la entidad Producto.
 *
 * Centraliza las consultas, inserciones, actualizaciones
 * y eliminaciones lógicas sobre la tabla producto.
 * ==========================================================
 */
class ProductoModel
{
    /**
     * Conexión activa con la base de datos.
     */
    private PDO $db;

    /**
     * Crea una nueva instancia del modelo e inicializa
     * la conexión con la base de datos.
     */
    public function __construct()
    {
        $this->db = Database::connect();
    }

    /* ==========================================================
     * CONSULTAS
     * ========================================================== */

    /**
     * Obtiene todos los productos activos del administrador
     * actualmente autenticado.
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $sql = "
            SELECT
                id_producto,
                nombre,
                descripcion,
                precio_unitario,
                stock_disponible,
                imagen,
                CASE
                    WHEN stock_disponible = 0 THEN 'Agotado'
                    WHEN stock_disponible <= 5 THEN 'Stock Bajo'
                    ELSE 'Disponible'
                END AS estado,
                id_usuario,
                fecha_creacion
            FROM producto
            WHERE activo = 1
              AND id_usuario = :id_usuario
            ORDER BY id_producto DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene un producto mediante su identificador.
     *
     * La consulta también verifica que el producto
     * pertenezca al administrador autenticado.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $sql = "
            SELECT
                id_producto,
                nombre,
                descripcion,
                precio_unitario,
                stock_disponible,
                imagen,
                id_usuario
            FROM producto
            WHERE id_producto = :id
              AND id_usuario = :id_usuario
              AND activo = 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':id_usuario' => $idUsuario
        ]);

        $producto = $stmt->fetch();

        return $producto ?: null;
    }

    /* ==========================================================
     * DASHBOARD
     * ========================================================== */

    /**
     * Cuenta la cantidad total de productos activos
     * del administrador autenticado.
     *
     * @return int
     */
    public function contarProductos(): int
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $sql = "
            SELECT COUNT(*) AS total
            FROM producto
            WHERE activo = 1
              AND id_usuario = :id_usuario
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        return (int)$stmt->fetch()['total'];
    }

    /**
     * Obtiene los últimos productos registrados
     * por el administrador autenticado.
     *
     * @param int $limite
     * @return array
     */
    public function obtenerUltimos(int $limite = 5): array
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $sql = "
            SELECT
                id_producto,
                nombre,
                precio_unitario,
                stock_disponible,
                fecha_creacion
            FROM producto
            WHERE activo = 1
              AND id_usuario = :id_usuario
            ORDER BY fecha_creacion DESC
            LIMIT :limite
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':id_usuario',
            $idUsuario,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':limite',
            $limite,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Cuenta los productos con stock bajo
     * del administrador autenticado.
     *
     * @return int
     */
    public function contarStockBajo(): int
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $sql = "
            SELECT COUNT(*) AS total
            FROM producto
            WHERE activo = 1
              AND id_usuario = :id_usuario
              AND stock_disponible <= 5
              AND stock_disponible > 0
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        return (int)$stmt->fetch()['total'];
    }

    /* ==========================================================
     * INSERCIONES
     * ========================================================== */

    /**
     * Registra un nuevo producto.
     *
     * El usuario responsable se obtiene directamente
     * desde la sesión del administrador autenticado.
     *
     * @param array $datos
     * @return bool
     */
    public function crear(array $datos): bool
    {
        /**
         * Obtiene el administrador autenticado.
         */
        $idUsuario = obtenerIdUsuarioSesion();

        /**
         * No se permite registrar productos si no existe
         * un usuario autenticado.
         */
        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $sql = "
            INSERT INTO producto
            (
                nombre,
                descripcion,
                precio_unitario,
                stock_disponible,
                id_usuario
            )
            VALUES
            (
                :nombre,
                :descripcion,
                :precio,
                :stock,
                :usuario
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':precio' => $datos['precio_unitario'],
            ':stock' => $datos['stock_disponible'],
            ':usuario' => $idUsuario
        ]);
    }

    /* ==========================================================
     * ACTUALIZACIONES
     * ========================================================== */

    /**
     * Actualiza un producto existente.
     *
     * Solo permite modificar productos pertenecientes
     * al administrador autenticado.
     *
     * @param array $datos
     * @return bool
     */
    public function actualizar(array $datos): bool
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $sql = "
            UPDATE producto
            SET
                nombre = :nombre,
                descripcion = :descripcion,
                precio_unitario = :precio,
                stock_disponible = :stock
            WHERE id_producto = :id
              AND id_usuario = :id_usuario
              AND activo = 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':precio' => $datos['precio_unitario'],
            ':stock' => $datos['stock_disponible'],
            ':id' => $datos['id_producto'],
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->rowCount() > 0;
    }

    /* ==========================================================
     * ELIMINACIONES LÓGICAS
     * ========================================================== */

    /**
     * Desactiva un producto mediante eliminación lógica.
     *
     * Solo permite desactivar productos pertenecientes
     * al administrador autenticado.
     *
     * @param int $id
     * @return bool
     */
    public function desactivar(int $id): bool
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $sql = "
            UPDATE producto
            SET activo = 0
            WHERE id_producto = :id
              AND id_usuario = :id_usuario
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->rowCount() > 0;
    }
}