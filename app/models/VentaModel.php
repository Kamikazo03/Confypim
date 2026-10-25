<?php

declare(strict_types=1);

require_once ROOT_PATH . '/config/database.php';

class VentaModel
{
    /**
     * Conexión activa con la base de datos.
     */
    private PDO $db;

    /**
     * Inicializa el modelo.
     *
     * Puede recibir una conexión existente para permitir
     * el uso de transacciones desde VentaService.
     */
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
     *
     * Cada administrador consulta únicamente
     * las ventas que él mismo registró.
     *
     * @param int $idUsuario
     * @param string|null $estado
     * @param string|null $busqueda
     * @return array
     */
    public function obtenerTodos(
        int $idUsuario,
        ?string $estado = null,
        ?string $busqueda = null
    ): array {

        $sql = "
            SELECT
                v.id_venta,
                v.numero_venta,
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
            WHERE v.id_usuario = :id_usuario
        ";

        $params = [
            ':id_usuario' => $idUsuario
        ];

        /**
         * Si se selecciona un estado específico,
         * se consulta exactamente ese estado.
         */
        if (
            $estado !== null &&
            $estado !== '' &&
            $estado !== 'Todos'
        ) {

            $sql .= "
                AND v.estado = :estado
            ";

            $params[':estado'] = $estado;

        } else {

            /**
             * Por defecto las ventas Canceladas
             * no aparecen en el listado principal.
             */
            $sql .= "
                AND v.estado <> 'Cancelada'
            ";
        }

        /**
         * Búsqueda por número de venta
         * o nombre del cliente.
         */
        if (
            $busqueda !== null &&
            trim($busqueda) !== ''
        ) {

            $sql .= "
                AND (
                    CAST(v.numero_venta AS CHAR)
                    LIKE :busqueda
                    OR COALESCE(
                        c.nombre,
                        'Cliente General'
                    ) LIKE :busqueda
                )
            ";

            $params[':busqueda'] =
                '%' . trim($busqueda) . '%';
        }

        $sql .= "
            ORDER BY v.id_venta DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una venta específica.
     *
     * La venta solamente puede ser consultada
     * por el administrador que la registró.
     *
     * @param int $idVenta
     * @param int $idUsuario
     * @return array|null
     */
    public function obtenerPorId(
        int $idVenta,
        int $idUsuario
    ): ?array {

        $sql = "
            SELECT
                v.id_venta,
                v.numero_venta,
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
              AND v.id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_venta' => $idVenta,
            ':id_usuario' => $idUsuario
        ]);

        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        return $venta ?: null;
    }

    /**
     * Obtiene los productos asociados a una venta.
     *
     * La consulta valida que la venta pertenezca
     * al administrador autenticado.
     *
     * @param int $idVenta
     * @param int $idUsuario
     * @return array
     */
    public function obtenerDetalle(
        int $idVenta,
        int $idUsuario
    ): array {

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
            INNER JOIN venta v
                ON v.id_venta = dv.id_venta
            WHERE dv.id_venta = :id_venta
              AND v.id_usuario = :id_usuario
            ORDER BY dv.id_detalle ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_venta' => $idVenta,
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta los detalles guardados de una venta.
     *
     * Se utiliza dentro de la transacción de creación y
     * actualización para comprobar que todos los productos
     * de la venta quedaron registrados en detalle_venta.
     *
     * @param int $idVenta
     * @return int
     */
    public function contarDetalles(
        int $idVenta
    ): int {

        $sql = "
            SELECT COUNT(*)
            FROM detalle_venta
            WHERE id_venta = :id_venta
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_venta' => $idVenta
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtiene una venta completa junto con sus detalles.
     *
     * @param int $idVenta
     * @param int $idUsuario
     * @return array|null
     */
    public function obtenerCompleta(
        int $idVenta,
        int $idUsuario
    ): ?array {

        $venta = $this->obtenerPorId(
            $idVenta,
            $idUsuario
        );

        if (!$venta) {
            return null;
        }

        $venta['detalles'] =
            $this->obtenerDetalle(
                $idVenta,
                $idUsuario
            );

        return $venta;
    }

    /* ==========================================================
     * INSERCIONES
     * ========================================================== */

    /**
     * Obtiene el siguiente numero_venta para un administrador.
     *
     * El registro del consecutivo se bloquea durante
     * la transacción para evitar números repetidos
     * cuando se crean ventas simultáneamente.
     *
     * Este método debe ejecutarse dentro de una transacción.
     *
     * @param int $idUsuario
     * @return int
     */
    public function obtenerSiguienteNumeroVenta(
        int $idUsuario
    ): int {

        /**
         * Si el administrador todavía no tiene un registro
         * de consecutivo, se crea con valor inicial 0.
         */
        $sqlInsertar = "
            INSERT INTO consecutivo_venta
            (
                id_usuario,
                ultimo_numero
            )
            VALUES
            (
                :id_usuario,
                0
            )
            ON DUPLICATE KEY UPDATE
                id_usuario = id_usuario
        ";

        $stmtInsertar =
            $this->db->prepare($sqlInsertar);

        $stmtInsertar->execute([
            ':id_usuario' => $idUsuario
        ]);

        /**
         * Bloqueamos el registro para evitar que otra
         * transacción utilice el mismo número.
         */
        $sqlConsultar = "
            SELECT
                ultimo_numero
            FROM consecutivo_venta
            WHERE id_usuario = :id_usuario
            FOR UPDATE
        ";

        $stmtConsultar =
            $this->db->prepare($sqlConsultar);

        $stmtConsultar->execute([
            ':id_usuario' => $idUsuario
        ]);

        $ultimoNumero =
            (int) $stmtConsultar->fetchColumn();

        $nuevoNumero =
            $ultimoNumero + 1;

        /**
         * Actualizamos el último número utilizado.
         */
        $sqlActualizar = "
            UPDATE consecutivo_venta
            SET ultimo_numero = :ultimo_numero
            WHERE id_usuario = :id_usuario
        ";

        $stmtActualizar =
            $this->db->prepare($sqlActualizar);

        $stmtActualizar->execute([
            ':ultimo_numero' => $nuevoNumero,
            ':id_usuario' => $idUsuario
        ]);

        return $nuevoNumero;
    }

    /**
     * Crea la cabecera de una venta.
     *
     * El id_usuario recibido corresponde al administrador
     * autenticado que registra la venta.
     *
     * numero_venta corresponde al consecutivo visible
     * para ese administrador.
     *
     * @param string $fechaVenta
     * @param float $total
     * @param int $idUsuario
     * @param int $numeroVenta
     * @param int|null $idCliente
     * @param string $estado
     * @return int
     */
    public function crearVenta(
        string $fechaVenta,
        float $total,
        int $idUsuario,
        int $numeroVenta,
        ?int $idCliente = null,
        string $estado = 'Pendiente'
    ): int {

        $sql = "
            INSERT INTO venta
            (
                fecha_venta,
                total,
                id_usuario,
                numero_venta,
                id_cliente,
                estado
            )
            VALUES
            (
                :fecha_venta,
                :total,
                :id_usuario,
                :numero_venta,
                :id_cliente,
                :estado
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':fecha_venta' => $fechaVenta,
            ':total' => $total,
            ':id_usuario' => $idUsuario,
            ':numero_venta' => $numeroVenta,
            ':id_cliente' => $idCliente,
            ':estado' => $estado
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Agrega un detalle a una venta.
     *
     * El precio unitario se guarda como una fotografía
     * del precio vigente en el momento de crear la venta.
     *
     * @return int
     */
    public function crearDetalle(
        int $idVenta,
        int $idProducto,
        int $cantidad,
        float $precioUnitario,
        float $subtotal
    ): int {

        $sql = "
            INSERT INTO detalle_venta
            (
                id_venta,
                id_producto,
                cantidad,
                precio_unitario,
                subtotal
            )
            VALUES
            (
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
     *
     * Solo permite modificar una venta perteneciente
     * al administrador autenticado.
     */
    public function actualizarVenta(
        int $idVenta,
        int $idUsuario,
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
              AND id_usuario = :id_usuario
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':fecha_venta' => $fechaVenta,
            ':total' => $total,
            ':id_cliente' => $idCliente,
            ':id_venta' => $idVenta,
            ':id_usuario' => $idUsuario
        ]);
    }

    /**
     * Elimina los detalles de una venta.
     *
     * Se utiliza únicamente cuando la venta está Pendiente
     * y se está reconstruyendo su detalle.
     */
    public function eliminarDetalles(
        int $idVenta
    ): bool {

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
     *
     * Solo permite modificar ventas pertenecientes
     * al administrador autenticado.
     */
    public function actualizarEstado(
        int $idVenta,
        int $idUsuario,
        string $estado
    ): bool {

        $sql = "
            UPDATE venta
            SET estado = :estado
            WHERE id_venta = :id_venta
              AND id_usuario = :id_usuario
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':estado' => $estado,
            ':id_venta' => $idVenta,
            ':id_usuario' => $idUsuario
        ]);
    }

    /* ==========================================================
     * PRODUCTOS
     * ========================================================== */

    /**
     * Obtiene información de un producto.
     *
     * Solo devuelve productos pertenecientes
     * al administrador autenticado.
     */
    public function obtenerProducto(
        int $idProducto,
        int $idUsuario
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
              AND id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_producto' => $idProducto,
            ':id_usuario' => $idUsuario
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
     *
     * El producto también debe pertenecer al administrador
     * que registró la venta.
     */
    public function obtenerProductoParaActualizacion(
        int $idProducto,
        int $idUsuario
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
              AND id_usuario = :id_usuario
            FOR UPDATE
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_producto' => $idProducto,
            ':id_usuario' => $idUsuario
        ]);

        $producto =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $producto ?: null;
    }

    /**
     * Descuenta stock de un producto.
     *
     * Solo permite descontar stock del administrador
     * propietario del producto.
     */
    public function descontarStock(
        int $idProducto,
        int $idUsuario,
        int $cantidad
    ): bool {

        $sql = "
            UPDATE producto
            SET stock_disponible =
                stock_disponible - :cantidad
            WHERE id_producto = :id_producto
              AND id_usuario = :id_usuario
              AND stock_disponible >= :cantidad
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':cantidad' => $cantidad,
            ':id_producto' => $idProducto,
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->rowCount() === 1;
    }

    /* ==========================================================
     * CLIENTES
     * ========================================================== */

    /**
     * Obtiene el identificador y el rol de un usuario.
     *
     * Se utiliza para validar que el id_cliente enviado
     * en una venta exista y corresponda a un usuario con
     * rol cliente. No se consulta información sensible.
     *
     * @param int $idUsuario
     * @return array|null
     */
    public function obtenerUsuarioPorId(
        int $idUsuario
    ): ?array {

        $sql = "
            SELECT
                id_usuario,
                rol
            FROM usuario
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        $usuario =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    /* ==========================================================
     * MÉTRICAS
     * ========================================================== */

    /**
     * Obtiene las métricas generales del módulo.
     *
     * Solo se consideran ventas Pagadas del
     * administrador autenticado.
     *
     * Los cálculos se realizan mediante consultas
     * independientes para evitar duplicar el total
     * de una venta cuando contiene varios productos.
     */
    public function obtenerMetricas(
        int $idUsuario
    ): array {

        $sql = "
            SELECT

                (
                    SELECT COUNT(*)
                    FROM venta
                    WHERE estado = 'Pagada'
                      AND id_usuario = :id_usuario_1
                ) AS total_ventas_pagadas,

                (
                    SELECT COALESCE(SUM(total), 0)
                    FROM venta
                    WHERE estado = 'Pagada'
                      AND id_usuario = :id_usuario_2
                ) AS ingresos_totales,

                (
                    SELECT COALESCE(SUM(dv.cantidad), 0)
                    FROM detalle_venta dv
                    INNER JOIN venta v
                        ON v.id_venta = dv.id_venta
                    WHERE v.estado = 'Pagada'
                      AND v.id_usuario = :id_usuario_3
                ) AS productos_vendidos,

                (
                    SELECT COUNT(DISTINCT dv.id_producto)
                    FROM detalle_venta dv
                    INNER JOIN venta v
                        ON v.id_venta = dv.id_venta
                    WHERE v.estado = 'Pagada'
                      AND v.id_usuario = :id_usuario_4
                ) AS productos_diferentes
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario_1' => $idUsuario,
            ':id_usuario_2' => $idUsuario,
            ':id_usuario_3' => $idUsuario,
            ':id_usuario_4' => $idUsuario
        ]);

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
    public function obtenerPromedioVenta(
        int $idUsuario
    ): float {

        $sql = "
            SELECT COALESCE(AVG(total), 0)
            FROM venta
            WHERE estado = 'Pagada'
              AND id_usuario = :id_usuario
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

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