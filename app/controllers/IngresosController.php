<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/controllers/FinanzasController.php';
require_once ROOT_PATH . '/app/services/IngresosService.php';

/**
 * ==========================================================
 * Controlador del módulo Ingresos
 * ==========================================================
 * Atiende las consultas y operaciones del módulo de ingresos.
 * ==========================================================
 */
class IngresosController extends FinanzasController
{
    private IngresosService $serviceIngresos;

    public function __construct()
    {
        $this->serviceIngresos = new IngresosService();
        parent::__construct($this->serviceIngresos);
    }

    public function consultar(array $filtros = []): array
    {
        try {
            return [
                'success' => true,
                'data' => $this->serviceIngresos->consultar($filtros)
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        } catch (Throwable $e) {
            error_log(
                'IngresosController: ' . $e->getMessage() .
                ' en ' . $e->getFile() . ':' . $e->getLine()
            );

            return [
                'success' => false,
                'message' => 'No fue posible consultar los ingresos.',
                'http_status' => 500
            ];
        }
    }
}
