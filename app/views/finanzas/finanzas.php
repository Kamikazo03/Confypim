<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Vista compartida de los módulos financieros
 * ==========================================================
 * Presenta las secciones de Ingresos, Gastos y Reportes.
 * La información se consulta mediante ajax/finanzas.php.
 * ==========================================================
 */

$tipoFinanzas = $paginaActual;
$esReportes = $tipoFinanzas === 'reportes';
$jsPagina = JS_URL . 'finanzas.js';
$jsPaginaFile = ROOT_PATH . '/assets/js/finanzas.js';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include ROOT_PATH . '/includes/head.php'; ?>
    <link
        rel="stylesheet"
        href="<?= CSS_URL ?>finanzas.css?v=<?= filemtime(ROOT_PATH . '/assets/css/finanzas.css'); ?>"
    >
</head>

<body>

    <!-- =======================================================
         Aplicación
    ======================================================== -->

    <div class="app">

        <!-- =======================================================
             Barra lateral
        ======================================================== -->

        <?php include ROOT_PATH . '/includes/sidebar.php'; ?>

        <!-- =======================================================
             Contenido principal
        ======================================================== -->

        <div class="content">

            <?php include ROOT_PATH . '/includes/topbar.php'; ?>

            <main
                class="main finanzas-main"
                id="finanzasApp"
                data-tipo="<?= htmlspecialchars($tipoFinanzas, ENT_QUOTES, 'UTF-8'); ?>"
                data-api="<?= BASE_URL ?>ajax/<?= htmlspecialchars($tipoFinanzas, ENT_QUOTES, 'UTF-8'); ?>.php"
            >

                <!-- ===================================================
                     Filtro de periodo
                ==================================================== -->

                <section class="finanzas-toolbar" aria-label="Filtros del periodo">
                    <div class="finanzas-periodo">
                        <label for="periodoFecha">Periodo</label>

                        <select id="periodoFecha" aria-label="Seleccionar periodo">
                            <option value="hoy">Hoy</option>
                            <option value="ultimos7">Últimos 7 días</option>
                            <option value="ultimos15">Últimos 15 días</option>
                            <option value="mes_actual" selected>Mes actual</option>
                            <option value="anio_actual">Año actual</option>
                            <option value="personalizado">Rango personalizado</option>
                        </select>

                        <div class="finanzas-rango-personalizado" id="rangoPersonalizado" hidden>
                            <label for="fechaDesde">Desde</label>
                            <input type="date" id="fechaDesde">

                            <label for="fechaHasta">Hasta</label>
                            <input type="date" id="fechaHasta">
                        </div>

                        <button
                            type="button"
                            class="finanzas-btn finanzas-btn-primary"
                            id="aplicarFechas"
                            hidden
                        >
                            Aplicar
                        </button>
                    </div>
                </section>

                <!-- ===================================================
                     Navegación por secciones
                ==================================================== -->

                <nav class="finanzas-tabs" aria-label="Secciones del módulo">
                    <?php if ($esReportes): ?>
                        <button class="finanzas-tab active" data-tab="resumen">Resumen</button>
                        <button class="finanzas-tab" data-tab="balance">Balance</button>
                        <button class="finanzas-tab" data-tab="historial">Historial</button>
                    <?php else: ?>
                        <button class="finanzas-tab active" data-tab="resumen">Resumen</button>
                        <button class="finanzas-tab" data-tab="movimientos">Movimientos</button>

                        <?php if ($tipoFinanzas === 'ingresos'): ?>
                            <button class="finanzas-tab" data-tab="productos">Productos</button>
                        <?php endif; ?>

                        <button class="finanzas-tab" data-tab="categorias">Categorías</button>
                    <?php endif; ?>
                </nav>

                <!-- ===================================================
                     Resumen
                ==================================================== -->

                <section class="finanzas-panel active" data-panel="resumen">
                    <div class="finanzas-kpis" id="finanzasKpis"></div>

                    <div class="finanzas-charts">
                        <article class="finanzas-card finanzas-chart-card">
                            <div class="finanzas-card-heading">
                                <div>
                                    <h2 id="evolucionTitulo">Evolución</h2>
                                    <p>Comportamiento durante el periodo seleccionado</p>
                                </div>
                            </div>

                            <div class="finanzas-chart-wrap">
                                <canvas id="evolucionChart"></canvas>
                            </div>
                        </article>

                        <article class="finanzas-card finanzas-chart-card">
                            <div class="finanzas-card-heading">
                                <div>
                                    <h2>Distribución por categoría</h2>
                                    <p>Participación de cada categoría</p>
                                </div>
                            </div>

                            <div class="finanzas-chart-wrap">
                                <canvas id="categoriasChart"></canvas>
                            </div>
                        </article>
                    </div>
                </section>

                <!-- ===================================================
                     Movimientos
                ==================================================== -->

                <section class="finanzas-panel" data-panel="movimientos">
                    <article class="finanzas-card">
                        <div class="finanzas-card-heading">
                            <div>
                                <h2>Movimientos registrados</h2>
                                <p>Consulta los registros y su categoría correspondiente</p>
                            </div>

                            <input
                                class="finanzas-search"
                                type="search"
                                id="buscarMovimiento"
                                placeholder="Buscar movimiento..."
                            >
                        </div>

                        <div class="finanzas-filtros">
                            <label for="filtroCategoria">Categoría</label>
                            <select id="filtroCategoria">
                                <option value="">Todas las categorías</option>
                            </select>
                        </div>

                        <div class="finanzas-table-wrap">
                            <table class="finanzas-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Concepto / origen</th>
                                        <th>Categoría</th>
                                        <th>Tipo</th>
                                        <th class="text-right">Valor</th>
                                    </tr>
                                </thead>

                                <tbody id="movimientosBody">
                                    <tr>
                                        <td colspan="5" class="finanzas-empty">
                                            Cargando movimientos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="finanzas-footnote">
                            Los movimientos vinculados a ventas o compras se administran desde sus módulos
                            de origen para conservar la integridad de los registros.
                        </p>
                    </article>
                </section>

                <!-- ===================================================
                     Rendimiento de productos (solo Ingresos)
                ==================================================== -->

                <?php if (!$esReportes && $tipoFinanzas === 'ingresos'): ?>
                    <section class="finanzas-panel" data-panel="productos">
                        <article class="finanzas-card">
                            <div class="finanzas-card-heading">
                                <div>
                                    <h2>Rendimiento de productos</h2>
                                    <p>Ventas pagadas, calculadas con los valores históricos de cada detalle</p>
                                </div>
                            </div>

                            <div class="finanzas-table-wrap">
                                <table class="finanzas-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Unidades vendidas</th>
                                            <th class="text-right">Ingresos generados</th>
                                        </tr>
                                    </thead>

                                    <tbody id="productosBody">
                                        <tr>
                                            <td colspan="3" class="finanzas-empty">
                                                Cargando productos...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    </section>
                <?php endif; ?>

                <!-- ===================================================
                     Categorías
                ==================================================== -->

                <section class="finanzas-panel" data-panel="categorias">
                    <div class="finanzas-kpis" id="categoriasKpis"></div>

                    <article class="finanzas-card">
                        <div class="finanzas-card-heading">
                            <div>
                                <h2>Categorías</h2>
                                <p>Las categorías se obtienen directamente del catálogo de Confypim</p>
                            </div>
                        </div>

                        <div id="categoriasLista" class="finanzas-categorias-lista"></div>
                    </article>
                </section>

                <!-- ===================================================
                     Balance e historial (solo Reportes)
                ==================================================== -->

                <?php if ($esReportes): ?>
                    <section class="finanzas-panel" data-panel="balance">
                        <div class="finanzas-kpis" id="balanceKpis"></div>

                        <article class="finanzas-card">
                            <div class="finanzas-card-heading">
                                <div>
                                    <h2>Balance por día</h2>
                                    <p>Ingresos menos gastos; el acumulado corresponde al periodo consultado</p>
                                </div>
                            </div>

                            <div class="finanzas-table-wrap">
                                <table class="finanzas-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th class="text-right">Ingresos</th>
                                            <th class="text-right">Gastos</th>
                                            <th class="text-right">Balance del día</th>
                                            <th class="text-right">Acumulado</th>
                                        </tr>
                                    </thead>

                                    <tbody id="balanceBody"></tbody>
                                </table>
                            </div>
                        </article>
                    </section>

                    <section class="finanzas-panel" data-panel="historial">
                        <article class="finanzas-card">
                            <div class="finanzas-card-heading">
                                <div>
                                    <h2>Reportes guardados</h2>
                                    <p>Instantáneas históricas del periodo que seleccionaste</p>
                                </div>

                                <button
                                    type="button"
                                    class="finanzas-btn finanzas-btn-primary"
                                    id="guardarReporte"
                                >
                                    Guardar reporte del periodo
                                </button>
                            </div>

                            <div class="finanzas-table-wrap">
                                <table class="finanzas-table">
                                    <thead>
                                        <tr>
                                            <th>Generado</th>
                                            <th>Periodo</th>
                                            <th class="text-right">Ingresos</th>
                                            <th class="text-right">Gastos</th>
                                            <th class="text-right">Balance</th>
                                        </tr>
                                    </thead>

                                    <tbody id="historialBody"></tbody>
                                </table>
                            </div>
                        </article>
                    </section>
                <?php endif; ?>

                <div
                    class="finanzas-toast"
                    id="finanzasToast"
                    role="status"
                    aria-live="polite"
                ></div>
            </main>

            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>

            <?php include ROOT_PATH . '/includes/footer.php'; ?>

        </div>
    </div>

</body>
</html>
