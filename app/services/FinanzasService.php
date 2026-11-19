<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/models/FinanzasModel.php';
require_once ROOT_PATH . '/app/helpers/AuthHelper.php';

/**
 * ==========================================================
 * Servicio financiero
 * ==========================================================
 * Centraliza las validaciones y reglas compartidas por los
 * módulos de Ingresos, Gastos y Reportes.
 * ==========================================================
 */
class FinanzasService
{
    private FinanzasModel $model;

    public function __construct(?FinanzasModel $model = null)
    {
        $this->model = $model ?? new FinanzasModel();
    }

    private function validarTipo(string $tipo): string
    {
        if (!in_array($tipo, ['ingresos', 'gastos'], true)) {
            throw new InvalidArgumentException('El módulo solicitado no es válido.');
        }

        return $tipo;
    }

    private function validarFecha(?string $fecha): ?string
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        $date = DateTime::createFromFormat('Y-m-d', $fecha);

        if (!$date || $date->format('Y-m-d') !== $fecha) {
            throw new InvalidArgumentException('Una de las fechas no tiene un formato válido.');
        }

        return $fecha;
    }

    private function validarRango(?string $desde, ?string $hasta): array
    {
        $desde = $this->validarFecha($desde);
        $hasta = $this->validarFecha($hasta);

        if ($desde && $hasta && $desde > $hasta) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la fecha final.');
        }

        return [$desde, $hasta];
    }

    public function consultarModulo(string $tipo, array $filtros): array
    {
        $tipo = $this->validarTipo($tipo);

        [$desde, $hasta] = $this->validarRango(
            $filtros['desde'] ?? null,
            $filtros['hasta'] ?? null
        );

        $categoria = isset($filtros['categoria']) && ctype_digit((string) $filtros['categoria'])
            ? (int) $filtros['categoria']
            : null;

        $busqueda = trim((string) ($filtros['busqueda'] ?? ''));

        return [
            'resumen' => $this->model->obtenerResumen($tipo, $desde, $hasta),
            'movimientos' => $this->model->listarMovimientos(
                $tipo,
                $desde,
                $hasta,
                $categoria,
                $busqueda
            ),
            'categorias' => $this->model->obtenerCategorias($tipo),
            'productos' => $tipo === 'ingresos'
                ? $this->model->obtenerProductos($desde, $hasta)
                : []
        ];
    }

    public function reportes(array $filtros): array
    {
        [$desde, $hasta] = $this->validarRango(
            $filtros['desde'] ?? null,
            $filtros['hasta'] ?? null
        );

        return [
            'totales' => $this->model->obtenerTotales($desde, $hasta),
            'balance' => $this->model->obtenerBalance($desde, $hasta),
            'historial' => $this->model->listarReportes()
        ];
    }

    public function guardarReporte(array $datos): array
    {
        [$desde, $hasta] = $this->validarRango(
            $datos['desde'] ?? null,
            $datos['hasta'] ?? null
        );

        if (!$desde || !$hasta) {
            throw new InvalidArgumentException('Selecciona las fechas inicial y final para guardar el reporte.');
        }

        return [
            'id_reporte' => $this->model->guardarReporte($desde, $hasta)
        ];
    }
}
