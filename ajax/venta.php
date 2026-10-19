<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/controllers/VentaController.php';

iniciarSesion();
exigirAdministradorAPI();

/**
 * Envía una respuesta JSON y termina la ejecución.
 */
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

/**
 * Lee y valida el cuerpo JSON de la solicitud.
 */
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

    if (!is_array($datos)) {
        responder([
            'success' => false,
            'message' => 'El cuerpo JSON debe ser un objeto o una lista válida.'
        ], 400);
    }

    return $datos;
}

/**
 * Valida que el ID recibido sea un entero positivo.
 */
function obtenerIdVentaDesdeSolicitud(): int
{
    if (!array_key_exists('id', $_GET)) {
        responder([
            'success' => false,
            'message' => 'Debe indicar el id de la venta.'
        ], 400);
    }

    $idRecibido = $_GET['id'];

    if (
        !is_string($idRecibido) ||
        !ctype_digit($idRecibido) ||
        (int) $idRecibido <= 0
    ) {
        responder([
            'success' => false,
            'message' => 'El ID de la venta no es válido.'
        ], 400);
    }

    return (int) $idRecibido;
}

/**
 * Responde usando el código HTTP indicado por el controlador.
 */
function responderResultado(
    array $respuesta,
    int $codigoExito = 200
): never {
    $codigo = $respuesta['success']
        ? $codigoExito
        : ($respuesta['http_status'] ?? 500);

    responder($respuesta, $codigo);
}

try {
    $controller = new VentaController();

    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    switch ($metodo) {
        /*
         * GET: consultar métricas, una venta o listar ventas.
         */
        case 'GET':

            if (
                isset($_GET['accion']) &&
                $_GET['accion'] === 'metricas'
            ) {
                $respuesta = $controller->obtenerMetricas();

                responderResultado($respuesta);
            }

            if (array_key_exists('id', $_GET)) {
                $idVenta = obtenerIdVentaDesdeSolicitud();

                $respuesta = $controller->obtenerVenta($idVenta);

                responderResultado($respuesta);
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

            responderResultado($respuesta);

        /*
         * POST: crear una venta.
         */
        case 'POST':

            $datos = obtenerJsonBody();

            $respuesta = $controller->crearVenta($datos);

            responderResultado($respuesta, 201);

        /*
         * PUT: actualizar una venta pendiente.
         */
        case 'PUT':

            $idVenta = obtenerIdVentaDesdeSolicitud();
            $datos = obtenerJsonBody();

            $respuesta = $controller->actualizarVenta(
                $idVenta,
                $datos
            );

            responderResultado($respuesta);

        /*
         * PATCH: pagar o cancelar una venta.
         */
        case 'PATCH':

            $idVenta = obtenerIdVentaDesdeSolicitud();

            $accion = isset($_GET['accion'])
                ? strtolower(trim((string) $_GET['accion']))
                : '';

            if ($accion === 'pagar') {
                $respuesta = $controller->pagarVenta($idVenta);
            } elseif ($accion === 'cancelar') {
                $respuesta = $controller->cancelarVenta($idVenta);
            } else {
                responder([
                    'success' => false,
                    'message' => 'Acción no válida. Use pagar o cancelar.'
                ], 400);
            }

            responderResultado($respuesta);

        /*
         * Cualquier otro método HTTP.
         */
        default:

            header('Allow: GET, POST, PUT, PATCH');

            responder([
                'success' => false,
                'message' => 'Método HTTP no permitido.'
            ], 405);
    }
} catch (Throwable $e) {
    error_log(
        'Error en ajax/venta.php: ' .
        get_class($e) . ': ' .
        $e->getMessage()
    );

    responder([
        'success' => false,
        'message' => 'Error interno del servidor.'
    ], 500);
}