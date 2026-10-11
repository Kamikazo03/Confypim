<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Auth Helper
 * ==========================================================
 *
 * Archivo encargado de administrar las funciones auxiliares
 * relacionadas con la autenticación y las sesiones.
 *
 * Centraliza:
 * - Inicio seguro de sesión.
 * - Verificación de autenticación.
 * - Verificación de roles.
 * - Cierre de sesión.
 * - Control básico de inactividad.
 *
 * ==========================================================
 */

/* ==========================================================
 * CONFIGURACIÓN DE SESIÓN
 * ========================================================== */

/**
 * Inicia la sesión de la aplicación.
 *
 * La configuración se realiza antes de session_start()
 * para establecer las características de la cookie.
 *
 * @return void
 */
function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Configuración de la cookie de sesión
    |--------------------------------------------------------------------------
    */

    $esHttps = (
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_URL,
        'secure' => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Modo estricto de sesiones
    |--------------------------------------------------------------------------
    */

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    session_start();

    /*
    |--------------------------------------------------------------------------
    | Control de inactividad
    |--------------------------------------------------------------------------
    */

    $tiempoInactividad = 1800;

    if (
        isset($_SESSION['ultima_actividad']) &&
        (
            time() - (int) $_SESSION['ultima_actividad']
        ) > $tiempoInactividad
    ) {
        cerrarSesion();

        return;
    }

    $_SESSION['ultima_actividad'] = time();
}


/* ==========================================================
 * AUTENTICACIÓN
 * ========================================================== */

/**
 * Determina si existe una sesión autenticada.
 *
 * @return bool
 */
function estaAutenticado(): bool
{
    return isset($_SESSION['usuario'])
        && isset($_SESSION['usuario']['id_usuario']);
}


/**
 * Obtiene los datos del usuario autenticado.
 *
 * @return array|null
 */
function obtenerUsuarioSesion(): ?array
{
    if (!estaAutenticado()) {
        return null;
    }

    return $_SESSION['usuario'];
}


/**
 * Obtiene el ID del usuario autenticado.
 *
 * @return int|null
 */
function obtenerIdUsuarioSesion(): ?int
{
    if (!estaAutenticado()) {
        return null;
    }

    return (int) $_SESSION['usuario']['id_usuario'];
}


/**
 * Determina si el usuario autenticado tiene
 * el rol de administrador.
 *
 * @return bool
 */
function esAdministrador(): bool
{
    if (!estaAutenticado()) {
        return false;
    }

    return (
        $_SESSION['usuario']['rol'] ?? ''
    ) === 'administrador';
}


/* ==========================================================
 * AUTORIZACIÓN
 * ========================================================== */

/**
 * Exige que exista una sesión autenticada.
 *
 * Si no existe, redirige al login.
 *
 * @return void
 */
function exigirAutenticacion(): void
{
    if (!estaAutenticado()) {
        header(
            'Location: ' .
            BASE_URL .
            'index.php?page=login'
        );

        exit;
    }
}


/**
 * Exige que el usuario tenga rol administrador.
 *
 * Si no cumple, redirige al login.
 *
 * @return void
 */
function exigirAdministrador(): void
{
    exigirAutenticacion();

    if (!esAdministrador()) {
        cerrarSesion();

        header(
            'Location: ' .
            BASE_URL .
            'index.php?page=login&error=acceso'
        );

        exit;
    }
}

/************************************************************
 * AUTORIZACIÓN PARA API
 ************************************************************/

/**
 * Exige que exista una sesión autenticada
 * y que el usuario tenga rol administrador.
 *
 * Esta función está destinada exclusivamente
 * a endpoints AJAX/API.
 *
 * En lugar de redirigir al login, devuelve
 * una respuesta JSON con el código HTTP correspondiente.
 *
 * @return void
 */
function exigirAdministradorAPI(): void
{
    if (!estaAutenticado()) {

        http_response_code(401);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode([
            'success' => false,
            'message' => 'Debe iniciar sesión para acceder a este servicio.'
        ]);

        exit;
    }

    if (!esAdministrador()) {

        http_response_code(403);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode([
            'success' => false,
            'message' => 'No tiene permisos para acceder a este servicio.'
        ]);

        exit;
    }
}

/* ==========================================================
 * CIERRE DE SESIÓN
 * ========================================================== */

/**
 * Cierra completamente la sesión actual.
 *
 * @return void
 */
function cerrarSesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $parametros = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $parametros['path'],
            $parametros['domain'],
            $parametros['secure'],
            $parametros['httponly']
        );
    }

    session_destroy();
}