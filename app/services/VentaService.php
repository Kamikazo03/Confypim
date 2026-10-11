<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/models/VentaModel.php';
require_once ROOT_PATH . '/app/helpers/AuthHelper.php';

class VentaService
{
    private VentaModel $ventaModel;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();

        $this->ventaModel =
            new VentaModel($this->db);
    }

    /**
     * Lista las ventas visibles.
     *
     * Cada administrador consulta únicamente
     * sus propias ventas.
     */
    public function listarVentas(
        ?string $estado = null,
        ?string $busqueda = null
    ): array {

        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        return $this->ventaModel->obtenerTodos(
            $idUsuario,
            $estado,
            $busqueda
        );
    }

    /**
     * Obtiene una venta completa.
     *
     * Solo permite consultar ventas pertenecientes
     * al administrador autenticado.
     */
    public function obtenerVenta(
        int $idVenta
    ): array {

        if ($idVenta <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la venta no es válido.'
            );
        }

        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $venta =
            $this->ventaModel->obtenerCompleta(
                $idVenta,
                $idUsuario
            );

        if (!$venta) {
            throw new RuntimeException(
                'Venta no encontrada.'
            );
        }

        return $venta;
    }

    /**
     * Crea una nueva venta.
     *
     * La venta se crea como Pendiente.
     *
     * El stock NO se descuenta todavía.
     *
     * El usuario responsable se obtiene directamente
     * desde la sesión del administrador autenticado.
     *
     * numero_venta se genera automáticamente como
     * consecutivo independiente para cada administrador.
     */
    public function crearVenta(
        array $datos
    ): array {

        /**
         * El usuario NO se recibe desde React.
         *
         * Se obtiene directamente de la sesión para evitar
         * que un cliente pueda enviar manualmente otro
         * id_usuario.
         */
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $idCliente =
            isset($datos['id_cliente']) &&
            $datos['id_cliente'] !== ''
                ? (int) $datos['id_cliente']
                : null;

        $fechaVenta =
            $this->normalizarFecha(
                $datos['fecha_venta'] ?? null
            );

        $detalles =
            $datos['detalles'] ?? [];

        $this->validarDatosGenerales(
            $idUsuario,
            $detalles
        );

        $detallesPreparados =
            $this->prepararDetalles(
                $detalles,
                $idUsuario
            );

        $total =
            $this->calcularTotal(
                $detallesPreparados
            );

        try {

            $this->db->beginTransaction();

            /**
             * Obtiene el siguiente número correspondiente
             * exclusivamente al administrador autenticado.
             */
            $numeroVenta =
                $this->ventaModel
                    ->obtenerSiguienteNumeroVenta(
                        $idUsuario
                    );

            $idVenta =
                $this->ventaModel->crearVenta(
                    $fechaVenta,
                    $total,
                    $idUsuario,
                    $numeroVenta,
                    $idCliente,
                    'Pendiente'
                );

            foreach (
                $detallesPreparados as $detalle
            ) {

                $this->ventaModel->crearDetalle(
                    $idVenta,
                    $detalle['id_producto'],
                    $detalle['cantidad'],
                    $detalle['precio_unitario'],
                    $detalle['subtotal']
                );
            }

            $this->db->commit();

            return $this->ventaModel
                ->obtenerCompleta(
                    $idVenta,
                    $idUsuario
                );

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw new RuntimeException(
                'No fue posible crear la venta: ' .
                $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Actualiza una venta pendiente.
     */
    public function actualizarVenta(
        int $idVenta,
        array $datos
    ): array {

        if ($idVenta <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la venta no es válido.'
            );
        }

        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        /**
         * obtenerPorId() también valida el usuario.
         *
         * Por lo tanto, un administrador no puede modificar
         * una venta perteneciente a otro administrador.
         */
        $ventaActual =
            $this->ventaModel->obtenerPorId(
                $idVenta,
                $idUsuario
            );

        if (!$ventaActual) {
            throw new RuntimeException(
                'Venta no encontrada.'
            );
        }

        if (
            $ventaActual['estado'] !==
            'Pendiente'
        ) {
            throw new RuntimeException(
                'Solo se pueden modificar ventas que estén Pendientes.'
            );
        }

        $idCliente =
            isset($datos['id_cliente']) &&
            $datos['id_cliente'] !== ''
                ? (int) $datos['id_cliente']
                : null;

        $fechaVenta =
            $this->normalizarFecha(
                $datos['fecha_venta']
                ?? $ventaActual['fecha_venta']
            );

        $detalles =
            $datos['detalles'] ?? [];

        if (
            !is_array($detalles) ||
            count($detalles) === 0
        ) {
            throw new InvalidArgumentException(
                'La venta debe contener al menos un producto.'
            );
        }

        $detallesPreparados =
            $this->prepararDetalles(
                $detalles,
                $idUsuario
            );

        $total =
            $this->calcularTotal(
                $detallesPreparados
            );

        try {

            $this->db->beginTransaction();

            $this->ventaModel->actualizarVenta(
                $idVenta,
                $idUsuario,
                $fechaVenta,
                $total,
                $idCliente
            );

            $this->ventaModel->eliminarDetalles(
                $idVenta
            );

            foreach (
                $detallesPreparados as $detalle
            ) {

                $this->ventaModel->crearDetalle(
                    $idVenta,
                    $detalle['id_producto'],
                    $detalle['cantidad'],
                    $detalle['precio_unitario'],
                    $detalle['subtotal']
                );
            }

            $this->db->commit();

            return $this->ventaModel
                ->obtenerCompleta(
                    $idVenta,
                    $idUsuario
                );

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw new RuntimeException(
                'No fue posible actualizar la venta: ' .
                $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Marca una venta como Pagada y descuenta el stock.
     */
    public function pagarVenta(
        int $idVenta
    ): array {

        if ($idVenta <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la venta no es válido.'
            );
        }

        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        try {

            $this->db->beginTransaction();

            $venta =
                $this->ventaModel->obtenerPorId(
                    $idVenta,
                    $idUsuario
                );

            if (!$venta) {
                throw new RuntimeException(
                    'Venta no encontrada.'
                );
            }

            if (
                $venta['estado'] !==
                'Pendiente'
            ) {
                throw new RuntimeException(
                    'Solo se pueden pagar ventas que estén Pendientes.'
                );
            }

            $detalles =
                $this->ventaModel->obtenerDetalle(
                    $idVenta,
                    $idUsuario
                );

            if (count($detalles) === 0) {
                throw new RuntimeException(
                    'La venta no contiene productos.'
                );
            }

            /**
             * Primero verificamos todo el stock.
             *
             * No descontamos parcialmente.
             */
            foreach ($detalles as $detalle) {

                $producto =
                    $this->ventaModel
                        ->obtenerProductoParaActualizacion(
                            (int) $detalle['id_producto'],
                            $idUsuario
                        );

                if (!$producto) {
                    throw new RuntimeException(
                        'El producto #' .
                        $detalle['id_producto'] .
                        ' no existe o no pertenece al usuario actual.'
                    );
                }

                if (
                    (int) $producto['activo'] !==
                    1
                ) {
                    throw new RuntimeException(
                        'El producto "' .
                        $producto['nombre'] .
                        '" está inactivo.'
                    );
                }

                if (
                    (int) $producto['stock_disponible']
                    <
                    (int) $detalle['cantidad']
                ) {

                    throw new RuntimeException(
                        'Stock insuficiente para "' .
                        $producto['nombre'] .
                        '". Disponible: ' .
                        $producto['stock_disponible'] .
                        ', solicitado: ' .
                        $detalle['cantidad'] .
                        '.'
                    );
                }
            }

            /**
             * Si todo el stock está disponible,
             * ahora sí hacemos los descuentos.
             */
            foreach ($detalles as $detalle) {

                $resultado =
                    $this->ventaModel->descontarStock(
                        (int) $detalle['id_producto'],
                        $idUsuario,
                        (int) $detalle['cantidad']
                    );

                if (!$resultado) {
                    throw new RuntimeException(
                        'No fue posible actualizar el stock del producto #' .
                        $detalle['id_producto'] .
                        '.'
                    );
                }
            }

            $this->ventaModel->actualizarEstado(
                $idVenta,
                $idUsuario,
                'Pagada'
            );

            $this->db->commit();

            return $this->ventaModel
                ->obtenerCompleta(
                    $idVenta,
                    $idUsuario
                );

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw new RuntimeException(
                'No fue posible pagar la venta: ' .
                $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Cancela una venta pendiente.
     *
     * No se agrega devolución de stock porque las ventas
     * pendientes todavía no han descontado stock.
     */
    public function cancelarVenta(
        int $idVenta
    ): array {

        if ($idVenta <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la venta no es válido.'
            );
        }

        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $venta =
            $this->ventaModel->obtenerPorId(
                $idVenta,
                $idUsuario
            );

        if (!$venta) {
            throw new RuntimeException(
                'Venta no encontrada.'
            );
        }

        if (
            $venta['estado'] !==
            'Pendiente'
        ) {
            throw new RuntimeException(
                'Solo se pueden cancelar ventas Pendientes.'
            );
        }

        $this->ventaModel->actualizarEstado(
            $idVenta,
            $idUsuario,
            'Cancelada'
        );

        return $this->ventaModel
            ->obtenerCompleta(
                $idVenta,
                $idUsuario
            );
    }

    /**
     * Obtiene las métricas del módulo.
     *
     * Las métricas corresponden únicamente
     * al administrador autenticado.
     */
    public function obtenerMetricas(): array
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $metricas =
            $this->ventaModel->obtenerMetricas(
                $idUsuario
            );

        $metricas['promedio_venta'] =
            $this->ventaModel
                ->obtenerPromedioVenta(
                    $idUsuario
                );

        return $metricas;
    }

    /* ==========================================================
     * VALIDACIONES
     * ========================================================== */

    /**
     * Valida la información general.
     */
    private function validarDatosGenerales(
        int $idUsuario,
        array $detalles
    ): void {

        if ($idUsuario <= 0) {
            throw new InvalidArgumentException(
                'El usuario de la venta no es válido.'
            );
        }

        if (
            !is_array($detalles) ||
            count($detalles) === 0
        ) {
            throw new InvalidArgumentException(
                'La venta debe contener al menos un producto.'
            );
        }
    }

    /**
     * Prepara y valida los productos de la venta.
     */
    private function prepararDetalles(
        array $detalles,
        int $idUsuario
    ): array {

        $resultado = [];

        $productosUsados = [];

        foreach ($detalles as $detalle) {

            if (!is_array($detalle)) {
                throw new InvalidArgumentException(
                    'Uno de los detalles de la venta no es válido.'
                );
            }

            $idProducto =
                (int) (
                    $detalle['id_producto']
                    ?? 0
                );

            $cantidad =
                (int) (
                    $detalle['cantidad']
                    ?? 0
                );

            if ($idProducto <= 0) {
                throw new InvalidArgumentException(
                    'Existe un producto con identificador inválido.'
                );
            }

            if ($cantidad <= 0) {
                throw new InvalidArgumentException(
                    'La cantidad de cada producto debe ser mayor que cero.'
                );
            }

            if (
                isset(
                    $productosUsados[$idProducto]
                )
            ) {
                throw new InvalidArgumentException(
                    'No se puede repetir el mismo producto dentro de la venta.'
                );
            }

            /**
             * IMPORTANTE:
             *
             * La consulta ahora exige que el producto
             * pertenezca al administrador autenticado.
             */
            $producto =
                $this->ventaModel
                    ->obtenerProducto(
                        $idProducto,
                        $idUsuario
                    );

            if (!$producto) {
                throw new RuntimeException(
                    'El producto #' .
                    $idProducto .
                    ' no existe o no pertenece al usuario actual.'
                );
            }

            if (
                (int) $producto['activo'] !==
                1
            ) {
                throw new RuntimeException(
                    'El producto "' .
                    $producto['nombre'] .
                    '" está inactivo.'
                );
            }

            /**
             * El precio se obtiene de la BD.
             *
             * El cliente no puede manipularlo
             * desde JavaScript.
             */
            $precioUnitario =
                (float) $producto['precio_unitario'];

            $subtotal =
                $precioUnitario *
                $cantidad;

            $resultado[] = [
                'id_producto' =>
                    $idProducto,

                'cantidad' =>
                    $cantidad,

                'precio_unitario' =>
                    round(
                        $precioUnitario,
                        2
                    ),

                'subtotal' =>
                    round(
                        $subtotal,
                        2
                    )
            ];

            $productosUsados[$idProducto] =
                true;
        }

        return $resultado;
    }

    /**
     * Calcula el total de la venta.
     */
    private function calcularTotal(
        array $detalles
    ): float {

        $total = 0;

        foreach ($detalles as $detalle) {

            $total +=
                (float) $detalle['subtotal'];
        }

        return round($total, 2);
    }

    /**
     * Normaliza la fecha recibida desde el frontend.
     */
    private function normalizarFecha(
        ?string $fecha
    ): string {

        if (!$fecha) {
            return date('Y-m-d H:i:s');
        }

        $timestamp =
            strtotime($fecha);

        if ($timestamp === false) {
            throw new InvalidArgumentException(
                'La fecha de venta no es válida.'
            );
        }

        return date(
            'Y-m-d H:i:s',
            $timestamp
        );
    }
}