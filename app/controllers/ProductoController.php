<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/ProductoModel.php';

/**==========================================================
 * ProductoController
 * ==========================================================
 * Controlador encargado de gestionar toda la lógica de negocio
 * relacionada con los productos.
 *
 * Actúa como intermediario entre las vistas y el modelo,
 * procesando las solicitudes y retornando la información
 * necesaria para cada módulo del sistema.
 * ==========================================================
 */

class ProductoController
{
    /**
     * Instancia del modelo de productos.
     */
    private ProductoModel $productoModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->productoModel = new ProductoModel();
    }

    /* ==========================================================
     * CONSULTAS
     * ==========================================================*/

    /**
     * Obtener el listado completo de productos.
     *
     * @return array
     */
    public function listarProductos(): array
    {
        return $this->productoModel->obtenerTodos();
    }

    /**
     * Obtener un producto por su ID.
     *
     * @param int $id
     * @return array
     */
    public function obtenerProducto(int $id): array
    {
        $producto = $this->productoModel->obtenerPorId($id);
        if ($producto) {
            return [
                "success" => true,
                "data" => $producto
            ];
        }
        return [
            "success" => false,
            "message" => "Producto no encontrado."
        ];
    }

    /* ==========================================================
     * DASHBOARD
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
     * Obtener los últimos productos registrados.
     *
     * @param int $limite
     * @return array
     */
    public function listarUltimosProductos(int $limite = 5): array
    {
        $productos = $this->productoModel->obtenerUltimos($limite);

        foreach ($productos as &$producto) {
            if ($producto['stock_disponible'] == 0) {
                $producto['estado'] = "Agotado";
            } elseif ($producto['stock_disponible'] <= 5) {
                $producto['estado'] = "Stock Bajo";
            } else {
                $producto['estado'] = "Disponible";
            }
        }
        return $productos;
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
     * CRUD
     * ========================================================== */

    /**
     * Registrar un nuevo producto.
     *
     * @param array $datos
     * @return array
     */
    public function crearProducto(array $datos): array
    {
        $resultado = $this->productoModel->crear($datos);
        if ($resultado) {
            return [
                'success' => true,
                'message' => 'Producto registrado correctamente.'
            ];
        }
        return [
            'success' => false,
            'message' => 'No fue posible registrar el producto.'
        ];
    }

    /**
     * Actualizar un producto existente.
     *
     * @param array $datos
     * @return array
     */
    public function actualizarProducto(array $datos): array
    {
        $resultado = $this->productoModel->actualizar($datos);
        if ($resultado) {
            return [
                "success" => true,
                "message" => "Producto actualizado correctamente."
            ];
        }
        return [
            "success" => false,
            "message" => "No fue posible actualizar el producto."
        ];
    }

    /**
     * Desactivar un producto.
     *
     * @param int $id
     * @return array
     */
    public function desactivarProducto(int $id): array
    {
        $resultado = $this->productoModel->desactivar($id);
        if ($resultado) {
            return [
                'success' => true,
                'message' => 'Producto desactivado correctamente.'
            ];
        }
        return [
            'success' => false,
            'message' => 'No fue posible desactivar el producto.'
        ];
    }

    /* ==========================================================
     * MÉTODOS PRIVADOS
     * ========================================================== */

    /**
     * Determinar el estado del stock.
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