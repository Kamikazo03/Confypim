<?php

declare(strict_types=1);

/**
 * ==========================================================
 * API de Insumos
 * ==========================================================
 * Este archivo recibe las solicitudes HTTP provenientes del
 * frontend y las delega al InsumoController.
 *
 * Soporta consultas y operaciones de inventario mediante
 * los métodos GET, POST, PUT y DELETE.
 * ==========================================================
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers/AuthHelper.php';
require_once __DIR__ . '/../app/controllers/InsumoController.php';

iniciarSesion();

header('Content-Type: application/json; charset=utf-8');

/**
 * ==========================================================
 * AUTENTICACIÓN
 * ==========================================================
 *
 * Este endpoint solo puede ser utilizado por usuarios
 * autenticados con rol administrador.
 * ==========================================================
 */

exigirAdministradorAPI();

$controller = new InsumoController();

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/**
 * Obtener los datos JSON enviados por el frontend.
 */
function obtenerDatosJSON(): ?array
{
    $datos = json_decode(file_get_contents('php://input'), true);

    return is_array($datos) ? $datos : null;
}

/**
 * Enviar una respuesta JSON con el código HTTP correspondiente.
 */
function responderJSON($respuesta, int $codigo = 200): void
{
    if (isset($respuesta['codigo'])) {
        $codigo = (int) $respuesta['codigo'];
        unset($respuesta['codigo']);
    }

    http_response_code($codigo);

    echo json_encode(
        $respuesta,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}

/* ==========================================================
 * ENRUTAMIENTO HTTP
 * ========================================================== */

try {

    switch ($metodo) {

        /* ==========================================================
         * GET
         * Consultar inventario, movimientos, recetas y catálogos.
         * ========================================================== */

        case 'GET':

            $accion = $_GET['accion'] ?? '';

            switch ($accion) {

                case 'inactivos':
                    responderJSON($controller->listarInactivos());
                    break;

                case 'movimientos':
                    responderJSON($controller->listarMovimientos());
                    break;

                case 'metricas':
                    responderJSON($controller->obtenerMetricas());
                    break;

                case 'productos':
                    responderJSON($controller->listarProductos());
                    break;

                case 'recetas':
                    responderJSON($controller->listarRecetas());
                    break;

                case 'receta':
                    $idProducto = (int) ($_GET['id_producto'] ?? 0);
                    responderJSON($controller->obtenerReceta($idProducto));
                    break;

                default:

                    if (isset($_GET['id'])) {
                        responderJSON(
                            $controller->obtenerInsumo((int) $_GET['id'])
                        );
                    } else {
                        responderJSON($controller->listarInsumos());
                    }

                    break;
            }

            break;

        /* ==========================================================
         * POST
         * Registrar insumos y operaciones de inventario.
         * ========================================================== */

        case 'POST':

            $datos = obtenerDatosJSON();

            if ($datos === null) {
                responderJSON([
                    'success' => false,
                    'message' => 'Los datos enviados no son válidos.'
                ], 400);

                break;
            }

            switch ($datos['accion'] ?? '') {

                case 'crear':
                    responderJSON($controller->crearInsumo($datos), 201);
                    break;

                case 'comprar':
                    responderJSON($controller->registrarCompra($datos), 201);
                    break;

                case 'comprar_multiple':
                    responderJSON($controller->registrarComprasMultiples($datos), 201);
                    break;

                case 'movimientos_multiples':
                    responderJSON($controller->registrarMovimientosMultiples($datos), 201);
                    break;

                case 'consumir':
                    responderJSON($controller->registrarConsumo($datos), 201);
                    break;

                case 'ajustar':
                    responderJSON($controller->registrarAjuste($datos), 201);
                    break;

                case 'guardar_receta':
                    responderJSON($controller->guardarReceta($datos), 200);
                    break;

                case 'restaurar':

                    $id = (int) ($datos['id_insumo'] ?? 0);

                    responderJSON(
                        $controller->restaurarInsumo($id),
                        200
                    );

                    break;

                default:
                    responderJSON([
                        'success' => false,
                        'message' => 'La operación solicitada no es válida.'
                    ], 400);
                    break;
            }

            break;

        /* ==========================================================
         * PUT
         * Actualizar la información de un insumo.
         * ========================================================== */

        case 'PUT':

            $datos = obtenerDatosJSON();

            if ($datos === null) {
                responderJSON([
                    'success' => false,
                    'message' => 'Los datos enviados no son válidos.'
                ], 400);

                break;
            }

            responderJSON(
                $controller->actualizarInsumo($datos),
                200
            );

            break;

        /* ==========================================================
         * DELETE
         * Desactivar un insumo (Soft Delete).
         * ========================================================== */

        case 'DELETE':

            if (isset($_GET['id_producto'])) {
                $idProducto = (int) $_GET['id_producto'];

                responderJSON(
                    $controller->eliminarReceta($idProducto),
                    200
                );
            } else {
                $id = (int) ($_GET['id'] ?? 0);

                responderJSON(
                    $controller->desactivarInsumo($id),
                    200
                );
            }

            break;

        /* ==========================================================
         * MÉTODO NO PERMITIDO
         * ========================================================== */

        default:

            responderJSON([
                'success' => false,
                'message' => 'Método no permitido.'
            ], 405);

            break;
    }

} catch (RuntimeException $e) {

    responderJSON([
        'success' => false,
        'message' => $e->getMessage()
    ], 422);

} catch (PDOException $e) {

    error_log('Error en API de Insumos: ' . $e->getMessage());

    responderJSON([
        'success' => false,
        'message' => 'No fue posible completar la operación en la base de datos.'
    ], 500);

} catch (Throwable $e) {

    error_log('Error inesperado en API de Insumos: ' . $e->getMessage());

    responderJSON([
        'success' => false,
        'message' => 'Ocurrió un error interno al procesar la solicitud.'
    ], 500);
}
