<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Ventas
 * ==========================================================
 *
 * Vista principal del módulo Ventas.
 *
 * En esta primera etapa se presenta únicamente la estructura
 * visual del módulo y datos temporales para realizar pruebas
 * independientes antes de integrar la lógica MVC y la base
 * de datos.
 *
 * ==========================================================
 */

$jsPagina = JS_URL . "ventas.js";
$jsPaginaFile = ROOT_PATH . "/assets/js/ventas.js";

/* ==========================================================
 * DATOS TEMPORALES
 * ========================================================== */

/**
 * Estos valores serán reemplazados posteriormente por
 * información obtenida mediante el controlador de ventas.
 */
$ventasPagadas = 2450000;
$productosVendidos = 47;
$productosDiferentes = 13;
$ventasRealizadas = 18;

/**
 * Ventas utilizadas únicamente para comprobar
 * la presentación de la tabla.
 */
$ventasRecientes = [

    [
        'id' => 25,
        'cliente' => 'María López',
        'total' => 95000,
        'estado' => 'Pendiente'
    ],

    [
        'id' => 24,
        'cliente' => 'Carlos Pérez',
        'total' => 42000,
        'estado' => 'Pagada'
    ],

    [
        'id' => 23,
        'cliente' => 'Ana Torres',
        'total' => 68000,
        'estado' => 'Pagada'
    ],

    [
        'id' => 22,
        'cliente' => 'Luis Gómez',
        'total' => 35000,
        'estado' => 'Pendiente'
    ],

    [
        'id' => 21,
        'cliente' => 'Pedro Díaz',
        'total' => 51000,
        'estado' => 'Pagada'
    ]

];

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

    <!-- ===================================================
         Barra lateral
    ==================================================== -->

    <?php include ROOT_PATH . '/includes/sidebar.php'; ?>


    <!-- ===================================================
         Contenido principal
    ==================================================== -->

    <div class="content">

        <!-- =================================================
             Barra superior
        ================================================== -->

        <?php include ROOT_PATH . '/includes/topbar.php'; ?>


        <!-- =================================================
             Contenido de la página
        ================================================== -->

        <main class="main">

            <!-- =================================================
                 Encabezado del módulo
            ================================================== -->

            <div class="actions"> 
                <button 
                    type="button" 
                    class="btn-primary" 
                    id="btnNuevaVenta"> 
                    + Nueva venta 
                </button> 
            </div>


            <!-- =================================================
                 Tarjetas de resumen
            ================================================== -->

            <section class="cards sales-cards">

                <!-- =================================================
                     Ventas pagadas
                ================================================== -->

                <article class="card">

                    <h3>Ventas pagadas</h3>

                    <p>
                        $<?= number_format(
                            $ventasPagadas,
                            0,
                            ',',
                            '.'
                        ); ?>
                    </p>

                </article>


                <!-- =================================================
                     Productos vendidos
                ================================================== -->

                <article class="card">

                    <h3>Productos vendidos</h3>

                    <p>
                        <?= $productosVendidos; ?>
                    </p>

                </article>


                <!-- =================================================
                     Productos diferentes
                ================================================== -->

                <article class="card">

                    <h3>Productos diferentes</h3>

                    <p>
                        <?= $productosDiferentes; ?>
                    </p>

                </article>


                <!-- =================================================
                     Ventas realizadas
                ================================================== -->

                <article class="card">

                    <h3>Ventas realizadas</h3>

                    <p>
                        <?= $ventasRealizadas; ?>
                    </p>

                </article>

            </section>


            <!-- =================================================
                 Filtros
            ================================================== -->

            <section class="sales-filters">

                <div class="form-group">

                    <label for="buscarVenta">
                        Buscar
                    </label>

                    <input
                        type="search"
                        id="buscarVenta"
                        placeholder="Buscar por cliente o número de venta..."
                    >

                </div>


                <div class="form-group">

                    <label for="estadoVenta">
                        Estado
                    </label>

                    <select id="estadoVenta">

                        <option value="todos">
                            Todos
                        </option>

                        <option value="pagada">
                            Pagada
                        </option>

                        <option value="pendiente">
                            Pendiente
                        </option>

                    </select>

                </div>

            </section>


            <!-- =================================================
                 Ventas recientes
            ================================================== -->

            <section class="table-card">

                <div class="card-title">

                    <h2>
                        🛒 Ventas recientes
                    </h2>

                    <p>
                        Consulta las últimas ventas registradas
                        en el sistema.
                    </p>

                </div>


                <div id="tablaVentas">

                    <table class="table-products">

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Cliente
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($ventasRecientes as $venta): ?>

                                <?php

                                $claseEstado = strtolower(
                                    $venta['estado']
                                );

                                ?>

                                <tr>

                                    <td>
                                        #<?= $venta['id']; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $venta['cliente'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </td>

                                    <td>

                                        $<?= number_format(
                                            $venta['total'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>

                                    </td>

                                    <td>

                                        <span
                                            class="badge <?= $claseEstado; ?>">

                                            <?= $venta['estado']; ?>

                                        </span>

                                    </td>

                                    <td>

                                        <div class="actions">

                                            <button
                                                type="button"
                                                class="btn-action edit btn-ver-venta"
                                                data-id="<?= $venta['id']; ?>"
                                                title="Ver venta"
                                                aria-label="Ver venta">

                                                <i class="fa-solid fa-eye"></i>

                                            </button>

                                            <?php if ($venta['estado'] === 'Pendiente'): ?>

                                                <button
                                                    type="button"
                                                    class="btn-action delete btn-editar-venta"
                                                    data-id="<?= $venta['id']; ?>"
                                                    title="Editar venta"
                                                    aria-label="Editar venta">

                                                    <i class="fa-solid fa-pen"></i>

                                                </button>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>


        <!-- =================================================
             Pie de página
        ================================================== -->

        <?php include ROOT_PATH . '/includes/footer.php'; ?>

    </div>

</div>

</body>

</html>