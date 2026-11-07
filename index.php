<?php

declare(strict_types=1);

/**=====================================================
 * Front Controller
 * =====================================================
 * Punto único de entrada de la aplicación.
 *
 * Desde aquí se carga la configuración,
 * se controla la autenticación y se determina
 * la vista correspondiente a cada ruta.
 * =====================================================*/

/* ==========================================================
 * CONFIGURACIÓN
 * ========================================================== */

/**
 * Cargar configuración general de la aplicación.
 *
 * Este archivo también inicia y mantiene la sesión
 * mediante AuthHelper.
 */

require_once __DIR__ . '/config/app.php';

/* ==========================================================
 * INICIALIZACIÓN
 * ========================================================== */

/* ==========================================================
 * RUTA SOLICITADA
 * ========================================================== */

/**
 * Obtener la página solicitada.
 *
 * Si no existe el parámetro "page", se utilizará
 * el Dashboard como ruta principal.
 */
$pagina = filter_input(
    INPUT_GET,
    'page',
    FILTER_SANITIZE_FULL_SPECIAL_CHARS
) ?? 'dashboard';

$pagina = $pagina ?: (estaAutenticado() ? 'dashboard' : 'login');


/* ==========================================================
 * LOGIN
 * ========================================================== */

/**
 * El Login y el Registro son las únicas rutas públicas.
 *
 * Si el usuario ya está autenticado e intenta acceder
 * a cualquiera de estas vistas, será enviado al Dashboard.
 */
if ($pagina === 'login' || $pagina === 'register') {

    /**
     * Usuario autenticado.
     */
    if (estaAutenticado()) {
        header( 'Location: ' . BASE_URL . 'index.php?page=dashboard');
        exit;
    }

    /**
     * Cargar la vista solicitada.
     */
    if ($pagina === 'login') {
        require_once ROOT_PATH . '/app/views/auth/login.php';
        exit;
    }

    if ($pagina === 'register') {
        require_once ROOT_PATH . '/app/views/auth/register.php';
        exit;
    }
}

/* ==========================================================
 * PROTECCIÓN DE RUTAS
 * ========================================================== */

/**
 * Todas las rutas diferentes de Login requieren
 * una sesión válida de administrador.
 *
 * Esta protección se ejecuta antes de cargar cualquier
 * controlador o vista del sistema.
 */

exigirAdministrador();

/* ==========================================================
 * ENRUTAMIENTO
 * ========================================================== */

switch ($pagina) {

    /* ==========================================================
     * DASHBOARD
     * ========================================================== */

    case 'dashboard':

        require_once __DIR__ . '/app/controllers/DashboardController.php';

        /**
         * Crear controlador.
         */
        $dashboardController = new DashboardController();

        /**
         * Obtener información del Dashboard.
         */        
        $totalProductos = $dashboardController->contarProductos();
        $ultimosProductos = $dashboardController->listarUltimosProductos();
        $totalStockBajo = $dashboardController->contarStockBajo();
        $resumenVentas  = $dashboardController->obtenerResumenVentas();
        $totalVentas    = $resumenVentas['ventas_pagadas'];
        $totalIngresos  = $resumenVentas['ingresos_totales'];

        /**
         * Página actual.
         */        
        $paginaActual = 'dashboard';

        /**
         * Cargar vista.
         */        
        require_once ROOT_PATH . '/app/views/dashboard/index.php';

        break;

    /* ==========================================================
     * PRODUCTOS
     * ========================================================== */

    case 'productos':
        
        /**
         * Crear controlador.
         */
        require_once ROOT_PATH . '/app/controllers/ProductoController.php';
        
        /**
         * Obtener productos.
         */
        $productoController = new ProductoController();        
        $productos = $productoController->listarProductos();
        
        /**
         * Página actual.
         */  
        $paginaActual = 'productos';
        
        /**
         * Cargar vista.
         */ 
        require_once ROOT_PATH . '/app/views/productos/productos.php';
        
        break;

    /* ==========================================================
     * VENTAS
     * ========================================================== */

    case 'ventas':

        /**
         * Página actual.
         */
        $paginaActual = 'ventas';

        /**
         * Cargar vista.
         */
        require_once ROOT_PATH . '/app/views/ventas/ventas.php';

        break;

    /* ==========================================================
     * MÓDULOS EN DESARROLLO
     * ========================================================== */

    case 'inventario':
    case 'ingresos':
    case 'gastos':
    case 'reportes':
    case 'configuracion':

    /**
     * Página actual.
     */        
    $paginaActual = $pagina;

    /**
     * Cargar vista genérica.
     */    
    require_once ROOT_PATH . '/app/views/sinpagina.php';

    break;

    /* ======================================================
     * PERFIL
     * =================================================== */

    case 'perfil':

        $paginaActual = 'perfil';

        require_once ROOT_PATH . '/app/views/perfil/perfil.php';

        break;

    /* ==========================================================
     * RUTA NO ENCONTRADA (404)
     * ========================================================== */

    default:

        /**
        * Página de mantenimiento / ruta no encontrada.
        */
        $paginaActual = 'mantenimiento';

        require_once ROOT_PATH . '/app/views/sinpagina.php';
        
        break;
}