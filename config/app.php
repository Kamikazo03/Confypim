<?php
declare(strict_types=1);

/**
 * ==========================================================
 * App Configuration
 * ==========================================================
 * Archivo encargado de cargar la configuración general del
 * sistema Confipym. Inicializa las constantes globales,
 * variables compartidas por las vistas y preferencias de la
 * aplicación para que estén disponibles durante toda la
 * ejecución del proyecto.
 *
 * Inicializa las constantes globales, la configuración
 * del menú y la sesión del usuario autenticado.
 * ==========================================================
 */

/* ==========================================================
 * CONFIGURACIÓN BASE
 * ========================================================== */

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/menu.php';

/* ==========================================================
 * AUTENTICACIÓN
 * ========================================================== */

/**
 * Cargar helper de autenticación.
 */
require_once ROOT_PATH . '/app/helpers/AuthHelper.php';

/**
 * Iniciar o mantener la sesión.
 *
 * AuthHelper se encarga de controlar:
 * - Inicio de sesión
 * - Tiempo de inactividad
 * - Cookie de sesión
 * - Regeneración
 * - Estado de autenticación
 */
iniciarSesion();

/* ==========================================================
 * INFORMACIÓN GENERAL
 * ========================================================== */

/**
 * Nombre del sistema.
 */
$nombreSistema = APP_NAME;

/* ==========================================================
 * SESIÓN DEL USUARIO
 * ========================================================== */

/**
 * Obtener información del usuario autenticado
 * desde la sesión.
 */
$usuarioSesion = obtenerUsuarioSesion();

/**
 * Nombre del usuario.
 */
$nombreUsuario = $usuarioSesion['nombre'] ?? 'Usuario';

/**
 * Rol del usuario.
 */
$rolUsuario = $usuarioSesion['rol'] ?? '';

/**
 * Foto de perfil.
 *
 * Actualmente Confipym no utiliza fotografías de usuario.
 */
$fotoPerfil = null;

/* ==========================================================
 * PREFERENCIAS
 * ========================================================== */

/**
 * Mostrar el botón de notificaciones.
 */
$mostrarNotificaciones = true;