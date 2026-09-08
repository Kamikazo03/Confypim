<!-- ==========================================================
 * Dashboard
 * ==========================================================
 *
 * Vista principal del sistema.
 *
 * Muestra el resumen general del negocio mediante
 * tarjetas informativas y el listado de los últimos
 * productos registrados.
 * ========================================================== -->

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

            <!-- =======================================================
                Tarjetas resumen
            ======================================================== -->

            <section class="cards">

                <div class="card">
                    <h3>Productos</h3>
                    <p><?= $totalProductos ?></p>
                </div>

                <div class="card">
                    <h3>Ventas</h3>
                    <p>8</p>
                </div>

                <div class="card">
                    <h3>Ingresos</h3>
                    <p>$430.000</p>
                </div>

                <div class="card">
                    <h3>Stock Bajo</h3>
                    <p><?= $totalStockBajo ?></p>
                </div>

            </section>

            <!-- =======================================================
                Últimos productos registrados
            ======================================================== -->

            <section class="table-section">

                <h2>Últimos Productos</h2>

                <table>

                    <thead>

                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Disponibilidad</th>
                        </tr>

                    </thead>

                    <tbody>
                    <?php foreach ($ultimosProductos as $producto): ?>                  
                    <tr>
                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                        <td>$<?= number_format($producto['precio_unitario'], 0, ',', '.') ?></td>
                        <td><?= $producto['stock_disponible'] ?></td>
                        <td>
                            <span class="badge <?= strtolower(str_replace(' ', '-', $producto['estado'])) ?>">
                                <?= $producto['estado'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>

                </table>

            </section>

        </main>

        <!-- =======================================================
            Pie de página
        ======================================================== -->
        
        <?php include ROOT_PATH . '/includes/footer.php'; ?>

    </div>

</div>

</body>
</html>