<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

/**==========================================================
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
     * Obtiene todos los productos activos.
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
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
            ORDER BY id_producto DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtiene un producto mediante su identificador.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "
            SELECT
                id_producto,
                nombre,
                descripcion,
                precio_unitario,
                stock_disponible,
                imagen
            FROM producto
            WHERE id_producto = :id
            AND activo = 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([":id" => $id]);

        $producto = $stmt->fetch();

        return $producto ?: null;
    }

    /* ==========================================================
     * DASHBOARD
     * ========================================================== */

    /**
     * Cuenta la cantidad total de productos activos.
     *
     * @return int
     */
    public function contarProductos(): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM producto
            WHERE activo = 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return (int)$stmt->fetch()['total'];
    }

    /**
     * Obtiene los últimos productos registrados.
     *
     * @param int $limite
     * @return array
     */
    public function obtenerUltimos(int $limite = 5): array
    {
        $sql = "
            SELECT
                id_producto,
                nombre,
                precio_unitario,
                stock_disponible,
                fecha_creacion
            FROM producto
            WHERE activo = 1
            ORDER BY fecha_creacion DESC
            LIMIT :limite
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Cuenta los productos con stock bajo.
     *
     * @return int
     */
    public function contarStockBajo(): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM producto
            WHERE activo = 1
                AND stock_disponible <= 5
                AND stock_disponible > 0
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return (int)$stmt->fetch()['total'];
    }

    /* ==========================================================
     * INSERCIONES
     * ========================================================== */

    /**
     * Registra un nuevo producto.
     *
     * @param array $datos
     * @return bool
     */
    public function crear(array $datos): bool
    {
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
            // Temporal mientras se implementa autenticación.
            ':usuario' => 1
        ]);
    }

    /* ==========================================================
     * ACTUALIZACIONES
     * ========================================================== */

    /**
     * Actualiza un producto existente.
     *
     * @param array $datos
     * @return bool
     */
    public function actualizar(array $datos): bool
    {
        $sql = "
            UPDATE producto
            SET
                nombre = :nombre,
                descripcion = :descripcion,
                precio_unitario = :precio,
                stock_disponible = :stock
            WHERE id_producto = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':precio' => $datos['precio_unitario'],
            ':stock' => $datos['stock_disponible'],
            ':id' => $datos['id_producto']
        ]);
    }

    /* ==========================================================
     * ELIMINACIONES LÓGICAS
     * ========================================================== */

    /**
     * Desactiva un producto mediante eliminación lógica.
     *
     * @param int $id
     * @return bool
     */
    public function desactivar(int $id): bool
    {
        $sql = "
            UPDATE producto
            SET activo = 0
            WHERE id_producto = :id
        ";

        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':id' => $id
        ]);
    }

}