<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Auth AJAX
 * ==========================================================
 * Endpoint para iniciar sesión, registrar usuarios y cerrar
 * sesiones mediante solicitudes HTTP.
 * ==========================================================
 */

require_once dirname(__DIR__) . '/config/app.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Enviar una respuesta JSON y finalizar la solicitud.
 */
function responderJSON(array $resultado, int $codigoHTTP = 200): void
{
    http_response_code($codigoHTTP);

    echo json_encode(
        $resultado,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

/**
 * Leer datos enviados como JSON o formulario.
 */
function obtenerDatosSolicitud(): array
{
    $contenido = file_get_contents('php://input');

    if ($contenido !== false && trim($contenido) !== '') {
        $datos = json_decode($contenido, true);

        if (is_array($datos)) {
            return $datos;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            responderJSON([
                'success' => false,
                'message' => 'Los datos enviados no tienen un formato válido.'
            ], 400);
        }
    }

    return $_POST;
}

/**
 * ==========================================================
 * Procesamiento de la solicitud
 * ==========================================================
 */

try {
    require_once ROOT_PATH . '/app/controllers/AuthController.php';

    iniciarSesion();

    $controller = new AuthController();
    $accion = $_GET['accion'] ?? 'login';
    $metodo = $_SERVER['REQUEST_METHOD'] ?? '';

    /**
     * Solo se permiten las acciones definidas.
     */
    $accionesPermitidas = ['login', 'register', 'logout'];

    if (!in_array($accion, $accionesPermitidas, true)) {
        responderJSON([
            'success' => false,
            'message' => 'La acción solicitada no es válida.'
        ], 400);
    }

    /**
     * Todas las acciones requieren POST.
     */
    if ($metodo !== 'POST') {
        header('Allow: POST');

        responderJSON([
            'success' => false,
            'message' => 'Método no permitido.'
        ], 405);
    }

    /**
     * Iniciar sesión.
     */
    if ($accion === 'login') {
        $resultado = $controller->iniciarSesion(
            obtenerDatosSolicitud()
        );

        responderJSON(
            $resultado,
            $resultado['success'] ? 200 : 401
        );
    }

    /**
     * Registrar usuario.
     */
    if ($accion === 'register') {
        $resultado = $controller->registrarUsuario(
            obtenerDatosSolicitud()
        );

        responderJSON(
            $resultado,
            $resultado['success'] ? 201 : 400
        );
    }

    /**
     * Cerrar sesión.
     */
    if ($accion === 'logout') {
        $resultado = $controller->cerrarSesion();

        responderJSON(
            $resultado,
            $resultado['success'] ? 200 : 500
        );
    }

} catch (Throwable $e) {
    /**
     * Registrar detalles técnicos en el log del servidor.
     * No enviar excepciones ni información de la base de
     * datos al navegador.
     */
    error_log(
        '[Confypim][Auth] ' .
        get_class($e) . ': ' .
        $e->getMessage() .
        ' en ' . $e->getFile() .
        ':' . $e->getLine()
    );

    responderJSON([
        'success' => false,
        'message' => 'Ocurrió un error interno. Inténtalo nuevamente más tarde.'
    ], 500);
}