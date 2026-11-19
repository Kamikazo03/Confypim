<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/controllers/FinanzasController.php';
require_once ROOT_PATH . '/app/services/GastosService.php';

/**
 * ==========================================================
 * Controlador del módulo Gastos
 * ==========================================================
 * Atiende las consultas y operaciones del módulo de gastos.
 * ==========================================================
 */
class GastosController extends FinanzasController
{
    private GastosService $serviceGastos;

    public function __construct()
    {
        $this->serviceGastos = new GastosService();
        parent::__construct($this->serviceGastos);
    }

    public function consultar(array $filtros = []): array
    {
        try {
            return [
                'success' => true,
                'data' => $this->serviceGastos->consultar($filtros)
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_status' => 422
            ];
        } catch (Throwable $e) {
            error_log('GastosController: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No fue posible consultar los gastos.',
                'http_status' => 500
            ];
        }
    }
}
