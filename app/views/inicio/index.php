<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Vista de Inicio público
 * ==========================================================
 * Página de bienvenida para visitantes que todavía no han
 * iniciado sesión. Presenta el propósito y los módulos del
 * sistema, y facilita el acceso al Login y al Registro.
 * ==========================================================
 */

$paginaActual = 'inicio';
$urlLogin = BASE_URL . 'index.php?page=login';
$urlRegistro = BASE_URL . 'index.php?page=register';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php require_once ROOT_PATH . '/includes/head.php'; ?>
    <meta name="description" content="Confypim: una herramienta para organizar productos, ventas, insumos e información financiera de pequeños emprendimientos de repostería.">
</head>

<body class="inicio-page">

    <a class="inicio-skip-link" href="#contenido">Saltar al contenido principal</a>

    <!-- ======================================================
         Barra superior fija
    ======================================================= -->
    <header class="inicio-navbar">
        <div class="inicio-navbar-inner">
            <a class="inicio-brand" href="<?= BASE_URL ?>" aria-label="Confypim, página de inicio">
                <span class="inicio-brand-mark">
                    <img src="<?= IMG_URL ?>favicon/apple-touch-icon.png?v=<?= APP_VERSION; ?>" alt="" aria-hidden="true">
                </span>
                <span class="inicio-brand-name">Confypim</span>
            </a>

            <nav class="inicio-nav-actions" aria-label="Navegación principal">
                <a class="inicio-nav-login" href="<?= $urlLogin ?>">Iniciar sesión</a>
                <a class="inicio-button inicio-button-small" href="<?= $urlRegistro ?>">Registrarse</a>
            </nav>
        </div>
    </header>

    <main id="contenido">

        <!-- ==================================================
             Presentación principal
        =================================================== -->
        <section class="inicio-hero">
            <div class="inicio-container inicio-hero-grid">
                <div class="inicio-hero-copy">
                    <span class="inicio-eyebrow">
                        <i class="fa-solid fa-heart" aria-hidden="true"></i>
                        Organización para emprender con tranquilidad
                    </span>

                    <h1>Tu emprendimiento merece <span>crecer con orden.</span></h1>

                    <p class="inicio-hero-description">
                        Administra los productos, las ventas, los insumos y las finanzas de tu negocio de repostería desde un solo lugar. Confypim te ayuda a mantener cada detalle bajo control para que puedas concentrarte en crear.
                    </p>

                    <div class="inicio-hero-actions">
                        <a class="inicio-button" href="<?= $urlRegistro ?>">
                            Crear mi cuenta
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <a class="inicio-button-outline" href="<?= $urlLogin ?>">
                            Ya tengo una cuenta
                        </a>
                    </div>

                    <div class="inicio-hero-note">
                        <i class="fa-solid fa-shield-heart" aria-hidden="true"></i>
                        <span>Tu espacio de trabajo para gestionar tu negocio.</span>
                    </div>
                </div>

                <div class="inicio-hero-visual" aria-label="Vista ilustrativa de Confypim">
                    <div class="inicio-visual-glow"></div>
                    <div class="inicio-visual-decoration inicio-decoration-one"></div>
                    <div class="inicio-visual-decoration inicio-decoration-two"></div>

                    <div class="inicio-preview-card">
                        <div class="inicio-preview-topline">
                            <div>
                                <span class="inicio-preview-overline">PANEL DE CONTROL</span>
                                <h2>¡Hola, Administrador!</h2>
                                <p>Este es el resumen de tu emprendimiento.</p>
                            </div>
                            <span class="inicio-preview-avatar">A</span>
                        </div>

                        <div class="inicio-preview-stats">
                            <div class="inicio-preview-stat">
                                <span>Productos</span>
                                <strong>24</strong>
                                <small><i class="fa-solid fa-cube"></i> En catálogo</small>
                            </div>
                            <div class="inicio-preview-stat">
                                <span>Ventas del mes</span>
                                <strong>$1.2M</strong>
                                <small><i class="fa-solid fa-chart-line"></i> Ejemplo ilustrativo</small>
                            </div>
                        </div>

                        <div class="inicio-preview-chart">
                            <div class="inicio-preview-chart-heading">
                                <strong>Actividad del negocio</strong>
                                <span>Últimos días</span>
                            </div>
                            <div class="inicio-chart-bars" aria-hidden="true">
                                <span style="--bar-height: 38%"></span>
                                <span style="--bar-height: 58%"></span>
                                <span style="--bar-height: 46%"></span>
                                <span style="--bar-height: 78%"></span>
                                <span style="--bar-height: 62%"></span>
                                <span style="--bar-height: 92%"></span>
                                <span style="--bar-height: 72%"></span>
                            </div>
                            <div class="inicio-chart-labels"><span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span></div>
                        </div>
                    </div>

                    <div class="inicio-cupcake-card">
                        <img src="<?= IMG_URL ?>Logo_Confypim.png" alt="Ilustración del logo de Confypim con un cupcake">
                    </div>
                    <span class="inicio-floating-heart"><i class="fa-solid fa-heart"></i></span>
                </div>
            </div>
        </section>

        <!-- ==================================================
             Funcionalidades
        =================================================== -->
        <section class="inicio-features" id="funcionalidades">
            <div class="inicio-container">
                <div class="inicio-section-heading">
                    <span class="inicio-eyebrow">Todo en un mismo lugar</span>
                    <h2>Las herramientas que tu negocio necesita</h2>
                    <p>Una forma más sencilla de organizar el día a día de tu emprendimiento.</p>
                </div>

                <div class="inicio-feature-grid">

                    <!-- Productos -->
                    <article class="inicio-feature-card">
                        <div class="inicio-feature-card-top">
                            <span class="inicio-feature-icon icon-products"><i class="fa-solid fa-cake-candles" aria-hidden="true"></i></span>
                            <span class="inicio-feature-label">Catálogo</span>
                        </div>
                        <h3>Productos</h3>
                        <p>Organiza tu catálogo, consulta precios y mantén a la vista la información de tus creaciones.</p>
                        <div class="inicio-mini-products" aria-label="Ejemplo ficticio de productos">
                            <div class="inicio-mini-product-row"><span class="mini-product-thumb thumb-cake"><i class="fa-solid fa-cake-candles"></i></span><span><b>Pastel de fresa</b><small>Disponible</small></span><strong>$65.000</strong></div>
                            <div class="inicio-mini-product-row"><span class="mini-product-thumb thumb-cupcake"><i class="fa-solid fa-cookie-bite"></i></span><span><b>Cupcakes de vainilla</b><small>Disponible</small></span><strong>$8.000</strong></div>
                        </div>
                    </article>

                    <!-- Ventas -->
                    <article class="inicio-feature-card">
                        <div class="inicio-feature-card-top">
                            <span class="inicio-feature-icon icon-sales"><i class="fa-solid fa-cash-register" aria-hidden="true"></i></span>
                            <span class="inicio-feature-label">Operación</span>
                        </div>
                        <h3>Ventas</h3>
                        <p>Registra las ventas, consulta sus detalles y lleva el seguimiento de cada operación.</p>
                        <div class="inicio-mini-sales" aria-label="Ejemplo ficticio de ventas">
                            <div class="inicio-mini-sales-head"><span>Venta</span><span>Estado</span><span>Total</span></div>
                            <div class="inicio-mini-sales-row"><b>#0018</b><span class="mini-status paid">Pagada</span><strong>$85.000</strong></div>
                            <div class="inicio-mini-sales-row"><b>#0019</b><span class="mini-status pending">Pendiente</span><strong>$42.000</strong></div>
                        </div>
                    </article>

                    <!-- Insumos -->
                    <article class="inicio-feature-card">
                        <div class="inicio-feature-card-top">
                            <span class="inicio-feature-icon icon-supplies"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></span>
                            <span class="inicio-feature-label">Inventario</span>
                        </div>
                        <h3>Insumos</h3>
                        <p>Controla ingredientes y materiales, revisa existencias y anticipa necesidades de compra.</p>
                        <div class="inicio-mini-inventory" aria-label="Ejemplo ficticio de inventario">
                            <div class="inicio-stock-row"><span><i class="fa-solid fa-wheat-awn"></i> Harina de trigo</span><b>12 kg</b><em class="stock-ok">Disponible</em></div>
                            <div class="inicio-stock-row"><span><i class="fa-solid fa-bottle-droplet"></i> Esencia de vainilla</span><b>2 und.</b><em class="stock-low">Stock bajo</em></div>
                            <div class="inicio-stock-progress"><span></span></div>
                        </div>
                    </article>

                    <!-- Reportes -->
                    <article class="inicio-feature-card">
                        <div class="inicio-feature-card-top">
                            <span class="inicio-feature-icon icon-reports"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i></span>
                            <span class="inicio-feature-label">Finanzas</span>
                        </div>
                        <h3>Reportes</h3>
                        <p>Consulta ingresos, gastos y balances para comprender mejor el movimiento de tu negocio.</p>
                        <div class="inicio-mini-report" aria-label="Ejemplo ficticio de resumen financiero">
                            <div class="inicio-report-total"><span>Balance ilustrativo</span><strong>$940.000</strong><small>Ingresos menos gastos</small></div>
                            <div class="inicio-report-legend"><span><i class="legend-income"></i> Ingresos <b>$1.200.000</b></span><span><i class="legend-expense"></i> Gastos <b>$260.000</b></span></div>
                        </div>
                    </article>
                </div>

                <p class="inicio-demo-disclaimer"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Los nombres, cantidades y valores de estas tarjetas son ejemplos ficticios con fines ilustrativos.</p>
            </div>
        </section>

        <!-- ==================================================
             Cierre / propósito
        =================================================== -->
        <section class="inicio-purpose">
            <div class="inicio-container inicio-purpose-inner">
                <div class="inicio-purpose-icon"><i class="fa-solid fa-heart" aria-hidden="true"></i></div>
                <div>
                    <span class="inicio-eyebrow">Hecho para pequeños emprendimientos</span>
                    <h2>Más tiempo para crear. Más claridad para crecer.</h2>
                    <p>Confypim reúne herramientas de gestión pensadas para apoyar a quienes convierten su creatividad y esfuerzo en un emprendimiento.</p>
                </div>
                <a class="inicio-button" href="<?= $urlRegistro ?>">Comenzar ahora <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </section>

    </main>

    <!-- ======================================================
         Pie de página
    ======================================================= -->
    <footer class="inicio-footer">
        <div class="inicio-container inicio-footer-inner">
            <a class="inicio-footer-brand" href="<?= BASE_URL ?>">
                <span class="inicio-footer-mark"><i class="fa-solid fa-cookie-bite" aria-hidden="true"></i></span>
                <strong>Confypim</strong>
            </a>
            <span>© <?= date('Y'); ?> <?= APP_NAME; ?> · Sistema de Gestión Financiera</span>
            <span>Versión <?= APP_VERSION; ?></span>
        </div>
    </footer>

</body>
</html>
