<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Constants
 * ==========================================================
 * Archivo encargado de definir las constantes globales del
 * sistema Confipym. Centraliza la configuración relacionada
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
define('APP_NAME', 'Confipym');

/**
 * Versión actual del sistema.
 */
define('APP_VERSION', '1.0.1');

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
 * Modificar únicamente si cambia
 * el nombre de la carpeta raíz.
 */
define('BASE_URL', '/evidencia_EV06/');

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