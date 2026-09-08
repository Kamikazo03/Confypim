<?php
/**==========================================================
 * Productos
 * ==========================================================
 *
 * Vista encargada de administrar los productos registrados
 * dentro del sistema.
 *
 * Permite crear, editar, consultar y desactivar productos
 * mediante una interfaz CRUD conectada al controlador.
 * ==========================================================
 */
$jsPagina = JS_URL . "productos.js";
$jsPaginaFile = ROOT_PATH . "/assets/js/productos.js";
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

            <div class="crud-layout">

                <!-- =======================================================
                    Panel de formulario
                ======================================================== -->

                <aside class="crud-sidebar">
                    <section class="form-card sticky-card">
                        <div class="card-title">
                            <h2 id="tituloFormulario">🧁 Registrar producto</h2>
                            <p id="descripcionFormulario">
                            Complete la información del producto para agregarlo al inventario.
                            </p>
                        </div>
                        <form id="formProducto">
                            <input type="hidden" id="idProducto" value="">

                            <!-- =======================================================
                                Datos básicos
                            ======================================================== -->

                            <div class="form-group">
                                <label for="nombre">Nombre del producto</label>
                                <input
                                    id="nombre"
                                    name="nombre"
                                    type="text"
                                    placeholder="Ej: Cheesecake de Fresa"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea
                                    id="descripcion"
                                    name="descripcion"
                                    rows="4"
                                    placeholder="Describe brevemente el producto..."></textarea>
                            </div>

                            <!-- =======================================================
                                Información comercial
                            ======================================================== -->

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="precio">Precio</label>
                                    <input
                                        id="precio"
                                        name="precio"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="$"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label for="stock">Stock</label>
                                    <input
                                        id="stock"
                                        name="stock"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        required>
                                </div>
                            </div>

                            <!-- =======================================================
                                Acciones
                            ======================================================== -->

                            <div class="buttons">
                                <button
                                    id="btnGuardar"
                                    type="submit"
                                    class="btn-primary">
                                    Guardar Producto
                                </button>
                                <button
                                    id="btnCancelar"
                                    type="button"
                                    class="btn-secondary">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </section>
                </aside>

                <!-- =======================================================
                    Listado de productos
                ======================================================== -->

                <section class="crud-content">

                    <section class="table-card">
                        <div class="card-title">
                            <h2>📦 Productos registrados</h2>
                            <p>
                                Consulta los productos almacenados en el sistema.
                            </p>
                        </div>

                        <div id="tablaProductos">
                            <p style="padding:30px;text-align:center;color:#888;">
                                Cargando productos...
                            </p>
                        </div>
                    </section>
                </section>
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