<?php

declare(strict_types=1);

require_once __DIR__ . '/FinanzasService.php';
require_once ROOT_PATH . '/app/models/IngresosModel.php';

/**
 * Servicio del módulo Ingresos.
 * Conserva las reglas comunes y fija el contexto del módulo.
 */
class IngresosService extends FinanzasService
{
    public function __construct()
    {
        parent::__construct(new IngresosModel());
    }

    public function consultar(array $filtros = []): array
    {
        return parent::consultarModulo('ingresos', $filtros);
    }

}
