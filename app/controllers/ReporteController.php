<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/ReporteModel.php';

/**
 * ==========================================================
 * ReporteController
 * ==========================================================
 * Valida las solicitudes del módulo Reportes y coordina
 * las consultas y el guardado de reportes históricos.
 * ==========================================================
 */

class ReporteController
{
    private ReporteModel $reporteModel;

    public function __construct()
    {
        $this->reporteModel = new ReporteModel();
    }

    /**
     * Validar fechas recibidas desde la API.
     */
    private function validarPeriodo(array $datos): array
    {
        $inicio = trim((string) ($datos['inicio'] ?? ''));
        $fin = trim((string) ($datos['fin'] ?? ''));

        $fechaValida = static function (string $fecha): bool {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                return false;
            }

            [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));

            return checkdate($mes, $dia, $anio);
        };

        if (!$fechaValida($inicio) || !$fechaValida($fin)) {
            throw new InvalidArgumentException(
                'Debes seleccionar un período con fechas válidas.'
            );
        }

        if ($inicio > $fin) {
            throw new InvalidArgumentException(
                'La fecha inicial no puede ser posterior a la fecha final.'
            );
        }

        return [$inicio, $fin];
    }

    public function obtenerDatos(array $datos): array
    {
        try {
            [$inicio, $fin] = $this->validarPeriodo($datos);

            return [
                'success' => true,
                'data' => $this->reporteModel->obtenerDatosPeriodo($inicio, $fin)
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        } catch (Throwable $e) {
            error_log('ReporteController::obtenerDatos: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No fue posible consultar la información del período.',
                'http_status' => 500
            ];
        }
    }

    public function guardar(array $datos): array
    {
        try {
            [$inicio, $fin] = $this->validarPeriodo($datos);
            $idReporte = $this->reporteModel->guardarReporte($inicio, $fin);

            return [
                'success' => true,
                'message' => 'El reporte histórico se guardó correctamente.',
                'data' => ['id_reporte' => $idReporte],
                'http_status' => 201
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        } catch (Throwable $e) {
            error_log('ReporteController::guardar: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No fue posible guardar el reporte histórico.',
                'http_status' => 500
            ];
        }
    }

    public function obtenerHistorial(): array
    {
        try {
            return [
                'success' => true,
                'data' => $this->reporteModel->obtenerHistorial()
            ];
        } catch (Throwable $e) {
            error_log('ReporteController::obtenerHistorial: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No fue posible consultar el historial de reportes.',
                'http_status' => 500
            ];
        }
    }
}
