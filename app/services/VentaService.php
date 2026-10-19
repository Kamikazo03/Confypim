<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/models/VentaModel.php';
require_once ROOT_PATH . '/app/helpers/AuthHelper.php';

/**
 * Excepción para errores relacionados con las reglas del negocio.
 */
class VentaBusinessException extends RuntimeException
{
    private int $httpStatus;

    public function __construct(
        string $message,
        int $httpStatus = 409
    ) {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}

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
     * Obtiene las ventas del usuario autenticado.
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
     */
    public function obtenerVenta(int $idVenta): array
    {
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

        $venta = $this->ventaModel->obtenerCompleta(
            $idVenta,
            $idUsuario
        );

        if (!$venta) {
            throw new VentaBusinessException(
                'Venta no encontrada.',
                404
            );
        }

        return $venta;
    }

    /**
     * Crea una venta pendiente sin descontar stock.
     */
    public function crearVenta(array $datos): array
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $idCliente = isset($datos['id_cliente'])
            && $datos['id_cliente'] !== ''
                ? (int) $datos['id_cliente']
                : null;

        $fechaVenta = $this->normalizarFecha(
            $datos['fecha_venta'] ?? null
        );

        $detalles = $datos['detalles'] ?? [];

        if (!is_array($detalles)) {
            throw new InvalidArgumentException(
                'Los detalles de la venta deben enviarse como una lista.'
            );
        }

        $this->validarDatosGenerales(
            $idUsuario,
            $detalles
        );

        $detallesPreparados = $this->prepararDetalles(
            $detalles,
            $idUsuario
        );

        $total = $this->calcularTotal($detallesPreparados);

        try {
            $this->db->beginTransaction();

            $numeroVenta =
                $this->ventaModel->obtenerSiguienteNumeroVenta(
                    $idUsuario
                );

            $idVenta = $this->ventaModel->crearVenta(
                $fechaVenta,
                $total,
                $idUsuario,
                $numeroVenta,
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

            $venta = $this->ventaModel->obtenerCompleta(
                $idVenta,
                $idUsuario
            );

            if (!$venta) {
                throw new RuntimeException(
                    'No fue posible recuperar la venta creada.'
                );
            }

            return $venta;
        } catch (Throwable $e) {
            $this->revertirTransaccion();

            if (
                $e instanceof InvalidArgumentException
                || $e instanceof VentaBusinessException
            ) {
                throw $e;
            }

            throw $e;
        }
    }

