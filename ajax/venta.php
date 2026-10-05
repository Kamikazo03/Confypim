<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/controllers/VentaController.php';

function responder(array $respuesta, int $codigo = 200): never
{
    http_response_code($codigo);

    echo json_encode(
        $respuesta,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function obtenerJsonBody(): array
{
    $contenido = file_get_contents('php://input');

    if ($contenido === false || trim($contenido) === '') {
        return [];
    }

    $datos = json_decode($contenido, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        responder([
            'success' => false,
            'message' => 'El cuerpo JSON enviado no es válido.'
        ], 400);
    }

    return is_array($datos) ? $datos : [];
}

try {

    $controller = new VentaController();

    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    switch ($metodo) {

        /*
         * GET
         *
         * /ajax/venta.php
         * /ajax/venta.php?id=14
         * /ajax/venta.php?estado=Pagada
         * /ajax/venta.php?estado=Pendiente
         * /ajax/venta.php?busqueda=14
         */
        case 'GET':

            if (
                isset($_GET['accion']) &&
                $_GET['accion'] === 'metricas'
            ) {
                $respuesta = $controller->obtenerMetricas();

                responder(
                    $respuesta,
                    $respuesta['success'] ? 200 : 500
                );
            }

            $idVenta = isset($_GET['id'])
                ? (int) $_GET['id']
                : 0;

            if ($idVenta > 0) {

                $respuesta = $controller->obtenerVenta(
                    $idVenta
                );

                responder(
                    $respuesta,
                    $respuesta['success'] ? 200 : 404
                );
            }

            $estado = isset($_GET['estado'])
                ? trim((string) $_GET['estado'])
                : null;

            $busqueda = isset($_GET['busqueda'])
                ? trim((string) $_GET['busqueda'])
                : null;

            $respuesta = $controller->listarVentas(
                $estado,
                $busqueda
            );

            responder($respuesta);

            break;


        /*
         * POST
         *
         * Crear una venta.
         */
        case 'POST':

            $datos = obtenerJsonBody();

            $respuesta = $controller->crearVenta(
                $datos
            );

            responder(
                $respuesta,
                $respuesta['success'] ? 201 : 400
            );

            break;


        /*
         * PUT
         *
         * Actualizar una venta pendiente.
         *
         * /ajax/venta.php?id=13
         */
        case 'PUT':

            $idVenta = isset($_GET['id'])
                ? (int) $_GET['id']
                : 0;

            if ($idVenta <= 0) {
                responder([
                    'success' => false,
                    'message' => 'Debe indicar el id de la venta.'
                ], 400);
            }

            $datos = obtenerJsonBody();

            $respuesta = $controller->actualizarVenta(
                $idVenta,
                $datos
            );

            responder(
                $respuesta,
                $respuesta['success'] ? 200 : 400
            );

            break;


        /*
         * PATCH
         *
         * Acciones:
         *
         * /ajax/venta.php?id=13&accion=pagar
         * /ajax/venta.php?id=13&accion=cancelar
         */
        case 'PATCH':

            $idVenta = isset($_GET['id'])
                ? (int) $_GET['id']
                : 0;

            $accion = isset($_GET['accion'])
                ? strtolower(trim((string) $_GET['accion']))
                : '';

            if ($idVenta <= 0) {
                responder([
                    'success' => false,
                    'message' => 'Debe indicar el id de la venta.'
                ], 400);
            }

            if ($accion === 'pagar') {

                $respuesta = $controller->pagarVenta(
                    $idVenta
                );

            } elseif ($accion === 'cancelar') {

                $respuesta = $controller->cancelarVenta(
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
                $respuesta['success'] ? 200 : 400
            );

            break;


        default:

            responder([
                'success' => false,
                'message' => 'Método HTTP no permitido.'
            ], 405);
    }

} catch (Throwable $e) {

    responder([
        'success' => false,
        'message' => 'Error interno del servidor.',
        'error' => $e->getMessage()
    ], 500);
}