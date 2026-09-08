<?php

/**
 * ==========================================================
 * API de Productos
 * ==========================================================
 * Este archivo recibe las solicitudes HTTP provenientes del
 * frontend y las delega al ProductoController.
 *
 * Soporta operaciones CRUD mediante los métodos:
 * GET, POST, PUT y DELETE.
 * ==========================================================
 */

require_once __DIR__ . '/../app/controllers/ProductoController.php';

header('Content-Type: application/json; charset=utf-8');

$controller = new ProductoController();
$metodo = $_SERVER['REQUEST_METHOD'];

/* ==========================================================
 * ENRUTAMIENTO HTTP
 * ========================================================== */

switch ($metodo) {

    /* ==========================================================
     * GET
     * Obtener uno o todos los productos.
     * ========================================================== */

    case 'GET':

    if (isset($_GET["id"])) {

        echo json_encode(
            $controller->obtenerProducto(
                (int)$_GET["id"]
            )
        );

    } else {

        echo json_encode(
            $controller->listarProductos()
        );
    }

    break;

    /* ==========================================================
     * POST
     * Registrar un nuevo producto.
     * ========================================================== */

    case 'POST':

        $datos = json_decode(
            file_get_contents("php://input"),
            true
        );

        echo json_encode(
            $controller->crearProducto($datos)
        );

    break;

    /* ==========================================================
     * PUT
     * Actualizar un producto existente.
     * ========================================================== */

    case 'PUT':

    $datos = json_decode(
        file_get_contents("php://input"),
        true
    );

    echo json_encode(
        $controller->actualizarProducto($datos)
    );

    break;

    /* ==========================================================
     * DELETE
     * Desactivar un producto (Soft Delete).
     * ========================================================== */

    case 'DELETE':

    $id = $_GET['id'] ?? 0;

    echo json_encode(
        $controller->desactivarProducto((int)$id)
    );

    break;

    /* ==========================================================
     * Método no permitido.
     * ========================================================== */

    default:

        http_response_code(405);

        echo json_encode([
            "success" => false,
            "message" => "Método no permitido."
        ]);
        
        break;

}