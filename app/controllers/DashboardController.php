<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/ProductoModel.php';

/**==========================================================
 * DashboardController
 * ==========================================================
 * Controlador encargado de gestionar la información que se
 * muestra en el Dashboard principal.
 *
 * Obtiene los indicadores principales del Dashboard y prepara
 * los listados resumidos, además coordina la información
 * proveniente de los modelos.
 * ==========================================================*/

class DashboardController
{
    /**
     * Modelo encargado de la gestión de productos.
     */
    private ProductoModel $productoModel;

    /**
     * Crea una nueva instancia del controlador e inicializa
     * los modelos necesarios para el Dashboard.
     */
    public function __construct()
    {
        $this->productoModel = new ProductoModel();
    }

    /* ==========================================================
     * TARJETAS DEL DASHBOARD
     * ========================================================== */

    /**
     * Obtener la cantidad total de productos.
     *
     * @return int
     */
    public function contarProductos(): int
    {
        return $this->productoModel->contarProductos();
    }

    /**
     * Obtener la cantidad de productos con stock bajo.
     *
     * @return int
     */
    public function contarStockBajo(): int
    {
        return $this->productoModel->contarStockBajo();
    }

    /* ==========================================================
     * LISTADOS
     * ========================================================== */

    /**
     * Obtener los últimos productos registrados.
     *
     * @param int $limite
     * @return array
     */
    public function listarUltimosProductos(int $limite = 5): array
    {
        $productos = $this->productoModel->obtenerUltimos($limite);

        foreach ($productos as &$producto) {
            $producto['estado'] = $this->obtenerEstadoStock(
                (int)$producto['stock_disponible']
            );
        }
        unset($producto);

        return $productos;
    }

    /* ==========================================================
     * MÉTODOS PRIVADOS
     * ========================================================== */

    /**
     * Determinar el estado del stock de un producto.
     *
     * @param int $stock
     * @return string
     */
    private function obtenerEstadoStock(int $stock): string
    {
        if ($stock === 0) {
            return 'Agotado';
        }

        if ($stock <= 5) {
            return 'Stock Bajo';
        }
        
        return 'Disponible';
    }

}