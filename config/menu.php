<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Menu Configuration
 * ==========================================================
 * Archivo encargado de definir la estructura del menú de
 * navegación del sistema Confipym. Centraliza la información
 * de cada módulo, incluyendo títulos, descripciones, iconos,
 * rutas y estado de disponibilidad para facilitar el
 * mantenimiento del proyecto.
 * ==========================================================
 */

$menu = [

    /* ==========================================================
     * DASHBOARD
     * ========================================================== */

    "dashboard" => [
        "titulo"         => "Dashboard",
        "tituloBrowser"  => "Inicio",
        "descripcion"    => "Panel principal de Confipym.",
        "icono"          => "📊",
        "url"            => BASE_URL . "index.php?page=dashboard",
        "activo"         => true
    ],

    /* ==========================================================
     * INVENTARIO
     * ========================================================== */

    "inventario" => [
        "titulo"         => "Inventario",
        "tituloBrowser"  => "Inventario",
        "descripcion"    => "Esta sección se encuentra actualmente en desarrollo.",
        "icono"          => "📦",
        "url"            => BASE_URL . "index.php?page=inventario",
        "activo"         => true
    ],

    /* ==========================================================
     * PRODUCTOS
     * ========================================================== */

    "productos" => [
        "titulo"         => "Productos",
        "tituloBrowser"  => "Productos",
        "descripcion"    => "Administra los productos registrados dentro del inventario de Confipym.",
        "icono"          => "🧁",
        "url"            => BASE_URL . "index.php?page=productos",
        "activo"         => true
    ],

    /* ==========================================================
     * VENTAS
     * ========================================================== */

    "ventas" => [
        "titulo"         => "Ventas",
        "tituloBrowser"  => "Ventas",
        "descripcion"    => "Registra y consulta las ventas realizadas en Confipym.",
        "icono"          => "🛒",
        "url"            => BASE_URL . "index.php?page=ventas",
        "activo"         => true
    ],

    /* ==========================================================
     * INGRESOS
     * ========================================================== */

    "ingresos" => [
        "titulo"         => "Ingresos",
        "tituloBrowser"  => "Ingresos",
        "descripcion"    => "Esta sección se encuentra actualmente en desarrollo.",
        "icono"          => "💰",
        "url"            => BASE_URL . "index.php?page=ingresos",
        "activo"         => true
    ],

    /* ==========================================================
     * GASTOS
     * ========================================================== */

    "gastos" => [
        "titulo"         => "Gastos",
        "tituloBrowser"  => "Gastos",
        "descripcion"    => "Esta sección se encuentra actualmente en desarrollo.",
        "icono"          => "🧾",
        "url"            => BASE_URL . "index.php?page=gastos",
        "activo"         => true
    ],

    /* ==========================================================
     * REPORTES
     * ========================================================== */

    "reportes" => [
        "titulo"         => "Reportes",
        "tituloBrowser"  => "Reportes",
        "descripcion"    => "Esta sección se encuentra actualmente en desarrollo.",
        "icono"          => "📈",
        "url"            => BASE_URL . "index.php?page=reportes",
        "activo"         => true
    ],

    /* ==========================================================
     * CONFIGURACIÓN
     * ========================================================== */

    "configuracion" => [
        "titulo"         => "Configuración",
        "tituloBrowser"  => "Configuración",
        "descripcion"    => "Esta sección se encuentra actualmente en desarrollo.",
        "icono"          => "⚙️",
        "url"            => BASE_URL . "index.php?page=configuracion",
        "activo"         => true
    ],

    /* ==========================================================
     * MANTENIMIENTO
     * ========================================================== */

    "mantenimiento" => [
        "titulo"         => "Mantenimiento",
        "tituloBrowser"  => "Mantenimiento",
        "descripcion"    => "La página solicitada no se encuentra disponible actualmente.",
        "icono"          => "⚙️",
        "url"            => BASE_URL . "index.php?page=mantenimiento",
        "activo"         => true
    ]
];