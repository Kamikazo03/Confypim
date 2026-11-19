<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/controllers/ReporteController.php';

iniciarSesion();
exigirAdministradorAPI();

/**
 * Enviar una respuesta JSON y finalizar la solicitud.
 */
function responderReporte(array $respuesta, int $codigo = 200): void
{
    if (isset($respuesta['http_status'])) {
        $codigo = (int) $respuesta['http_status'];
        unset($respuesta['http_status']);
    }

    http_response_code($codigo);

    echo json_encode(
        $respuesta,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/**
 * Leer el cuerpo JSON de la solicitud.
 */
function obtenerJsonReporte(): array
{
    $contenido = file_get_contents('php://input');

    if ($contenido === false || trim($contenido) === '') {
        return [];
    }

    $datos = json_decode($contenido, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datos)) {
        responderReporte([
            'success' => false,
            'message' => 'Los datos enviados no contienen un JSON válido.'
        ], 400);
    }

    return $datos;
}

try {
    $controller = new ReporteController();
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    switch ($metodo) {
        case 'GET':

            $accion = $_GET['accion'] ?? 'datos';

            if ($accion === 'historial') {
                $respuesta = $controller->obtenerHistorial();
            } elseif ($accion === 'datos') {
                $respuesta = $controller->obtenerDatos([
                    'inicio' => $_GET['inicio'] ?? '',
                    'fin' => $_GET['fin'] ?? ''
                ]);
            } else {
                responderReporte([
                    'success' => false,
                    'message' => 'La consulta solicitada no es válida.'
                ], 400);
            }

            responderReporte($respuesta);
            break;

        case 'POST':

            $datos = obtenerJsonReporte();

            if (($datos['accion'] ?? '') !== 'guardar') {
                responderReporte([
                    'success' => false,
                    'message' => 'La operación solicitada no es válida.'
                ], 400);
            }

            responderReporte(
                $controller->guardar($datos),
                201
            );
            break;

        default:
            responderReporte([
                'success' => false,
                'message' => 'El método HTTP solicitado no está permitido.'
            ], 405);
    }
} catch (Throwable $e) {
    error_log('ajax/reporte.php: ' . $e->getMessage());

    responderReporte([
        'success' => false,
        'message' => 'Ocurrió un error inesperado al procesar la solicitud.'
    ], 500);
}
