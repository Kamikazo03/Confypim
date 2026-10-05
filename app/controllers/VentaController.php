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
        return [
            'success' => true,
            'data' => $this->ventaService->listarVentas(
                $estado,
                $busqueda
            )
        ];
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

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
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

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
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

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
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

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
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

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene las métricas.
     */
    public function obtenerMetricas(): array
    {
        try {

            return [
                'success' => true,
                'data' => $this->ventaService->obtenerMetricas()
            ];

        } catch (Throwable $e) {

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}