    /**
     * Actualiza una venta pendiente y sus detalles.
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

        $ventaActual = $this->ventaModel->obtenerPorId(
            $idVenta,
            $idUsuario
        );

        if (!$ventaActual) {
            throw new VentaBusinessException(
                'Venta no encontrada.',
                404
            );
        }

        if ($ventaActual['estado'] !== 'Pendiente') {
            throw new VentaBusinessException(
                'Solo se pueden modificar ventas que estén Pendientes.'
            );
        }

        $idCliente = isset($datos['id_cliente'])
            && $datos['id_cliente'] !== ''
                ? (int) $datos['id_cliente']
                : null;

        $fechaVenta = $this->normalizarFecha(
            $datos['fecha_venta'] ?? $ventaActual['fecha_venta']
        );

        $detalles = $datos['detalles'] ?? [];

        if (!is_array($detalles)) {
            throw new InvalidArgumentException(
                'Los detalles de la venta deben enviarse como una lista.'
            );
        }

        $this->validarDatosGenerales(
            $idUsuario,
            $detalles
        );

        $detallesPreparados = $this->prepararDetalles(
            $detalles,
            $idUsuario
        );

        $total = $this->calcularTotal($detallesPreparados);

        try {
            $this->db->beginTransaction();

            $this->ventaModel->actualizarVenta(
                $idVenta,
                $idUsuario,
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

            $venta = $this->ventaModel->obtenerCompleta(
                $idVenta,
                $idUsuario
            );

            if (!$venta) {
                throw new RuntimeException(
                    'No fue posible recuperar la venta actualizada.'
                );
            }

            return $venta;
        } catch (Throwable $e) {
            $this->revertirTransaccion();

            if (
                $e instanceof InvalidArgumentException
                || $e instanceof VentaBusinessException
            ) {
                throw $e;
            }

            throw $e;
        }
    }

    /**
     * Marca una venta como pagada y descuenta el stock.
     */
    public function pagarVenta(int $idVenta): array
    {
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

            $venta = $this->ventaModel->obtenerPorId(
                $idVenta,
                $idUsuario
            );

            if (!$venta) {
                throw new VentaBusinessException(
                    'Venta no encontrada.',
                    404
                );
            }

            if ($venta['estado'] !== 'Pendiente') {
                throw new VentaBusinessException(
                    'Solo se pueden pagar ventas que estén Pendientes.'
                );
            }

            $detalles = $this->ventaModel->obtenerDetalle(
                $idVenta,
                $idUsuario
            );

            if (count($detalles) === 0) {
                throw new VentaBusinessException(
                    'La venta no contiene productos.'
                );
            }

            /*
             * Primero verificamos el stock de todos los productos.
             * No se descuenta nada hasta validar todos.
             */
            foreach ($detalles as $detalle) {
                $producto =
                    $this->ventaModel->obtenerProductoParaActualizacion(
                        (int) $detalle['id_producto'],
                        $idUsuario
                    );

                if (!$producto) {
                    throw new VentaBusinessException(
                        'El producto #' .
                        $detalle['id_producto'] .
                        ' no existe o no pertenece al usuario actual.'
                    );
                }

                if ((int) $producto['activo'] !== 1) {
                    throw new VentaBusinessException(
                        'El producto "' .
                        $producto['nombre'] .
                        '" está inactivo.'
                    );
                }

                if (
                    (int) $producto['stock_disponible']
                    < (int) $detalle['cantidad']
                ) {
                    throw new VentaBusinessException(
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
             * El stock solo se descuenta si todas las validaciones
             * anteriores fueron satisfactorias.
             */
            foreach ($detalles as $detalle) {
                $resultado = $this->ventaModel->descontarStock(
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

            $venta = $this->ventaModel->obtenerCompleta(
                $idVenta,
                $idUsuario
            );

            if (!$venta) {
                throw new RuntimeException(
                    'No fue posible recuperar la venta pagada.'
                );
            }

            return $venta;
        } catch (Throwable $e) {
            $this->revertirTransaccion();

            if (
                $e instanceof InvalidArgumentException
                || $e instanceof VentaBusinessException
            ) {
                throw $e;
            }

            throw $e;
        }
    }

    /**
     * Cancela una venta pendiente.
     * Las ventas pendientes no han descontado stock.
     */
    public function cancelarVenta(int $idVenta): array
    {
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

        $venta = $this->ventaModel->obtenerPorId(
            $idVenta,
            $idUsuario
        );

        if (!$venta) {
            throw new VentaBusinessException(
                'Venta no encontrada.',
                404
            );
        }

        if ($venta['estado'] !== 'Pendiente') {
            throw new VentaBusinessException(
                'Solo se pueden cancelar ventas Pendientes.'
            );
        }

        $this->ventaModel->actualizarEstado(
            $idVenta,
            $idUsuario,
            'Cancelada'
        );

        $ventaActualizada = $this->ventaModel->obtenerCompleta(
            $idVenta,
            $idUsuario
        );

        if (!$ventaActualizada) {
            throw new RuntimeException(
                'No fue posible recuperar la venta cancelada.'
            );
        }

        return $ventaActualizada;
    }

    /**
     * Obtiene las métricas del usuario autenticado.
     */
    public function obtenerMetricas(): array
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        $metricas = $this->ventaModel->obtenerMetricas(
            $idUsuario
        );

        $metricas['promedio_venta'] =
            $this->ventaModel->obtenerPromedioVenta(
                $idUsuario
            );

        return $metricas;
    }

    /**
     * Valida la información general de la venta.
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

        if (count($detalles) === 0) {
            throw new InvalidArgumentException(
                'La venta debe contener al menos un producto.'
            );
        }
    }

    /**
     * Valida y prepara los productos de una venta.
     * El precio siempre se obtiene de la base de datos.
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

            $idProducto = (int) (
                $detalle['id_producto'] ?? 0
            );

            $cantidad = (int) (
                $detalle['cantidad'] ?? 0
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

            if (isset($productosUsados[$idProducto])) {
                throw new InvalidArgumentException(
                    'No se puede repetir el mismo producto dentro de la venta.'
                );
            }

            $producto = $this->ventaModel->obtenerProducto(
                $idProducto,
                $idUsuario
            );

            if (!$producto) {
                throw new VentaBusinessException(
                    'El producto #' .
                    $idProducto .
                    ' no existe o no pertenece al usuario actual.'
                );
            }

            if ((int) $producto['activo'] !== 1) {
                throw new VentaBusinessException(
                    'El producto "' .
                    $producto['nombre'] .
                    '" está inactivo.'
                );
            }

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
     * Normaliza y valida la fecha de venta.
     */
    private function normalizarFecha(mixed $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return date('Y-m-d H:i:s');
        }

        if (!is_string($fecha)) {
            throw new InvalidArgumentException(
                'La fecha de venta no es válida.'
            );
        }

        $timestamp = strtotime($fecha);

        if ($timestamp === false) {
            throw new InvalidArgumentException(
                'La fecha de venta no es válida.'
            );
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Revierte la transacción si sigue abierta.
     */
    private function revertirTransaccion(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}