<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/controllers/FinanzasController.php';
require_once ROOT_PATH . '/app/services/ReportesService.php';

/**
 * ==========================================================
 * Controlador del módulo Reportes
 * ==========================================================
 * Atiende las consultas y el almacenamiento de reportes
 * financieros históricos.
 * ==========================================================
 */
class ReportesController extends FinanzasController
{
    private ReportesService $serviceReportes;

    public function __construct()
    {
        $this->serviceReportes = new ReportesService();
        parent::__construct($this->serviceReportes);
    }

    public function reportes(array $filtros = []): array
    {
        try {
            return [
                'success' => true,
                'data' => $this->serviceReportes->consultarReportes($filtros)
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        } catch (Throwable $e) {
            error_log('ReportesController: ' . $e->getMessage());

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
                'data' => $this->serviceReportes->guardarReporte($datos)
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        } catch (Throwable $e) {
            error_log('ReportesController::guardarReporte: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No fue posible guardar el reporte.',
                'http_status' => 500
            ];
        }
    }
}
