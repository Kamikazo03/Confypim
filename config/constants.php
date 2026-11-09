<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Constants
 * ==========================================================
 * Archivo encargado de definir las constantes globales del
 * sistema Confypim. Centraliza la configuración relacionada
 * con el nombre del proyecto, su versión, rutas físicas,
 * URL base y directorios de recursos compartidos.
 * ==========================================================
 */

/* ==========================================================
 * INFORMACIÓN GENERAL
 * ========================================================== */

/**
 * Nombre de la aplicación.
 */
define('APP_NAME', 'Confypim');

/**
 * Versión actual del sistema.
 */
define('APP_VERSION', '1.0.2');

/**
 * Rol asignado a los usuarios durante el registro.
 *
 * IMPORTANTE:
 * El rol se define en el servidor y no debe recibirse
 * directamente desde el formulario de registro.
 *
 * Para cambiar el rol predeterminado en el futuro,
 * sustituir 'administrador' por 'cliente'.
 */
define('DEFAULT_REGISTER_ROLE', 'administrador');

/* ==========================================================
 * RUTAS DEL PROYECTO
 * ========================================================== */

/**
 * Ruta física absoluta del proyecto.
 */
define('ROOT_PATH', dirname(__DIR__));

/**
 * URL base del proyecto.
 *
 * La ruta se determina según el entorno de ejecución:
 *
 * - Desarrollo local: /Confypim/
 * - Producción: /
 *
 * También permite establecer una ruta personalizada
 * mediante la variable de entorno APP_BASE_URL.
 *
 * Modificar únicamente si cambia la ubicación
 * del proyecto dentro del servidor.
 */
$urlBaseConfigurada = getenv('APP_BASE_URL');

if ($urlBaseConfigurada !== false && $urlBaseConfigurada !== '') {

    $baseUrl = rtrim($urlBaseConfigurada, '/') . '/';

} elseif (getenv('APP_ENV') === 'production') {

    $baseUrl = '/';

} else {

    $baseUrl = '/Confypim/';
}

define('BASE_URL', $baseUrl);

/* ==========================================================
 * RECURSOS COMPARTIDOS
 * ========================================================== */

/**
 * Directorio de hojas de estilo.
 */
define('CSS_URL', BASE_URL . 'assets/css/');

/**
 * Directorio de archivos JavaScript.
 */
define('JS_URL', BASE_URL . 'assets/js/');

/**
 * Directorio de imágenes.
 */
define('IMG_URL', BASE_URL . 'assets/img/');