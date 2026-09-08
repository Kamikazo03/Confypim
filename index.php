<?php

declare(strict_types=1);

/**=====================================================
 * Front Controller
 * =====================================================
 * Punto único de entrada de la aplicación.
 * Desde aquí se carga la configuración,
 * los controladores y la vista correspondiente.
 * =====================================================*/

require_once __DIR__ . '/config/app.php';

/* ==========================================================
 * INICIALIZACIÓN
 * ========================================================== */

/**
 * Página solicitada.
 *
 * Si no existe el parámetro "page",
 * se carga el Dashboard por defecto.
 */
$pagina = filter_input(INPUT_GET,'page',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'dashboard';

/* ==========================================================
 * ENRUTAMIENTO
 * ========================================================== */

switch ($pagina) {

    /* ==========================================================
     * DASHBOARD
     * ========================================================== */

    case 'dashboard':

        require_once __DIR__ . '/app/controllers/DashboardController.php';

        $dashboardController = new DashboardController();

        $totalProductos = $dashboardController->contarProductos();
        $ultimosProductos = $dashboardController->listarUltimosProductos();
        $totalStockBajo = $dashboardController->contarStockBajo();

        $paginaActual = 'dashboard';

        require_once __DIR__ . '/app/views/dashboard/index.php';

        break;

    /* ==========================================================
     * PRODUCTOS
     * ========================================================== */

    case 'productos':
        
        require_once __DIR__ . '/app/controllers/ProductoController.php';
        
        $productoController = new ProductoController();
        
        $productos = $productoController->listarProductos();
        
        $paginaActual = 'productos';
        
        require_once __DIR__ . '/app/views/productos/productos.php';
        
        break;

    /* ==========================================================
     * VENTAS
     * ========================================================== */

    case 'ventas':

        require_once __DIR__ . '/app/controllers/ProductoController.php';

        $productoController = new ProductoController();

        $productos = $productoController->listarProductos();

        $paginaActual = 'ventas';

        require_once __DIR__ . '/app/views/ventas/ventas.php';

        break;

    /* ==========================================================
     * MÓDULOS EN DESARROLLO
     * ========================================================== */

    case 'inventario':
    case 'ingresos':
    case 'gastos':
    case 'reportes':
    case 'configuracion':

    $paginaActual = $pagina;

    require_once __DIR__ . '/app/views/sinpagina.php';

    break;

    /* ==========================================================
     * RUTA NO ENCONTRADA (404)
     * ========================================================== */

    default:

        $paginaActual = 'mantenimiento';

        require_once __DIR__ . '/app/views/sinpagina.php';
        
        break;
}