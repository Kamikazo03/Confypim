<?php

declare(strict_types=1);

require_once ROOT_PATH . '/config/database.php';

class VentaModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connect();
    }


    /* ==========================================================
     * CONSULTAS
     * ========================================================== */

    /**
     * Obtiene las ventas visibles para el módulo.
     *
     * Las ventas Canceladas se conservan en la BD,
     * pero no aparecen en el listado principal.
     */
    public function obtenerTodos(
        ?string $estado = null,
        ?string $busqueda = null
    ): array {

        $sql = "
            SELECT
                v.id_venta,
                v.fecha_venta,
                v.total,
                v.id_usuario,
                v.id_cliente,
                v.fecha_creacion,
                v.estado,
                COALESCE(
                    c.nombre,
                    'Cliente General'
                ) AS cliente
            FROM venta v
            LEFT JOIN usuario c
                ON c.id_usuario = v.id_cliente
            WHERE 1 = 1
        ";

        $params = [];

        /*
         * Si se selecciona un estado específico,
         * se consulta exactamente ese estado.
         */
        if (
            $estado !== null &&
            $estado !== '' &&
            $estado !== 'Todos'
        ) {

            $sql .= " AND v.estado = :estado";

            $params[':estado'] = $estado;

        } else {

            /*
             * Por defecto las ventas Canceladas
             * no aparecen en el listado principal.
             */
            $sql .= " AND v.estado <> 'Cancelada'";
        }

        /*
         * Búsqueda por número de venta
         * o nombre del cliente.
         */
        if (
            $busqueda !== null &&
            trim($busqueda) !== ''
        ) {

            $sql .= "
                AND (
                    CAST(v.id_venta AS CHAR) LIKE :busqueda
                    OR COALESCE(
                        c.nombre,
                        'Cliente General'
                    ) LIKE :busqueda
                )
            ";

            $params[':busqueda'] =
                '%' . trim($busqueda) . '%';
        }

        $sql .= " ORDER BY v.id_venta DESC";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Obtiene una venta específica.
     */
    public function obtenerPorId(int $idVenta): ?array
    {
        $sql = "
            SELECT
                v.id_venta,
                v.fecha_venta,
                v.total,
                v.id_usuario,
                v.id_cliente,
                v.fecha_creacion,
                v.estado,
                COALESCE(
                    c.nombre,
                    'Cliente General'
                ) AS cliente
            FROM venta v
            LEFT JOIN usuario c
                ON c.id_usuario = v.id_cliente
            WHERE v.id_venta = :id_venta
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_venta' => $idVenta
        ]);

        $venta =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $venta ?: null;
    }


    /**
     * Obtiene los productos asociados a una venta.
     */
    public function obtenerDetalle(int $idVenta): array
    {
        $sql = "
            SELECT
                dv.id_detalle,
                dv.id_venta,
                dv.id_producto,
                p.nombre AS producto,
                dv.cantidad,
                dv.precio_unitario,
                dv.subtotal
            FROM detalle_venta dv
            INNER JOIN producto p
                ON p.id_producto = dv.id_producto
            WHERE dv.id_venta = :id_venta
            ORDER BY dv.id_detalle ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_venta' => $idVenta
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Obtiene una venta completa junto con sus detalles.
     */
    public function obtenerCompleta(int $idVenta): ?array
    {
        $venta =
            $this->obtenerPorId($idVenta);

        if (!$venta) {
            return null;
        }

        $venta['detalles'] =
            $this->obtenerDetalle($idVenta);

        return $venta;
    }


    /* ==========================================================
     * INSERCIONES
     * ========================================================== */

    /**
     * Crea la cabecera de una venta.
     */
    public function crearVenta(
        string $fechaVenta,
        float $total,
        int $idUsuario,
        ?int $idCliente = null,
        string $estado = 'Pendiente'
    ): int {

        $sql = "
            INSERT INTO venta (
                fecha_venta,
                total,
                id_usuario,
                id_cliente,
                estado
            )
            VALUES (
                :fecha_venta,
                :total,
                :id_usuario,
                :id_cliente,
                :estado
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':fecha_venta' => $fechaVenta,
            ':total' => $total,
            ':id_usuario' => $idUsuario,
            ':id_cliente' => $idCliente,
            ':estado' => $estado
        ]);

        return (int) $this->db->lastInsertId();
    }


    /**
     * Agrega un detalle a una venta.
     */
    public function crearDetalle(
        int $idVenta,
        int $idProducto,
        int $cantidad,
        float $precioUnitario,
        float $subtotal
    ): int {

        $sql = "
            INSERT INTO detalle_venta (
                id_venta,
                id_producto,
                cantidad,
                precio_unitario,
                subtotal
            )
            VALUES (
                :id_venta,
                :id_producto,
                :cantidad,
                :precio_unitario,
                :subtotal
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_venta' => $idVenta,
            ':id_producto' => $idProducto,
            ':cantidad' => $cantidad,
            ':precio_unitario' => $precioUnitario,
            ':subtotal' => $subtotal
        ]);

        return (int) $this->db->lastInsertId();
    }


    /* ==========================================================
     * ACTUALIZACIONES
     * ========================================================== */

    /**
     * Actualiza los datos principales de una venta.
     */
    public function actualizarVenta(
        int $idVenta,
        string $fechaVenta,
        float $total,
        ?int $idCliente = null
    ): bool {

        $sql = "
            UPDATE venta
            SET
                fecha_venta = :fecha_venta,
                total = :total,
                id_cliente = :id_cliente
            WHERE id_venta = :id_venta
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':fecha_venta' => $fechaVenta,
            ':total' => $total,
            ':id_cliente' => $idCliente,
            ':id_venta' => $idVenta
        ]);
    }


    /**
     * Elimina los detalles de una venta.
     *
     * Se utiliza únicamente cuando la venta está Pendiente
     * y se está reconstruyendo su detalle.
     */
    public function eliminarDetalles(int $idVenta): bool
    {
        $sql = "
            DELETE FROM detalle_venta
            WHERE id_venta = :id_venta
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id_venta' => $idVenta
        ]);
    }


    /**
     * Cambia el estado de una venta.
     */
    public function actualizarEstado(
        int $idVenta,
        string $estado
    ): bool {

        $sql = "
            UPDATE venta
            SET estado = :estado
            WHERE id_venta = :id_venta
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':estado' => $estado,
            ':id_venta' => $idVenta
        ]);
    }


    /* ==========================================================
     * PRODUCTOS
     * ========================================================== */

    /**
     * Obtiene información de un producto.
     */
    public function obtenerProducto(
        int $idProducto
    ): ?array {

        $sql = "
            SELECT
                id_producto,
                nombre,
                precio_unitario,
                stock_disponible,
                activo
            FROM producto
            WHERE id_producto = :id_producto
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_producto' => $idProducto
        ]);

        $producto =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $producto ?: null;
    }


    /**
     * Obtiene un producto bloqueando su registro durante
     * una operación transaccional.
     *
     * Se utiliza al momento de pagar una venta.
     */
    public function obtenerProductoParaActualizacion(
        int $idProducto
    ): ?array {

        $sql = "
            SELECT
                id_producto,
                nombre,
                precio_unitario,
                stock_disponible,
                activo
            FROM producto
            WHERE id_producto = :id_producto
            FOR UPDATE
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_producto' => $idProducto
        ]);

        $producto =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $producto ?: null;
    }


    /**
     * Descuenta stock de un producto.
     */
    public function descontarStock(
        int $idProducto,
        int $cantidad
    ): bool {

        $sql = "
            UPDATE producto
            SET stock_disponible =
                stock_disponible - :cantidad
            WHERE id_producto = :id_producto
              AND stock_disponible >= :cantidad
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':cantidad' => $cantidad,
            ':id_producto' => $idProducto
        ]);

        return $stmt->rowCount() === 1;
    }


    /* ==========================================================
     * MÉTRICAS
     * ========================================================== */

    /**
     * Obtiene las métricas generales del módulo.
     *
     * Solo se consideran ventas Pagadas.
     *
     * Los cálculos se realizan mediante consultas independientes
     * para evitar duplicar el total de una venta cuando contiene
     * varios productos.
     */
    public function obtenerMetricas(): array
    {
        $sql = "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM venta
                    WHERE estado = 'Pagada'
                ) AS total_ventas_pagadas,

                (
                    SELECT COALESCE(SUM(total), 0)
                    FROM venta
                    WHERE estado = 'Pagada'
                ) AS ingresos_totales,

                (
                    SELECT COALESCE(SUM(dv.cantidad), 0)
                    FROM detalle_venta dv
                    INNER JOIN venta v
                        ON v.id_venta = dv.id_venta
                    WHERE v.estado = 'Pagada'
                ) AS productos_vendidos,

                (
                    SELECT COUNT(DISTINCT dv.id_producto)
                    FROM detalle_venta dv
                    INNER JOIN venta v
                        ON v.id_venta = dv.id_venta
                    WHERE v.estado = 'Pagada'
                ) AS productos_diferentes
        ";

        $stmt = $this->db->query($sql);

        $metricas =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'ventas_pagadas' =>
                (int) (
                    $metricas['total_ventas_pagadas']
                    ?? 0
                ),

            'ingresos_totales' =>
                (float) (
                    $metricas['ingresos_totales']
                    ?? 0
                ),

            'productos_vendidos' =>
                (int) (
                    $metricas['productos_vendidos']
                    ?? 0
                ),

            'productos_diferentes' =>
                (int) (
                    $metricas['productos_diferentes']
                    ?? 0
                )
        ];
    }


    /**
     * Obtiene el promedio de venta entre ventas pagadas.
     */
    public function obtenerPromedioVenta(): float
    {
        $sql = "
            SELECT COALESCE(AVG(total), 0)
            FROM venta
            WHERE estado = 'Pagada'
        ";

        $stmt = $this->db->query($sql);

        return (float) $stmt->fetchColumn();
    }


    /* ==========================================================
     * CONEXIÓN
     * ========================================================== */

    /**
     * Obtiene la conexión activa con la base de datos.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->db;
    }
}