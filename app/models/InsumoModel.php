<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

/**
 * ==========================================================
 * InsumoModel
 * ==========================================================
 * Modelo encargado de gestionar las operaciones relacionadas
 * con los insumos, sus movimientos de inventario y las recetas.
 *
 * Centraliza las consultas y operaciones sobre las tablas:
 * - insumo
 * - movimiento_insumo
 * - gasto
 * - producto
 * - producto_insumo
 * ==========================================================
 */

class InsumoModel
{
    /** Identificador fijo de la categoría "Ingredientes" en categoria_gasto. */
    private const ID_CATEGORIA_GASTO_INGREDIENTES = 1;

    /**
     * Conexión activa con la base de datos.
     */
    private PDO $db;

    /**
     * Crea una nueva instancia del modelo.
     */
    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Obtener el identificador del usuario autenticado.
     */
    private function obtenerUsuario(): int
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException('No existe un usuario autenticado.');
        }

        return $idUsuario;
    }

    /* ==========================================================
     * CONSULTAS DE INSUMOS
     * ========================================================== */

    /**
     * Obtener los insumos activos del usuario autenticado.
     *
     * El stock mínimo se calcula desde el código y no se almacena
     * como una columna adicional en la base de datos.
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                id_insumo,
                nombre,
                cantidad,
                unidad_medida,
                costo_unitario,
                CASE
                    WHEN cantidad <= 0 THEN 'Agotado'
                    WHEN (
                        (unidad_medida IN ('g', 'ml') AND cantidad < 1000)
                        OR
                        (unidad_medida = 'unidades' AND cantidad <= 5)
                    ) THEN 'Stock bajo'
                    ELSE 'Disponible'
                END AS estado,
                CASE
                    WHEN unidad_medida IN ('g', 'ml') THEN 1000
                    ELSE 5
                END AS stock_minimo
            FROM insumo
            WHERE id_usuario = :id_usuario
              AND activo = 1
            ORDER BY nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetchAll();
    }

    /**
     * Obtener un insumo activo mediante su identificador.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                id_insumo,
                nombre,
                cantidad,
                unidad_medida,
                costo_unitario,
                activo
            FROM insumo
            WHERE id_insumo = :id
              AND id_usuario = :id_usuario
              AND activo = 1
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':id_usuario' => $idUsuario
        ]);

        $insumo = $stmt->fetch();

        return $insumo ?: null;
    }

    /**
     * Obtener insumos inactivos para permitir su restauración.
     *
     * @return array
     */
    public function obtenerInactivos(): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                id_insumo,
                nombre,
                cantidad,
                unidad_medida,
                costo_unitario
            FROM insumo
            WHERE id_usuario = :id_usuario
              AND activo = 0
            ORDER BY nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetchAll();
    }

    /**
     * Obtener indicadores generales del inventario.
     *
     * @return array
     */
    public function obtenerMetricas(): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                COUNT(*) AS total_insumos,
                SUM(CASE WHEN cantidad <= 0 THEN 1 ELSE 0 END) AS agotados,
                SUM(
                    CASE
                        WHEN cantidad > 0
                         AND (
                            (unidad_medida IN ('g', 'ml') AND cantidad < 1000)
                            OR
                            (unidad_medida = 'unidades' AND cantidad <= 5)
                         )
                        THEN 1 ELSE 0
                    END
                ) AS stock_bajo,
                COALESCE(SUM(cantidad * costo_unitario), 0) AS valor_estimado
            FROM insumo
            WHERE id_usuario = :id_usuario
              AND activo = 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        $resultado = $stmt->fetch();

        return [
            'total_insumos' => (int) ($resultado['total_insumos'] ?? 0),
            'agotados' => (int) ($resultado['agotados'] ?? 0),
            'stock_bajo' => (int) ($resultado['stock_bajo'] ?? 0),
            'valor_estimado' => (float) ($resultado['valor_estimado'] ?? 0)
        ];
    }

    /* ==========================================================
     * INSERCIONES Y ACTUALIZACIONES
     * ========================================================== */

    /**
     * Registrar un nuevo insumo.
     *
     * La cantidad y el costo inicial se establecen en cero.
     * Las existencias se incorporan mediante una compra o ajuste.
     *
     * @param array $datos
     * @return bool
     */
    public function crear(array $datos): bool
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            INSERT INTO insumo
            (
                nombre,
                cantidad,
                unidad_medida,
                costo_unitario,
                id_usuario,
                activo
            )
            VALUES
            (
                :nombre,
                0,
                :unidad,
                0,
                :id_usuario,
                1
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':unidad' => $datos['unidad_medida'],
            ':id_usuario' => $idUsuario
        ]);
    }

    /**
     * Actualizar el nombre de un insumo.
     *
     * La unidad de medida no se modifica para conservar la
     * consistencia de las cantidades almacenadas en recetas
     * y movimientos históricos.
     *
     * @param array $datos
     * @return bool
     */
    public function actualizar(array $datos): bool
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            UPDATE insumo
            SET nombre = :nombre
            WHERE id_insumo = :id
              AND id_usuario = :id_usuario
              AND activo = 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':id' => $datos['id_insumo'],
            ':id_usuario' => $idUsuario
        ]);
    }

    /**
     * Verificar si un insumo forma parte de alguna receta activa.
     *
     * @param int $id
     * @return bool
     */
    public function tieneRecetas(int $id): bool
    {
        $idUsuario = $this->obtenerUsuario();

        $stmt = $this->db->prepare("
            SELECT 1
            FROM producto_insumo
            WHERE id_insumo = :id
              AND id_usuario = :id_usuario
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
            ':id_usuario' => $idUsuario
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Desactivar un insumo mediante eliminación lógica.
     *
     * No elimina sus movimientos ni sus relaciones históricas.
     *
     * @param int $id
     * @return bool
     */
    public function desactivar(int $id): bool
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            UPDATE insumo
            SET activo = 0
            WHERE id_insumo = :id
              AND id_usuario = :id_usuario
              AND activo = 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':id_usuario' => $idUsuario
        ]);
    }

    /**
     * Restaurar un insumo previamente desactivado.
     *
     * Si existe otro insumo activo con el mismo nombre,
     * la restricción UNIQUE impedirá la restauración.
     *
     * @param int $id
     * @return bool
     */
    public function restaurar(int $id): bool
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            UPDATE insumo
            SET activo = 1
            WHERE id_insumo = :id
              AND id_usuario = :id_usuario
              AND activo = 0
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Obtener la categoría de gasto destinada a compras de insumos.
     *
     * La vista de Insumos no solicita una categoría porque todas
     * sus compras pertenecen a este concepto contable.
     */
    private function obtenerCategoriaGastoInsumos(): int
    {
        /**
         * La categoría "Ingredientes" está registrada en categoria_gasto
         * con id_categoria_gasto = 1.
         *
         * Se utiliza directamente este identificador para evitar búsquedas
         * ambiguas por nombre y garantizar que las compras de insumos queden
         * clasificadas en la categoría correcta.
         */
        return self::ID_CATEGORIA_GASTO_INGREDIENTES;
    }

    /**
     * Registrar varias compras de insumos dentro de una única transacción.
     *
     * Se crea un solo gasto por el valor total del lote y cada insumo
     * recibe su movimiento de entrada asociado al mismo gasto.
     *
     * @param array $items
     * @param string $observacion
     * @return bool
     */
    public function registrarComprasMultiples(array $items, string $observacion = ''): bool
    {
        $idUsuario = $this->obtenerUsuario();
        $idCategoria = $this->obtenerCategoriaGastoInsumos();

        try {
            $this->db->beginTransaction();

            $totalCompra = array_sum(array_column($items, 'costo_total'));

            if ($totalCompra <= 0) {
                throw new RuntimeException('El valor total de la compra debe ser mayor que cero.');
            }

            $ids = array_column($items, 'id_insumo');
            sort($ids);

            $stmtBloqueo = $this->db->prepare("
                SELECT id_insumo, nombre, cantidad, costo_unitario
                FROM insumo
                WHERE id_insumo = :id
                  AND id_usuario = :id_usuario
                  AND activo = 1
                FOR UPDATE
            ");

            $insumosBloqueados = [];

            foreach ($ids as $id) {
                $stmtBloqueo->execute([
                    ':id' => $id,
                    ':id_usuario' => $idUsuario
                ]);

                $insumo = $stmtBloqueo->fetch();

                if (!$insumo) {
                    throw new RuntimeException('Uno de los insumos seleccionados no existe o está inactivo.');
                }

                $insumosBloqueados[(int) $id] = $insumo;
            }

            $stmtGasto = $this->db->prepare("
                INSERT INTO gasto
                (
                    descripcion,
                    monto,
                    fecha,
                    id_categoria_gasto,
                    id_usuario
                )
                VALUES
                (
                    :descripcion,
                    :monto,
                    CURRENT_DATE(),
                    :categoria,
                    :id_usuario
                )
            ");

            $stmtGasto->execute([
                ':descripcion' => 'Compra de insumos (' . count($items) . ' insumos)',
                ':monto' => $totalCompra,
                ':categoria' => $idCategoria,
                ':id_usuario' => $idUsuario
            ]);

            $idGasto = (int) $this->db->lastInsertId();

            foreach ($items as $item) {
                $insumo = $insumosBloqueados[(int) $item['id_insumo']];
                $cantidadAnterior = (float) $insumo['cantidad'];
                $costoAnterior = (float) $insumo['costo_unitario'];
                $cantidadNueva = (float) $item['cantidad_base'];
                $costoCompraTotal = (float) $item['costo_total'];
                $nuevoStock = $cantidadAnterior + $cantidadNueva;

                $nuevoCostoPromedio = (
                    ($cantidadAnterior * $costoAnterior) + $costoCompraTotal
                ) / $nuevoStock;

                $this->actualizarCantidadYCosto(
                    (int) $item['id_insumo'],
                    $idUsuario,
                    $nuevoStock,
                    $nuevoCostoPromedio
                );

                $this->insertarMovimiento(
                    (int) $item['id_insumo'],
                    $idUsuario,
                    'entrada',
                    'entrada',
                    $cantidadNueva,
                    $costoCompraTotal / $cantidadNueva,
                    $idGasto,
                    $item['observacion'] !== '' ? $item['observacion'] : $observacion
                );
            }

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Actualizar existencias y costo promedio ponderado.
     */
    private function actualizarCantidadYCosto(
        int $idInsumo,
        int $idUsuario,
        float $cantidad,
        float $costo
    ): void {
        $stmt = $this->db->prepare("
            UPDATE insumo
            SET cantidad = :cantidad,
                costo_unitario = :costo
            WHERE id_insumo = :id
              AND id_usuario = :id_usuario
        ");

        $stmt->execute([
            ':cantidad' => $cantidad,
            ':costo' => $costo,
            ':id' => $idInsumo,
            ':id_usuario' => $idUsuario
        ]);
    }

    /**
     * Registrar varios consumos o ajustes en una sola transacción.
     *
     * @param array $items
     * @param string $tipo
     * @param string $operacion
     * @param string $observacion
     * @return bool
     */
    public function registrarMovimientosMultiples(
        array $items,
        string $tipo,
        string $operacion,
        string $observacion
    ): bool {
        $idUsuario = $this->obtenerUsuario();

        if (!in_array($tipo, ['consumo', 'ajuste'], true) ||
            !in_array($operacion, ['entrada', 'salida'], true)) {
            throw new InvalidArgumentException('El tipo de movimiento no es válido.');
        }

        if ($tipo === 'consumo') {
            $operacion = 'salida';
        }

        try {
            $this->db->beginTransaction();

            $ids = array_column($items, 'id_insumo');
            sort($ids);

            $insumosBloqueados = [];

            foreach ($ids as $id) {
                $insumo = $this->bloquearInsumo((int) $id, $idUsuario);

                if (!$insumo) {
                    throw new RuntimeException('Uno de los insumos seleccionados no existe o está inactivo.');
                }

                $insumosBloqueados[(int) $id] = $insumo;
            }

            foreach ($items as $item) {
                $idInsumo = (int) $item['id_insumo'];
                $cantidad = (float) $item['cantidad'];
                $stockActual = (float) $insumosBloqueados[$idInsumo]['cantidad'];

                if ($operacion === 'salida' && $cantidad > $stockActual) {
                    throw new RuntimeException(
                        'La cantidad solicitada de "' . $insumosBloqueados[$idInsumo]['nombre'] .
                        '" supera las existencias disponibles (' . $stockActual . ').'
                    );
                }

                $nuevoStock = $operacion === 'entrada'
                    ? $stockActual + $cantidad
                    : $stockActual - $cantidad;

                $this->actualizarCantidad($idInsumo, $idUsuario, $nuevoStock);

                $this->insertarMovimiento(
                    $idInsumo,
                    $idUsuario,
                    $tipo,
                    $operacion,
                    $cantidad,
                    null,
                    null,
                    $item['observacion'] !== '' ? $item['observacion'] : $observacion
                );
            }

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /* ==========================================================
     * COMPRAS DE INSUMOS
     * ========================================================== */

    /**
     * Registrar una compra de insumo.
     *
     * La operación se ejecuta en una transacción:
     * 1. Bloquea el insumo.
     * 2. Registra el gasto.
     * 3. Actualiza existencias y costo promedio ponderado.
     * 4. Registra el movimiento de entrada.
     *
     * @param array $datos
     * @return bool
     */
    public function registrarCompra(array $datos): bool
    {
        $idUsuario = $this->obtenerUsuario();

        try {
            $this->db->beginTransaction();

            $stmtInsumo = $this->db->prepare("
                SELECT id_insumo, nombre, cantidad, costo_unitario
                FROM insumo
                WHERE id_insumo = :id
                  AND id_usuario = :id_usuario
                  AND activo = 1
                FOR UPDATE
            ");

            $stmtInsumo->execute([
                ':id' => $datos['id_insumo'],
                ':id_usuario' => $idUsuario
            ]);

            $insumo = $stmtInsumo->fetch();

            if (!$insumo) {
                throw new RuntimeException('El insumo seleccionado no existe o está inactivo.');
            }

            $cantidadAnterior = (float) $insumo['cantidad'];
            $costoAnterior = (float) $insumo['costo_unitario'];
            $cantidadNueva = (float) $datos['cantidad_base'];
            $costoCompraTotal = (float) $datos['costo_total'];

            $nuevoStock = $cantidadAnterior + $cantidadNueva;

            $nuevoCostoPromedio = $nuevoStock > 0
                ? (
                    ($cantidadAnterior * $costoAnterior) +
                    $costoCompraTotal
                ) / $nuevoStock
                : 0;

            /**
             * Registrar el gasto correspondiente a la compra.
             */
            $stmtGasto = $this->db->prepare("
                INSERT INTO gasto
                (
                    descripcion,
                    monto,
                    fecha,
                    id_categoria_gasto,
                    id_usuario
                )
                VALUES
                (
                    :descripcion,
                    :monto,
                    CURRENT_DATE(),
                    :categoria,
                    :id_usuario
                )
            ");

            $stmtGasto->execute([
                ':descripcion' => 'Compra de insumo: ' . $insumo['nombre'],
                ':monto' => $costoCompraTotal,
                ':categoria' => $this->obtenerCategoriaGastoInsumos(),
                ':id_usuario' => $idUsuario
            ]);

            $idGasto = (int) $this->db->lastInsertId();

            /**
             * Actualizar las existencias y el costo promedio.
             */
            $stmtActualizar = $this->db->prepare("
                UPDATE insumo
                SET
                    cantidad = :cantidad,
                    costo_unitario = :costo
                WHERE id_insumo = :id
                  AND id_usuario = :id_usuario
            ");

            $stmtActualizar->execute([
                ':cantidad' => $nuevoStock,
                ':costo' => $nuevoCostoPromedio,
                ':id' => $datos['id_insumo'],
                ':id_usuario' => $idUsuario
            ]);

            /**
             * Registrar el movimiento de entrada.
             * El costo unitario queda como valor histórico.
             */
            $stmtMovimiento = $this->db->prepare("
                INSERT INTO movimiento_insumo
                (
                    id_insumo,
                    id_usuario,
                    tipo_movimiento,
                    operacion,
                    cantidad,
                    costo_unitario,
                    id_gasto,
                    observacion
                )
                VALUES
                (
                    :id_insumo,
                    :id_usuario,
                    'entrada',
                    'entrada',
                    :cantidad,
                    :costo_unitario,
                    :id_gasto,
                    :observacion
                )
            ");

            $stmtMovimiento->execute([
                ':id_insumo' => $datos['id_insumo'],
                ':id_usuario' => $idUsuario,
                ':cantidad' => $cantidadNueva,
                ':costo_unitario' => $costoCompraTotal / $cantidadNueva,
                ':id_gasto' => $idGasto,
                ':observacion' => $datos['observacion'] !== ''
                    ? $datos['observacion']
                    : 'Compra registrada'
            ]);

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /* ==========================================================
     * CONSUMOS Y AJUSTES
     * ========================================================== */

    /**
     * Registrar un consumo manual de insumo.
     *
     * El consumo reduce existencias, pero no genera un gasto.
     *
     * @param array $datos
     * @return bool
     */
    public function registrarConsumo(array $datos): bool
    {
        return $this->registrarMovimientoSalida(
            $datos,
            'consumo',
            'salida'
        );
    }

    /**
     * Registrar un ajuste de inventario.
     *
     * Un ajuste puede aumentar o disminuir las existencias.
     *
     * @param array $datos
     * @return bool
     */
    public function registrarAjuste(array $datos): bool
    {
        $operacion = $datos['operacion'];

        if (!in_array($operacion, ['entrada', 'salida'], true)) {
            throw new InvalidArgumentException('La operación de ajuste no es válida.');
        }

        if ($operacion === 'salida') {
            return $this->registrarMovimientoSalida(
                $datos,
                'ajuste',
                'salida'
            );
        }

        $idUsuario = $this->obtenerUsuario();

        try {
            $this->db->beginTransaction();

            $insumo = $this->bloquearInsumo($datos['id_insumo'], $idUsuario);

            if (!$insumo) {
                throw new RuntimeException('El insumo seleccionado no existe o está inactivo.');
            }

            $cantidad = (float) $datos['cantidad'];
            $nuevoStock = (float) $insumo['cantidad'] + $cantidad;

            $this->actualizarCantidad($datos['id_insumo'], $idUsuario, $nuevoStock);

            $this->insertarMovimiento(
                $datos['id_insumo'],
                $idUsuario,
                'ajuste',
                'entrada',
                $cantidad,
                null,
                null,
                $datos['observacion']
            );

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Ejecutar un movimiento de salida con bloqueo de existencias.
     *
     * @param array $datos
     * @param string $tipo
     * @param string $operacion
     * @return bool
     */
    private function registrarMovimientoSalida(
        array $datos,
        string $tipo,
        string $operacion
    ): bool {
        $idUsuario = $this->obtenerUsuario();

        try {
            $this->db->beginTransaction();

            $insumo = $this->bloquearInsumo($datos['id_insumo'], $idUsuario);

            if (!$insumo) {
                throw new RuntimeException('El insumo seleccionado no existe o está inactivo.');
            }

            $cantidad = (float) $datos['cantidad'];
            $stockActual = (float) $insumo['cantidad'];

            if ($cantidad > $stockActual) {
                throw new RuntimeException('La cantidad solicitada supera las existencias disponibles.');
            }

            $nuevoStock = $stockActual - $cantidad;

            $this->actualizarCantidad($datos['id_insumo'], $idUsuario, $nuevoStock);

            $this->insertarMovimiento(
                $datos['id_insumo'],
                $idUsuario,
                $tipo,
                $operacion,
                $cantidad,
                null,
                null,
                $datos['observacion']
            );

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Bloquear el registro del insumo durante una transacción.
     */
    private function bloquearInsumo(int $idInsumo, int $idUsuario): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id_insumo, nombre, cantidad
            FROM insumo
            WHERE id_insumo = :id
              AND id_usuario = :id_usuario
              AND activo = 1
            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $idInsumo,
            ':id_usuario' => $idUsuario
        ]);

        $insumo = $stmt->fetch();

        return $insumo ?: null;
    }

    /**
     * Actualizar únicamente la cantidad disponible.
     */
    private function actualizarCantidad(
        int $idInsumo,
        int $idUsuario,
        float $cantidad
    ): void {
        $stmt = $this->db->prepare("
            UPDATE insumo
            SET cantidad = :cantidad
            WHERE id_insumo = :id
              AND id_usuario = :id_usuario
        ");

        $stmt->execute([
            ':cantidad' => $cantidad,
            ':id' => $idInsumo,
            ':id_usuario' => $idUsuario
        ]);
    }

    /**
     * Insertar un movimiento de inventario.
     */
    private function insertarMovimiento(
        int $idInsumo,
        int $idUsuario,
        string $tipo,
        string $operacion,
        float $cantidad,
        ?float $costoUnitario,
        ?int $idGasto,
        string $observacion
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO movimiento_insumo
            (
                id_insumo,
                id_usuario,
                tipo_movimiento,
                operacion,
                cantidad,
                costo_unitario,
                id_gasto,
                observacion
            )
            VALUES
            (
                :id_insumo,
                :id_usuario,
                :tipo,
                :operacion,
                :cantidad,
                :costo,
                :id_gasto,
                :observacion
            )
        ");

        $stmt->execute([
            ':id_insumo' => $idInsumo,
            ':id_usuario' => $idUsuario,
            ':tipo' => $tipo,
            ':operacion' => $operacion,
            ':cantidad' => $cantidad,
            ':costo' => $costoUnitario,
            ':id_gasto' => $idGasto,
            ':observacion' => $observacion !== '' ? $observacion : null
        ]);
    }

    /* ==========================================================
     * HISTORIAL DE MOVIMIENTOS
     * ========================================================== */

    /**
     * Obtener los movimientos recientes del usuario.
     *
     * @param int $limite
     * @return array
     */
    public function obtenerMovimientos(int $limite = 100): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                m.id_movimiento,
                m.id_insumo,
                i.nombre AS insumo,
                i.unidad_medida,
                m.tipo_movimiento,
                m.operacion,
                m.cantidad,
                m.costo_unitario,
                m.id_gasto,
                m.observacion,
                m.fecha
            FROM movimiento_insumo m
            INNER JOIN insumo i
                ON i.id_insumo = m.id_insumo
               AND i.id_usuario = m.id_usuario
            WHERE m.id_usuario = :id_usuario
            ORDER BY m.fecha DESC, m.id_movimiento DESC
            LIMIT :limite
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /* ==========================================================
     * CATEGORÍAS Y PRODUCTOS PARA FORMULARIOS
     * ========================================================== */

    /**
     * Obtener productos activos para configurar recetas.
     *
     * @return array
     */
    public function obtenerProductos(): array
    {
        $idUsuario = $this->obtenerUsuario();

        $stmt = $this->db->prepare("
            SELECT id_producto, nombre
            FROM producto
            WHERE id_usuario = :id_usuario
              AND activo = 1
            ORDER BY nombre ASC
        ");

        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetchAll();
    }

    /* ==========================================================
     * RECETAS
     * ========================================================== */

    /**
     * Obtener el listado de productos que ya tienen una receta.
     *
     * Incluye la cantidad de ingredientes y el costo estimado
     * calculado con el costo promedio actual de cada insumo.
     *
     * @return array
     */
    public function obtenerRecetas(): array
    {
        $idUsuario = $this->obtenerUsuario();

        $stmt = $this->db->prepare("
            SELECT
                p.id_producto,
                p.nombre AS producto,
                COUNT(pi.id_insumo) AS total_ingredientes,
                COALESCE(SUM(pi.cantidad_necesaria * i.costo_unitario), 0) AS costo_estimado
            FROM producto p
            INNER JOIN producto_insumo pi
                ON pi.id_producto = p.id_producto
               AND pi.id_usuario = p.id_usuario
            INNER JOIN insumo i
                ON i.id_insumo = pi.id_insumo
               AND i.id_usuario = pi.id_usuario
            WHERE p.id_usuario = :id_usuario
              AND p.activo = 1
            GROUP BY p.id_producto, p.nombre
            ORDER BY p.nombre ASC
        ");

        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetchAll();
    }

    /**
     * Eliminar la receta de un producto sin eliminar el producto.
     *
     * @param int $idProducto
     * @return bool
     */
    public function eliminarReceta(int $idProducto): bool
    {
        $idUsuario = $this->obtenerUsuario();

        $stmt = $this->db->prepare("
            DELETE FROM producto_insumo
            WHERE id_producto = :id_producto
              AND id_usuario = :id_usuario
        ");

        $stmt->execute([
            ':id_producto' => $idProducto,
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Obtener los insumos de la receta de un producto.
     *
     * @param int $idProducto
     * @return array
     */
    public function obtenerReceta(int $idProducto): array
    {
        $idUsuario = $this->obtenerUsuario();

        $stmt = $this->db->prepare("
            SELECT
                pi.id_producto,
                pi.id_insumo,
                pi.cantidad_necesaria,
                i.nombre,
                i.unidad_medida,
                i.costo_unitario,
                (pi.cantidad_necesaria * i.costo_unitario) AS costo_estimado
            FROM producto_insumo pi
            INNER JOIN insumo i
                ON i.id_insumo = pi.id_insumo
               AND i.id_usuario = pi.id_usuario
            INNER JOIN producto p
                ON p.id_producto = pi.id_producto
               AND p.id_usuario = pi.id_usuario
            WHERE pi.id_producto = :id_producto
              AND pi.id_usuario = :id_usuario
              AND i.activo = 1
            ORDER BY i.nombre ASC
        ");

        $stmt->execute([
            ':id_producto' => $idProducto,
            ':id_usuario' => $idUsuario
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Guardar la composición de la receta de un producto.
     *
     * Se reemplazan sus asociaciones actuales dentro de una
     * transacción para evitar recetas guardadas parcialmente.
     *
     * @param int $idProducto
     * @param array $ingredientes
     * @return bool
     */
    public function guardarReceta(int $idProducto, array $ingredientes, bool $permitirEdicion = false): bool
    {
        $idUsuario = $this->obtenerUsuario();

        try {
            $this->db->beginTransaction();

            $stmtProducto = $this->db->prepare("
                SELECT id_producto
                FROM producto
                WHERE id_producto = :id_producto
                  AND id_usuario = :id_usuario
                  AND activo = 1
                FOR UPDATE
            ");

            $stmtProducto->execute([
                ':id_producto' => $idProducto,
                ':id_usuario' => $idUsuario
            ]);

            if (!$stmtProducto->fetch()) {
                throw new RuntimeException('El producto seleccionado no existe o está inactivo.');
            }

            $stmtExistente = $this->db->prepare("
                SELECT 1
                FROM producto_insumo
                WHERE id_producto = :id_producto
                  AND id_usuario = :id_usuario
                LIMIT 1
            ");

            $stmtExistente->execute([
                ':id_producto' => $idProducto,
                ':id_usuario' => $idUsuario
            ]);

            $tieneReceta = (bool) $stmtExistente->fetchColumn();

            if ($tieneReceta && !$permitirEdicion) {
                throw new RuntimeException('Este producto ya tiene una receta configurada. Utiliza la opción Editar para modificarla.');
            }

            if (!$tieneReceta && $permitirEdicion) {
                throw new RuntimeException('La receta que intentas editar ya no existe. Actualiza el listado e inténtalo nuevamente.');
            }

            $stmtEliminar = $this->db->prepare("
                DELETE FROM producto_insumo
                WHERE id_producto = :id_producto
                  AND id_usuario = :id_usuario
            ");

            $stmtEliminar->execute([
                ':id_producto' => $idProducto,
                ':id_usuario' => $idUsuario
            ]);

            $stmtInsertar = $this->db->prepare("
                INSERT INTO producto_insumo
                (
                    id_producto,
                    id_insumo,
                    id_usuario,
                    cantidad_necesaria
                )
                VALUES
                (
                    :id_producto,
                    :id_insumo,
                    :id_usuario,
                    :cantidad
                )
            ");

            $stmtInsumo = $this->db->prepare("
                SELECT id_insumo
                FROM insumo
                WHERE id_insumo = :id_insumo
                  AND id_usuario = :id_usuario
                  AND activo = 1
                LIMIT 1
            ");

            foreach ($ingredientes as $ingrediente) {
                $stmtInsumo->execute([
                    ':id_insumo' => $ingrediente['id_insumo'],
                    ':id_usuario' => $idUsuario
                ]);

                if (!$stmtInsumo->fetch()) {
                    throw new RuntimeException('Uno de los insumos de la receta no existe o está inactivo.');
                }

                $stmtInsertar->execute([
                    ':id_producto' => $idProducto,
                    ':id_insumo' => $ingrediente['id_insumo'],
                    ':id_usuario' => $idUsuario,
                    ':cantidad' => $ingrediente['cantidad_necesaria']
                ]);
            }

            $this->db->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
