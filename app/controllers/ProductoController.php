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
     * Valida los datos antes de enviarlos al modelo.
     * Códigos: 201 creado, 400 datos inválidos, 500 fallo interno.
     *
     * @param array $datos
     * @return array
     */
    public function crearProducto(array $datos): array
    {
        // 1. Validar y normalizar los datos recibidos
        $validacion = $this->validarDatosProducto($datos);

        if (!$validacion['ok']) {
            return [
                'success' => false,
                'message' => $validacion['mensaje'],
                'codigo'  => 400
            ];
        }

        // 2. Registrar el producto con los datos ya limpios
        $resultado = $this->productoModel->crear($validacion['datos']);

        if ($resultado) {
            return [
                'success' => true,
                'message' => 'Producto registrado correctamente.',
                'codigo'  => 201
            ];
        }

        return [
            'success' => false,
            'message' => 'No fue posible registrar el producto.',
            'codigo'  => 500
        ];
    }

    /**
     * Actualizar un producto existente.
     *
     * Códigos: 200 actualizado, 400 datos inválidos,
     * 404 producto inexistente, 500 fallo interno.
     *
     * @param array $datos
     * @return array
     */
    public function actualizarProducto(array $datos): array
    {
        // 1. Validar el identificador del producto
        $id = filter_var($datos['id_producto'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            return [
                'success' => false,
                'message' => 'Debe indicar un producto válido.',
                'codigo'  => 400
            ];
        }

        // 2. Validar y normalizar el resto de los datos
        $validacion = $this->validarDatosProducto($datos);

        if (!$validacion['ok']) {
            return [
                'success' => false,
                'message' => $validacion['mensaje'],
                'codigo'  => 400
            ];
        }

        // 3. Verificar que el producto exista, esté activo y
        //    pertenezca al administrador autenticado
        if ($this->productoModel->obtenerPorId($id) === null) {
            return [
                'success' => false,
                'message' => 'El producto que intenta actualizar no existe.',
                'codigo'  => 404
            ];
        }

        // 4. Actualizar
        $validacion['datos']['id_producto'] = $id;

        $resultado = $this->productoModel->actualizar($validacion['datos']);

        if ($resultado) {
            return [
                'success' => true,
                'message' => 'Producto actualizado correctamente.',
                'codigo'  => 200
            ];
        }

        return [
            'success' => false,
            'message' => 'No fue posible actualizar el producto.',
            'codigo'  => 500
        ];
    }

    /**
     * Desactivar un producto (eliminación lógica).
     *
     * Códigos: 200 desactivado, 404 producto inexistente,
     * 500 fallo interno.
     *
     * @param int $id
     * @return array
     */
    public function desactivarProducto(int $id): array
    {
        // Verificar que el producto exista, esté activo y
        // pertenezca al administrador autenticado
        if ($this->productoModel->obtenerPorId($id) === null) {
            return [
                'success' => false,
                'message' => 'El producto que intenta desactivar no existe.',
                'codigo'  => 404
            ];
        }

        $resultado = $this->productoModel->desactivar($id);

        if ($resultado) {
            return [
                'success' => true,
                'message' => 'Producto desactivado correctamente.',
                'codigo'  => 200
            ];
        }

        return [
            'success' => false,
            'message' => 'No fue posible desactivar el producto.',
            'codigo'  => 500
        ];
    }

    /* ==========================================================
     * MÉTODOS PRIVADOS
     * ========================================================== */

    /**
     * Validar y normalizar los datos de un producto.
     *
     * Reglas:
     *  - nombre: obligatorio, no puede estar vacío ni ser solo espacios.
     *  - descripcion: opcional.
     *  - precio_unitario: numérico y no negativo.
     *  - stock_disponible: entero y no negativo.
     *
     * @param array $datos
     * @return array ['ok' => bool, 'mensaje' => string] si hay error,
     *               ['ok' => true, 'datos' => array] si es válido.
     */
    private function validarDatosProducto(array $datos): array
    {
        // Nombre obligatorio
        $nombre = (isset($datos['nombre']) && is_string($datos['nombre']))
            ? trim($datos['nombre'])
            : '';

        if ($nombre === '') {
            return [
                'ok'      => false,
                'mensaje' => 'El nombre del producto es obligatorio.'
            ];
        }

        // Descripción opcional
        $descripcion = (isset($datos['descripcion']) && is_string($datos['descripcion']))
            ? trim($datos['descripcion'])
            : '';

        // Precio numérico y no negativo
        $precio = $datos['precio_unitario'] ?? null;

        if (!is_numeric($precio)) {
            return [
                'ok'      => false,
                'mensaje' => 'El precio unitario debe ser un número válido.'
            ];
        }

        if ((float) $precio <= 0) {
            return [
                'ok'      => false,
                'mensaje' => 'El precio unitario no puede ser negativo.'
            ];
        }

        // Stock entero y no negativo
        $stock = filter_var($datos['stock_disponible'] ?? null, FILTER_VALIDATE_INT);

        if ($stock === false) {
            return [
                'ok'      => false,
                'mensaje' => 'El stock disponible debe ser un número entero.'
            ];
        }

        if ($stock < 0) {
            return [
                'ok'      => false,
                'mensaje' => 'El stock disponible no puede ser negativo.'
            ];
        }

        return [
            'ok'    => true,
            'datos' => [
                'nombre'           => $nombre,
                'descripcion'      => $descripcion,
                'precio_unitario'  => (float) $precio,
                'stock_disponible' => $stock
            ]
        ];
    }

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