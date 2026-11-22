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
 * Si no existe el parámetro "page", se mostrará
 * Inicio para visitantes y el Dashboard para usuarios autenticados.
 */
$pagina = filter_input(
    INPUT_GET,
    'page',
    FILTER_SANITIZE_FULL_SPECIAL_CHARS
);

$pagina = $pagina ?: (estaAutenticado() ? 'dashboard' : 'inicio');


/* ==========================================================
 * RUTAS PÚBLICAS
 * ========================================================== */

/**
 * Inicio, Login y Registro son las rutas públicas.
 *
 * Si el usuario ya está autenticado e intenta acceder
 * a cualquiera de estas vistas, será enviado al Dashboard.
 */
if ($pagina === 'inicio' || $pagina === 'login' || $pagina === 'register') {

    /**
     * Usuario autenticado.
     */
    if (estaAutenticado()) {
        header('Location: ' . BASE_URL . 'index.php?page=dashboard');
        exit;
    }

    /**
     * Cargar la vista solicitada.
     */
    if ($pagina === 'inicio') {
        $paginaActual = 'inicio';
        require_once ROOT_PATH . '/app/views/inicio/index.php';
        exit;
    }

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
 * Todas las rutas diferentes de Inicio, Login y Registro
 * requieren una sesión válida de administrador.
 *
 * Si el usuario no ha iniciado sesión, será redirigido
 * a la vista Inicio.
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
     * INSUMOS
     * ========================================================== */

    case 'insumos':

        /**
         * Crear controlador.
         */
        require_once ROOT_PATH . '/app/controllers/InsumoController.php';

        $insumoController = new InsumoController();

        /**
         * Obtener indicadores iniciales del inventario.
         */
        $metricasInsumos = $insumoController->obtenerMetricas();

        /**
         * Página actual.
         */
        $paginaActual = 'insumos';

        /**
         * Cargar vista.
         */
        require_once ROOT_PATH . '/app/views/insumos/insumo.php';

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
     * INGRESOS, GASTOS Y REPORTES
     * ========================================================== */

    case 'ingresos':
    case 'gastos':
    case 'reportes':

        /**
         * Los tres módulos comparten una vista y recursos
         * visuales, pero mantienen rutas independientes.
         */
        $paginaActual = $pagina;

        if ($pagina === 'ingresos') {
            require_once ROOT_PATH . '/app/views/ingresos/ingresos.php';
        } elseif ($pagina === 'gastos') {
            require_once ROOT_PATH . '/app/views/gastos/gastos.php';
        } else {
            require_once ROOT_PATH . '/app/views/reportes/reportes.php';
        }

        break;

    /* ==========================================================
     * MÓDULOS EN DESARROLLO
     * ========================================================== */

    case 'inventario':
    case 'configuracion':

        $paginaActual = $pagina;

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