<?php

declare(strict_types=1);

require_once __DIR__ . '/FinanzasService.php';
require_once ROOT_PATH . '/app/models/GastosModel.php';

/**
 * Servicio del módulo Gastos.
 * Conserva las reglas comunes y fija el contexto del módulo.
 */
class GastosService extends FinanzasService
{
    public function __construct()
    {
        parent::__construct(new GastosModel());
    }

    public function consultar(array $filtros = []): array
    {
        return parent::consultarModulo('gastos', $filtros);
    }

}
