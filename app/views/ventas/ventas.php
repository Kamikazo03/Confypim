<?php
/**==========================================================
 * Ventas
 * ==========================================================
 *
 * Vista encargada de registrar nuevas ventas dentro del
 * sistema Confipym.
 *
 * Permite consultar los productos disponibles, agregarlos
 * a una venta temporal y calcular automáticamente el total
 * antes de realizar el registro de la venta.
 * ==========================================================
 */
$jsPagina = JS_URL . "ventas.js";
$jsPaginaFile = ROOT_PATH . "/assets/js/ventas.js";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include ROOT_PATH . '/includes/head.php'; ?>
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

        <!-- =======================================================
            Barra superior
        ======================================================== -->

        <?php include ROOT_PATH . '/includes/topbar.php'; ?>


        <!-- =======================================================
            Contenido de la página
        ======================================================== -->

        <main class="main">

            <div class="ventas-layout">

                <!-- =======================================================
                    Productos disponibles
                ======================================================== -->

                <section class="ventas-productos">

                    <section class="table-card">

                        <div class="card-title">
                            <h2>🧁 Productos disponibles</h2>
                            <p>
                                Selecciona los productos que deseas agregar
                                a la venta actual.
                            </p>
                        </div>


                        <!-- =======================================================
                            Buscador
                        ======================================================== -->

                        <div class="ventas-search">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="search"
                                id="buscarProductoVenta"
                                placeholder="Buscar producto...">

                        </div>


                        <!-- =======================================================
                            Listado de productos
                        ======================================================== -->

                        <div
                            class="productos-venta-lista"
                            id="productosVenta">

                            <?php if (empty($productos)): ?>

                                <p class="ventas-empty">
                                    No hay productos disponibles actualmente.
                                </p>

                            <?php else: ?>

                                <?php foreach ($productos as $producto): ?>

                                    <article
                                        class="producto-venta-card"
                                        data-id="<?= (int)$producto['id_producto']; ?>"
                                        data-nombre="<?= htmlspecialchars($producto['nombre']); ?>"
                                        data-precio="<?= (float)$producto['precio_unitario']; ?>"
                                        data-stock="<?= (int)$producto['stock_disponible']; ?>">

                                        <div class="producto-venta-info">

                                            <h3>
                                                <?= htmlspecialchars($producto['nombre']); ?>
                                            </h3>

                                            <?php if (!empty($producto['descripcion'])): ?>

                                                <p>
                                                    <?= htmlspecialchars($producto['descripcion']); ?>
                                                </p>

                                            <?php endif; ?>

                                        </div>


                                        <div class="producto-venta-details">

                                            <span class="producto-precio">

                                                $<?= number_format(
                                                    (float)$producto['precio_unitario'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ); ?>

                                            </span>

                                            <span class="producto-stock">

                                                Stock:
                                                <?= (int)$producto['stock_disponible']; ?>

                                            </span>

                                        </div>


                                        <button
                                            type="button"
                                            class="btn-agregar-producto"
                                            data-agregar-producto>

                                            <i class="fa-solid fa-plus"></i>

                                            Agregar

                                        </button>

                                    </article>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                    </section>

                </section>


                <!-- =======================================================
                    Resumen de la venta
                ======================================================== -->

                <aside class="ventas-resumen">

                    <section class="form-card sticky-card">

                        <div class="card-title">

                            <h2>🛒 Nueva venta</h2>

                            <p>
                                Revisa los productos seleccionados antes
                                de registrar la venta.
                            </p>

                        </div>


                        <!-- =======================================================
                            Productos seleccionados
                        ======================================================== -->

                        <div
                            class="carrito-productos"
                            id="carritoProductos">

                            <div class="carrito-empty">

                                <i class="fa-solid fa-cart-shopping"></i>

                                <p>
                                    Aún no has agregado productos
                                    a la venta.
                                </p>

                            </div>

                        </div>


                        <!-- =======================================================
                            Resumen económico
                        ======================================================== -->

                        <div class="venta-total">

                            <div class="venta-total-row">

                                <span>Total productos</span>

                                <strong id="totalProductosVenta">
                                    0
                                </strong>

                            </div>


                            <div class="venta-total-row total-final">

                                <span>Total</span>

                                <strong id="totalVenta">
                                    $0
                                </strong>

                            </div>

                        </div>


                        <!-- =======================================================
                            Acciones
                        ======================================================== -->

                        <div class="buttons">

                            <button
                                type="button"
                                id="btnCancelarVenta"
                                class="btn-secondary">

                                Cancelar

                            </button>


                            <button
                                type="button"
                                id="btnRegistrarVenta"
                                class="btn-primary"
                                disabled>

                                Registrar venta

                            </button>

                        </div>

                    </section>

                </aside>

            </div>

        </main>


        <!-- =======================================================
            Pie de página
        ======================================================== -->

        <?php include ROOT_PATH . '/includes/footer.php'; ?>

    </div>

</div>

</body>
</html>