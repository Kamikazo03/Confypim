<?php

declare(strict_types=1);

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

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/AuthHelper.php';
require_once __DIR__ . '/../app/controllers/ProductoController.php';

iniciarSesion();

header('Content-Type: application/json; charset=utf-8');

/**
 * ==========================================================
 * AUTENTICACIÓN
 * ==========================================================
 *
 * Este endpoint solo puede ser utilizado por usuarios
 * autenticados con rol administrador.
 *
 * ==========================================================
 */

exigirAdministradorAPI();

$controller =
    new ProductoController();

$metodo =
    $_SERVER['REQUEST_METHOD']
    ?? 'GET';

/* ==========================================================
 * ENRUTAMIENTO HTTP
 * ========================================================== */

try {

    switch ($metodo) {

        /* ==========================================================
         * GET
         * Obtener uno o todos los productos.
         * ========================================================== */

        case 'GET':

            if (isset($_GET['id'])) {

                $respuesta =
                    $controller->obtenerProducto(
                        (int) $_GET['id']
                    );

            } else {

                $respuesta =
                    $controller->listarProductos();
            }

            echo json_encode(
                $respuesta,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;

        /* ==========================================================
         * POST
         * Registrar un nuevo producto.
         * ========================================================== */

        case 'POST':

            $datos =
                json_decode(
                    file_get_contents(
                        'php://input'
                    ),
                    true
                );

            if (!is_array($datos)) {
                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Los datos enviados no son válidos.'
                ]);

                break;
            }

            $respuesta =
                $controller->crearProducto(
                    $datos
                );

            http_response_code($respuesta['codigo'] ?? ($respuesta['success'] ? 201 : 400));
            unset($respuesta['codigo']);

            echo json_encode(
                $respuesta,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;

        /* ==========================================================
         * PUT
         * Actualizar un producto existente.
         * ========================================================== */

        case 'PUT':

            $datos =
                json_decode(
                    file_get_contents(
                        'php://input'
                    ),
                    true
                );

            if (!is_array($datos)) {
                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Los datos enviados no son válidos.'
                ]);

                break;
            }

            $respuesta =
                $controller->actualizarProducto(
                    $datos
                );
            
            http_response_code($respuesta['codigo'] ?? ($respuesta['success'] ? 200 : 404));
            unset($respuesta['codigo']);

            echo json_encode(
                $respuesta,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;

        /* ==========================================================
         * DELETE
         * Desactivar un producto (Soft Delete).
         * ========================================================== */

        case 'DELETE':

            $id =
                isset($_GET['id'])
                    ? (int) $_GET['id']
                    : 0;

            if ($id <= 0) {
                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Debe indicar un producto válido.'
                ]);

                break;
            }

            $respuesta =
                $controller->desactivarProducto(
                    $id
                );

            http_response_code($respuesta['codigo'] ?? ($respuesta['success'] ? 200 : 404));
            unset($respuesta['codigo']);

            echo json_encode(
                $respuesta,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            break;

        /* ==========================================================
         * MÉTODO NO PERMITIDO
         * ========================================================== */

        default:

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Método no permitido.'
            ]);

            break;
    }

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'Error interno del servidor.'
    ]);
}