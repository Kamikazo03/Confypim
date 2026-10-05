<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/models/VentaModel.php';

class VentaService
{
    private VentaModel $ventaModel;
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->ventaModel = new VentaModel($this->db);
    }

    /**
     * Lista las ventas visibles.
     */
    public function listarVentas(
        ?string $estado = null,
        ?string $busqueda = null
    ): array {
        return $this->ventaModel->obtenerTodos($estado, $busqueda);
    }

    /**
     * Obtiene una venta completa.
     */
    public function obtenerVenta(int $idVenta): array
    {
        if ($idVenta <= 0) {
            throw new InvalidArgumentException('El identificador de la venta no es válido.');
        }

        $venta = $this->ventaModel->obtenerCompleta($idVenta);

        if (!$venta) {
            throw new RuntimeException('Venta no encontrada.');
        }

        return $venta;
    }

    /**
     * Crea una nueva venta.
     *
     * La venta se crea como Pendiente.
     * El stock NO se descuenta todavía.
     */
    public function crearVenta(array $datos): array
    {
        $idUsuario = (int) ($datos['id_usuario'] ?? 1);
        $idCliente = isset($datos['id_cliente']) && $datos['id_cliente'] !== ''
            ? (int) $datos['id_cliente']
            : null;

        $fechaVenta = $this->normalizarFecha(
            $datos['fecha_venta'] ?? null
        );

        $detalles = $datos['detalles'] ?? [];

        $this->validarDatosGenerales(
            $idUsuario,
            $detalles
        );

        $detallesPreparados = $this->prepararDetalles($detalles);

        $total = $this->calcularTotal($detallesPreparados);

        try {
            $this->db->beginTransaction();

            $idVenta = $this->ventaModel->crearVenta(
                $fechaVenta,
                $total,
                $idUsuario,
                $idCliente,
                'Pendiente'
            );

            foreach ($detallesPreparados as $detalle) {
                $this->ventaModel->crearDetalle(
                    $idVenta,
                    $detalle['id_producto'],
                    $detalle['cantidad'],
                    $detalle['precio_unitario'],
                    $detalle['subtotal']
                );
            }

            $this->db->commit();

            return $this->ventaModel->obtenerCompleta($idVenta);

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw new RuntimeException(
                'No fue posible crear la venta: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Actualiza una venta pendiente.
     */
    public function actualizarVenta(int $idVenta, array $datos): array
    {
        if ($idVenta <= 0) {
            throw new InvalidArgumentException('El identificador de la venta no es válido.');
        }

        $ventaActual = $this->ventaModel->obtenerPorId($idVenta);

        if (!$ventaActual) {
            throw new RuntimeException('Venta no encontrada.');
        }

        if ($ventaActual['estado'] !== 'Pendiente') {
            throw new RuntimeException(
                'Solo se pueden modificar ventas que estén Pendientes.'
            );
        }

        $idCliente = isset($datos['id_cliente']) && $datos['id_cliente'] !== ''
            ? (int) $datos['id_cliente']
            : null;

        $fechaVenta = $this->normalizarFecha(
            $datos['fecha_venta'] ?? $ventaActual['fecha_venta']
        );

        $detalles = $datos['detalles'] ?? [];

        if (!is_array($detalles) || count($detalles) === 0) {
            throw new InvalidArgumentException(
                'La venta debe contener al menos un producto.'
            );
        }

        $detallesPreparados = $this->prepararDetalles($detalles);

        $total = $this->calcularTotal($detallesPreparados);

        try {
            $this->db->beginTransaction();

            $this->ventaModel->actualizarVenta(
                $idVenta,
                $fechaVenta,
                $total,
                $idCliente
            );

            $this->ventaModel->eliminarDetalles($idVenta);

            foreach ($detallesPreparados as $detalle) {
                $this->ventaModel->crearDetalle(
                    $idVenta,
                    $detalle['id_producto'],
                    $detalle['cantidad'],
                    $detalle['precio_unitario'],
                    $detalle['subtotal']
                );
            }

            $this->db->commit();

            return $this->ventaModel->obtenerCompleta($idVenta);

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw new RuntimeException(
                'No fue posible actualizar la venta: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Marca una venta como Pagada y descuenta el stock.
     */
    public function pagarVenta(int $idVenta): array
    {
        if ($idVenta <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la venta no es válido.'
            );
        }

        try {
            $this->db->beginTransaction();

            $venta = $this->ventaModel->obtenerPorId($idVenta);

            if (!$venta) {
                throw new RuntimeException('Venta no encontrada.');
            }

            if ($venta['estado'] !== 'Pendiente') {
                throw new RuntimeException(
                    'Solo se pueden pagar ventas que estén Pendientes.'
                );
            }

            $detalles = $this->ventaModel->obtenerDetalle($idVenta);

            if (count($detalles) === 0) {
                throw new RuntimeException(
                    'La venta no contiene productos.'
                );
            }

            /*
             * Primero verificamos todo el stock.
             * No descontamos parcialmente.
             */
            foreach ($detalles as $detalle) {

                $producto = $this->ventaModel
                    ->obtenerProductoParaActualizacion(
                        (int) $detalle['id_producto']
                    );

                if (!$producto) {
                    throw new RuntimeException(
                        'El producto #' . $detalle['id_producto'] . ' no existe.'
                    );
                }

                if ((int) $producto['activo'] !== 1) {
                    throw new RuntimeException(
                        'El producto "' . $producto['nombre'] . '" está inactivo.'
                    );
                }

                if (
                    (int) $producto['stock_disponible']
                    < (int) $detalle['cantidad']
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

            /*
             * Si todo el stock está disponible,
             * ahora sí hacemos los descuentos.
             */
            foreach ($detalles as $detalle) {

                $resultado = $this->ventaModel->descontarStock(
                    (int) $detalle['id_producto'],
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
                'Pagada'
            );

            $this->db->commit();

            return $this->ventaModel->obtenerCompleta($idVenta);

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw new RuntimeException(
                'No fue posible pagar la venta: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Cancela una venta pendiente.
     *
     * No se agrega devolución de stock porque las ventas pendientes
     * todavía no han descontado stock.
     */
    public function cancelarVenta(int $idVenta): array
    {
        if ($idVenta <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la venta no es válido.'
            );
        }

        $venta = $this->ventaModel->obtenerPorId($idVenta);

        if (!$venta) {
            throw new RuntimeException('Venta no encontrada.');
        }

        if ($venta['estado'] !== 'Pendiente') {
            throw new RuntimeException(
                'Solo se pueden cancelar ventas Pendientes.'
            );
        }

        $this->ventaModel->actualizarEstado(
            $idVenta,
            'Cancelada'
        );

        return $this->ventaModel->obtenerCompleta($idVenta);
    }

    /**
     * Obtiene las métricas del módulo.
     */
    public function obtenerMetricas(): array
    {
        $metricas = $this->ventaModel->obtenerMetricas();

        $metricas['promedio_venta'] =
            $this->ventaModel->obtenerPromedioVenta();

        return $metricas;
    }

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

        if (!is_array($detalles) || count($detalles) === 0) {
            throw new InvalidArgumentException(
                'La venta debe contener al menos un producto.'
            );
        }
    }

    /**
     * Prepara y valida los productos de la venta.
     */
    private function prepararDetalles(array $detalles): array
    {
        $resultado = [];
        $productosUsados = [];

        foreach ($detalles as $detalle) {

            if (!is_array($detalle)) {
                throw new InvalidArgumentException(
                    'Uno de los detalles de la venta no es válido.'
                );
            }

            $idProducto = (int) ($detalle['id_producto'] ?? 0);
            $cantidad = (int) ($detalle['cantidad'] ?? 0);

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

            if (isset($productosUsados[$idProducto])) {
                throw new InvalidArgumentException(
                    'No se puede repetir el mismo producto dentro de la venta.'
                );
            }

            $producto = $this->ventaModel->obtenerProducto($idProducto);

            if (!$producto) {
                throw new RuntimeException(
                    'El producto #' . $idProducto . ' no existe.'
                );
            }

            if ((int) $producto['activo'] !== 1) {
                throw new RuntimeException(
                    'El producto "' . $producto['nombre'] . '" está inactivo.'
                );
            }

            /*
             * El precio se obtiene de la BD.
             * El cliente no puede manipularlo desde JavaScript.
             */
            $precioUnitario = (float) $producto['precio_unitario'];

            $subtotal = $precioUnitario * $cantidad;

            $resultado[] = [
                'id_producto' => $idProducto,
                'cantidad' => $cantidad,
                'precio_unitario' => round($precioUnitario, 2),
                'subtotal' => round($subtotal, 2)
            ];

            $productosUsados[$idProducto] = true;
        }

        return $resultado;
    }

    /**
     * Calcula el total de la venta.
     */
    private function calcularTotal(array $detalles): float
    {
        $total = 0;

        foreach ($detalles as $detalle) {
            $total += (float) $detalle['subtotal'];
        }

        return round($total, 2);
    }

    /**
     * Normaliza la fecha recibida desde el frontend.
     */
    private function normalizarFecha(?string $fecha): string
    {
        if (!$fecha) {
            return date('Y-m-d H:i:s');
        }

        $timestamp = strtotime($fecha);

        if ($timestamp === false) {
            throw new InvalidArgumentException(
                'La fecha de venta no es válida.'
            );
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}