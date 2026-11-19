<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Endpoint de los módulos financieros
 * ==========================================================
 * Procesa las solicitudes AJAX de Ingresos, Gastos y Reportes.
 * Las respuestas se envían exclusivamente en formato JSON.
 * ==========================================================
 */

// Las respuestas AJAX deben contener exclusivamente JSON.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/controllers/IngresosController.php';
require_once dirname(__DIR__) . '/app/controllers/GastosController.php';
require_once dirname(__DIR__) . '/app/controllers/ReportesController.php';

iniciarSesion();
exigirAdministradorAPI();

/**
 * Enviar una respuesta JSON y finalizar la solicitud.
 */
function responderFinanzas(array $data, int $status = 200): never
{
    if (ob_get_level() > 0) {
        ob_clean();
    }

    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

try {
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $tipo = trim((string) ($_GET['tipo'] ?? ''));

    if ($metodo === 'GET') {
        if ($tipo === 'reportes') {
            $controller = new ReportesController();
            $respuesta = $controller->reportes($_GET);
        } elseif ($tipo === 'ingresos') {
            $controller = new IngresosController();
            $respuesta = $controller->consultar($_GET);
        } elseif ($tipo === 'gastos') {
            $controller = new GastosController();
            $respuesta = $controller->consultar($_GET);
        } else {
            responderFinanzas([
                'success' => false,
                'message' => 'Módulo no válido.'
            ], 400);
        }

        responderFinanzas(
            $respuesta,
            $respuesta['success'] ? 200 : ($respuesta['http_status'] ?? 500)
        );
    }

    if ($metodo === 'POST' && $tipo === 'reportes') {
        $datos = json_decode(file_get_contents('php://input') ?: '{}', true);

        if (!is_array($datos)) {
            responderFinanzas([
                'success' => false,
                'message' => 'Solicitud no válida.'
            ], 400);
        }

        $controller = new ReportesController();
        $respuesta = $controller->guardarReporte($datos);

        responderFinanzas(
            $respuesta,
            $respuesta['success'] ? 201 : ($respuesta['http_status'] ?? 500)
        );
    }

    header('Allow: GET, POST');

    responderFinanzas([
        'success' => false,
        'message' => 'Método no permitido.'
    ], 405);
} catch (Throwable $e) {
    error_log('ajax/finanzas.php: ' . $e->getMessage());

    responderFinanzas([
        'success' => false,
        'message' => 'Error interno del servidor.'
    ], 500);
}
