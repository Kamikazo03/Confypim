<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/services/FinanzasService.php';

/**
 * ==========================================================
 * Controlador financiero
 * ==========================================================
 * Centraliza las respuestas de los módulos de Ingresos,
 * Gastos y Reportes.
 * ==========================================================
 */
class FinanzasController
{
    private FinanzasService $service;

    public function __construct(?FinanzasService $service = null)
    {
        $this->service = $service ?? new FinanzasService();
    }

    public function consultarModulo(string $tipo, array $filtros = []): array
    {
        try {
            return [
                'success' => true,
                'data' => $this->service->consultar($tipo, $filtros)
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        } catch (Throwable $e) {
            error_log('FinanzasController::consultar: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No fue posible consultar la información.',
                'http_status' => 500
            ];
        }
    }

    public function reportes(array $filtros = []): array
    {
        try {
            return [
                'success' => true,
                'data' => $this->service->reportes($filtros)
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        } catch (Throwable $e) {
            error_log('FinanzasController::reportes: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No fue posible consultar los reportes.',
                'http_status' => 500
            ];
        }
    }

    public function guardarReporte(array $datos): array
    {
        try {
            return [
                'success' => true,
                'message' => 'Reporte guardado correctamente.',
                'data' => $this->service->guardarReporte($datos)
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        } catch (Throwable $e) {
            error_log('FinanzasController::guardarReporte: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No fue posible guardar el reporte.',
                'http_status' => 500
            ];
        }
    }
}
