<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/controllers/VentaController.php';

iniciarSesion();
exigirAdministradorAPI();

function responder(
    array $respuesta,
    int $codigo = 200
): never {

    http_response_code($codigo);

    echo json_encode(
        $respuesta,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

function obtenerJsonBody(): array
{
    $contenido =
        file_get_contents('php://input');

    if (
        $contenido === false ||
        trim($contenido) === ''
    ) {
        return [];
    }

    $datos =
        json_decode(
            $contenido,
            true
        );

    if (
        json_last_error() !==
        JSON_ERROR_NONE
    ) {
        responder([
            'success' => false,
            'message' =>
                'El cuerpo JSON enviado no es válido.'
        ], 400);
    }

    return is_array($datos)
        ? $datos
        : [];
}

try {

    $controller =
        new VentaController();

    $metodo =
        $_SERVER['REQUEST_METHOD']
        ?? 'GET';

    switch ($metodo) {

        /* ==========================================================
         * GET
         * ========================================================== */

        case 'GET':

            if (
                isset($_GET['accion']) &&
                $_GET['accion'] === 'metricas'
            ) {

                $respuesta =
                    $controller->obtenerMetricas();

                responder(
                    $respuesta,
                    $respuesta['success']
                        ? 200
                        : 500
                );
            }

            $idVenta =
                isset($_GET['id'])
                    ? (int) $_GET['id']
                    : 0;

            if ($idVenta > 0) {

                $respuesta =
                    $controller->obtenerVenta(
                        $idVenta
                    );

                responder(
                    $respuesta,
                    $respuesta['success']
                        ? 200
                        : 404
                );
            }

            $estado =
                isset($_GET['estado'])
                    ? trim(
                        (string) $_GET['estado']
                    )
                    : null;

            $busqueda =
                isset($_GET['busqueda'])
                    ? trim(
                        (string) $_GET['busqueda']
                    )
                    : null;

            $respuesta =
                $controller->listarVentas(
                    $estado,
                    $busqueda
                );

            responder($respuesta);

            break;

        /* ==========================================================
         * POST
         * ========================================================== */

        case 'POST':

            $datos =
                obtenerJsonBody();

            $respuesta =
                $controller->crearVenta(
                    $datos
                );

            responder(
                $respuesta,
                $respuesta['success']
                    ? 201
                    : 400
            );

            break;

        /* ==========================================================
         * PUT
         * ========================================================== */

        case 'PUT':

            $idVenta =
                isset($_GET['id'])
                    ? (int) $_GET['id']
                    : 0;

            if ($idVenta <= 0) {
                responder([
                    'success' => false,
                    'message' =>
                        'Debe indicar el id de la venta.'
                ], 400);
            }

            $datos =
                obtenerJsonBody();

            $respuesta =
                $controller->actualizarVenta(
                    $idVenta,
                    $datos
                );

            responder(
                $respuesta,
                $respuesta['success']
                    ? 200
                    : 400
            );

            break;

        /* ==========================================================
         * PATCH
         * ========================================================== */

        case 'PATCH':

            $idVenta =
                isset($_GET['id'])
                    ? (int) $_GET['id']
                    : 0;

            $accion =
                isset($_GET['accion'])
                    ? strtolower(
                        trim(
                            (string) $_GET['accion']
                        )
                    )
                    : '';

            if ($idVenta <= 0) {
                responder([
                    'success' => false,
                    'message' =>
                        'Debe indicar el id de la venta.'
                ], 400);
            }

            if ($accion === 'pagar') {

                $respuesta =
                    $controller->pagarVenta(
                        $idVenta
                    );

            } elseif ($accion === 'cancelar') {

                $respuesta =
                    $controller->cancelarVenta(
                        $idVenta
                    );

            } else {

                responder([
                    'success' => false,
                    'message' =>
                        'Acción no válida. Use pagar o cancelar.'
                ], 400);
            }

            responder(
                $respuesta,
                $respuesta['success']
                    ? 200
                    : 400
            );

            break;

        /* ==========================================================
         * MÉTODO NO PERMITIDO
         * ========================================================== */

        default:

            responder([
                'success' => false,
                'message' =>
                    'Método HTTP no permitido.'
            ], 405);
    }

} catch (Throwable $e) {

    /**
     * No exponemos detalles internos de PHP/MySQL
     * al navegador.
     */
    responder([
        'success' => false,
        'message' =>
            'Error interno del servidor.'
    ], 500);
}