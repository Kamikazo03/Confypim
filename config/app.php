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
 * ==========================================================
 */

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/menu.php';

/* ==========================================================
 * INFORMACIÓN GENERAL
 * ========================================================== */

/**
 * Nombre del sistema.
 */
$nombreSistema = "Confipym";

/**
 * Usuario autenticado.
 *
 * Temporalmente estos datos son estáticos.
 * Posteriormente serán obtenidos desde la sesión.
 */
$nombreUsuario = "Kevin Medina";
$rolUsuario = "Administrador";
$fotoPerfil = null;

/* ==========================================================
 * PREFERENCIAS
 * ========================================================== */

/**
 * Mostrar el botón de notificaciones.
 */
$mostrarNotificaciones = true;