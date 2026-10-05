<?php
declare(strict_types=1);

/**==========================================================
 * Ventas
 * ==========================================================
 *
 * Vista principal del módulo de Ventas.
 *
 * Mantiene la estructura general de Confypim mediante
 * los componentes compartidos de la aplicación.
 *
 * React se encarga de renderizar el contenido del módulo.
 * ==========================================================
 */
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
                Contenido de Ventas mediante React
            ======================================================== -->

            <main class="main">

                <!-- Punto de montaje de React -->

                <div id="ventas-react-root"></div>

            </main>

            <!-- =======================================================
                Carga del módulo React
            ======================================================== -->

            <script src="<?= BASE_URL ?>assets/react/ventas/ventas.iife.js"></script>

            <!-- =======================================================
                Pie de página
            ======================================================== -->

            <?php include ROOT_PATH . '/includes/footer.php'; ?>

        </div>

    </div>

</body>
</html>