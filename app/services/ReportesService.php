<?php

declare(strict_types=1);

require_once __DIR__ . '/FinanzasService.php';
require_once ROOT_PATH . '/app/models/ReportesModel.php';

/**
 * Servicio del módulo Reportes.
 * Conserva las reglas comunes y fija el contexto del módulo.
 */
class ReportesService extends FinanzasService
{
    public function __construct()
    {
        parent::__construct(new ReportesModel());
    }

    public function consultarReportes(array $filtros = []): array
    {
        return parent::reportes($filtros);
    }

}
