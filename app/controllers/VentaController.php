<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/services/VentaService.php';

class VentaController
{
    private VentaService $ventaService;

    public function __construct()
    {
        $this->ventaService = new VentaService();
    }

    /**
     * Lista las ventas.
     */
    public function listarVentas(
        ?string $estado = null,
        ?string $busqueda = null
    ): array {
        try {
            return [
                'success' => true,
                'data' => $this->ventaService->listarVentas(
                    $estado,
                    $busqueda
                )
            ];
        } catch (Throwable $e) {
            return $this->manejarError(
                $e,
                'VentaController::listarVentas'
            );
        }
    }

    /**
     * Obtiene una venta completa.
     */
    public function obtenerVenta(int $idVenta): array
    {
        try {
            return [
                'success' => true,
                'data' => $this->ventaService->obtenerVenta($idVenta)
            ];
        } catch (Throwable $e) {
            return $this->manejarError(
                $e,
                'VentaController::obtenerVenta'
            );
        }
    }

    /**
     * Crea una venta.
     */
    public function crearVenta(array $datos): array
    {
        try {
            return [
                'success' => true,
                'message' => 'Venta creada correctamente.',
                'data' => $this->ventaService->crearVenta($datos)
            ];
        } catch (Throwable $e) {
            return $this->manejarError(
                $e,
                'VentaController::crearVenta'
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
        try {
            return [
                'success' => true,
                'message' => 'Venta actualizada correctamente.',
                'data' => $this->ventaService->actualizarVenta(
                    $idVenta,
                    $datos
                )
            ];
        } catch (Throwable $e) {
            return $this->manejarError(
                $e,
                'VentaController::actualizarVenta'
            );
        }
    }

    /**
     * Marca una venta como pagada.
     */
    public function pagarVenta(int $idVenta): array
    {
        try {
            return [
                'success' => true,
                'message' => 'Venta pagada correctamente.',
                'data' => $this->ventaService->pagarVenta($idVenta)
            ];
        } catch (Throwable $e) {
            return $this->manejarError(
                $e,
                'VentaController::pagarVenta'
            );
        }
    }

    /**
     * Cancela una venta pendiente.
     */
    public function cancelarVenta(int $idVenta): array
    {
        try {
            return [
                'success' => true,
                'message' => 'Venta cancelada correctamente.',
                'data' => $this->ventaService->cancelarVenta($idVenta)
            ];
        } catch (Throwable $e) {
            return $this->manejarError(
                $e,
                'VentaController::cancelarVenta'
            );
        }
    }

    /**
     * Obtiene las métricas del módulo.
     */
    public function obtenerMetricas(): array
    {
        try {
            return [
                'success' => true,
                'data' => $this->ventaService->obtenerMetricas()
            ];
        } catch (Throwable $e) {
            return $this->manejarError(
                $e,
                'VentaController::obtenerMetricas'
            );
        }
    }

    /**
     * Convierte las excepciones en respuestas seguras.
     */
    private function manejarError(
        Throwable $e,
        string $operacion
    ): array {
        if ($e instanceof InvalidArgumentException) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        }

        if ($e instanceof VentaBusinessException) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => $e->getHttpStatus()
            ];
        }

        error_log(
            $operacion . ': ' .
            get_class($e) . ': ' .
            $e->getMessage()
        );

        return [
            'success' => false,
            'message' => 'No fue posible procesar la solicitud.',
            'http_status' => 500
        ];
    }
}