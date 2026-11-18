<?php
/**==========================================================
 * Insumos
 * ==========================================================
 *
 * Vista encargada de administrar los insumos utilizados
 * en la elaboración de los productos de Confypim.
 *
 * Permite consultar existencias, registrar compras,
 * controlar consumos y ajustes, consultar movimientos
 * y configurar las recetas de los productos.
 * ==========================================================
 */

$jsPagina = JS_URL . "insumos.js";
$jsPaginaFile = ROOT_PATH . "/assets/js/insumos.js";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include ROOT_PATH . '/includes/head.php'; ?>

    <!-- =======================================================
        Estilos exclusivos del módulo Insumos
    ======================================================== -->

    <link
        rel="stylesheet"
        href="<?= CSS_URL ?>insumo.css?v=<?= filemtime(ROOT_PATH . '/assets/css/insumo.css'); ?>">
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

        <main class="main insumos-main">


            <!-- =======================================================
                Indicadores
            ======================================================== -->

            <section class="insumos-metricas" aria-label="Resumen del inventario">

                <article class="insumo-metrica">
                    <span class="insumo-metrica-icono">📦</span>
                    <div>
                        <p>Insumos activos</p>
                        <strong id="totalInsumos">
                            <?= (int) ($metricasInsumos['total_insumos'] ?? 0); ?>
                        </strong>
                    </div>
                </article>

                <article class="insumo-metrica">
                    <span class="insumo-metrica-icono">⚠️</span>
                    <div>
                        <p>Stock bajo</p>
                        <strong id="insumosStockBajo">
                            <?= (int) ($metricasInsumos['stock_bajo'] ?? 0); ?>
                        </strong>
                    </div>
                </article>

                <article class="insumo-metrica">
                    <span class="insumo-metrica-icono">🚫</span>
                    <div>
                        <p>Agotados</p>
                        <strong id="insumosAgotados">
                            <?= (int) ($metricasInsumos['agotados'] ?? 0); ?>
                        </strong>
                    </div>
                </article>

                <article class="insumo-metrica">
                    <span class="insumo-metrica-icono">💰</span>
                    <div>
                        <p>Valor estimado del inventario</p>
                        <strong id="valorInventario">
                            $<?= number_format((float) ($metricasInsumos['valor_estimado'] ?? 0), 0, ',', '.'); ?>
                        </strong>
                    </div>
                </article>

            </section>

            <!-- =======================================================
                Navegación del módulo
            ======================================================== -->

            <nav class="insumos-tabs" aria-label="Secciones de insumos">
                <button type="button" class="insumo-tab active" data-tab="inventario">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    Inventario
                </button>

                <button type="button" class="insumo-tab" data-tab="compras">
                    <i class="fa-solid fa-cart-plus"></i>
                    Compras
                </button>

                <button type="button" class="insumo-tab" data-tab="movimientos">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    Consumos y ajustes
                </button>

                <button type="button" class="insumo-tab" data-tab="recetas">
                    <i class="fa-solid fa-book-open"></i>
                    Recetas
                </button>
            </nav>

            <!-- =======================================================
                INVENTARIO
            ======================================================== -->

            <section class="insumo-panel active" id="panel-inventario">

                <div class="insumo-layout">

                    <!-- =======================================================
                        Formulario de insumo
                    ======================================================== -->

                    <aside class="insumo-formulario-columna">
                        <section class="insumo-card insumo-form-card">

                            <div class="insumo-card-header">
                                <div>
                                    <h2 id="tituloFormularioInsumo">➕ Registrar insumo</h2>
                                    <p id="descripcionFormularioInsumo">
                                        Agrega un ingrediente o material a tu inventario.
                                    </p>
                                </div>
                            </div>

                            <form id="formInsumo">
                                <input type="hidden" id="idInsumo">

                                <div class="insumo-campo">
                                    <label for="nombreInsumo">Nombre del insumo</label>
                                    <input
                                        type="text"
                                        id="nombreInsumo"
                                        maxlength="100"
                                        placeholder="Ej: Harina de trigo"
                                        required>
                                </div>

                                <div class="insumo-campo">
                                    <label for="unidadInsumo">Unidad base de almacenamiento</label>
                                    <select id="unidadInsumo" required>
                                        <option value="g">Gramos (g)</option>
                                        <option value="ml">Mililitros (ml)</option>
                                        <option value="unidades">Unidades</option>
                                    </select>
                                    <small>
                                        Esta unidad se utilizará en el inventario y en las recetas.
                                        No se podrá cambiar después de crear el insumo.
                                    </small>
                                </div>

                                <div class="insumo-nota" id="notaStockInicial">
                                    El stock inicial será cero. Registra una compra o un ajuste
                                    para incorporar existencias.
                                </div>

                                <div class="insumo-form-acciones">
                                    <button type="submit" class="insumo-btn insumo-btn-principal" id="btnGuardarInsumo">
                                        Guardar insumo
                                    </button>

                                    <button type="button" class="insumo-btn insumo-btn-secundario" id="btnCancelarInsumo">
                                        Cancelar
                                    </button>
                                </div>
                            </form>

                        </section>

                        <!-- =======================================================
                            Papelera
                        ======================================================== -->

                        <section class="insumo-card insumo-papelera-card">
                            <div class="insumo-card-header">
                                <div>
                                    <h2><i class="fa-solid fa-trash-can"></i> Papelera</h2>
                                    <p>Insumos desactivados que puedes restaurar.</p>
                                </div>

                                <button type="button" class="insumo-btn-texto" id="btnCargarPapelera">
                                    Ver papelera
                                </button>
                            </div>

                            <div id="listaPapelera" class="insumo-papelera-lista">
                                <p class="insumo-vacio">La papelera está cerrada.</p>
                            </div>
                        </section>
                    </aside>

                    <!-- =======================================================
                        Tabla de inventario
                    ======================================================== -->

                    <section class="insumo-card insumo-tabla-card">

                        <div class="insumo-card-header insumo-listado-header">
                            <div>
                                <h2>Insumos registrados</h2>
                                <p>
                                    Consulta las existencias y el costo promedio de cada insumo.
                                    <span id="contadorInsumos" class="insumo-contador">...</span>
                                </p>
                            </div>

                            <div class="insumo-busqueda">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="buscarInsumo" placeholder="Buscar insumo...">
                            </div>
                        </div>

                        <div id="tablaInsumos" class="insumo-tabla-contenedor">
                            <p class="insumo-vacio">Cargando inventario...</p>
                        </div>

                    </section>

                </div>
            </section>

            <!-- =======================================================
                COMPRAS
            ======================================================== -->

            <section class="insumo-panel" id="panel-compras">

                <div class="insumo-panel-intro">
                    <h2>🛒 Registrar compra de insumos</h2>
                    <p>
                        Cada compra aumenta las existencias, actualiza el costo promedio
                        ponderado y registra un gasto asociado.
                    </p>
                </div>

                <section class="insumo-card insumo-operacion-card">
                    <div class="insumo-operacion-extra">
                        <p>¿Compraste varios ingredientes?</p>
                        <button type="button" class="insumo-btn insumo-btn-secundario" id="btnAbrirCompraMultiple">
                            <i class="fa-solid fa-layer-group"></i> Registrar varios insumos
                        </button>
                    </div>

                    <form id="formCompra">

                        <div class="insumo-form-grid">

                            <div class="insumo-campo">
                                <label for="insumoCompra">Insumo</label>
                                <select id="insumoCompra" required>
                                    <option value="">Selecciona un insumo</option>
                                </select>
                            </div>

                            <div class="insumo-campo">
                                <label for="cantidadCompra">Cantidad comprada</label>
                                <input
                                    type="number"
                                    id="cantidadCompra"
                                    min="0.001"
                                    step="0.001"
                                    placeholder="Ej: 2"
                                    required>
                            </div>

                            <div class="insumo-campo">
                                <label for="unidadCompra">Unidad de compra</label>
                                <select id="unidadCompra" required>
                                    <option value="">Selecciona un insumo primero</option>
                                </select>
                                <small id="ayudaUnidadCompra">
                                    Se convertirá automáticamente a la unidad base del insumo.
                                </small>
                            </div>

                            <div class="insumo-campo">
                                <label for="costoCompra">Costo total de la compra</label>
                                <input
                                    type="number"
                                    id="costoCompra"
                                    min="0.01"
                                    step="0.01"
                                    placeholder="Ej: 25000"
                                    required>
                            </div>

                            <div class="insumo-campo insumo-campo-completo">
                                <label for="observacionCompra">Observación (opcional)</label>
                                <textarea
                                    id="observacionCompra"
                                    rows="3"
                                    maxlength="255"
                                    placeholder="Proveedor, presentación, número de factura..."></textarea>
                            </div>

                        </div>

                        <div class="insumo-ayuda-operacion">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>
                                El costo total se distribuirá entre la cantidad comprada para
                                calcular el nuevo costo unitario promedio.
                            </span>
                        </div>

                        <div class="insumo-form-acciones">
                            <button type="submit" class="insumo-btn insumo-btn-principal">
                                Registrar compra
                            </button>
                            <button type="reset" class="insumo-btn insumo-btn-secundario">
                                Limpiar
                            </button>
                        </div>

                    </form>
                </section>
            </section>

            <!-- =======================================================
                CONSUMOS Y AJUSTES
            ======================================================== -->

            <section class="insumo-panel" id="panel-movimientos">

                <div class="insumo-panel-intro">
                    <h2>🔄 Consumos y ajustes de inventario</h2>
                    <p>
                        Registra el material utilizado o corrige las existencias cuando
                        sea necesario. Estas operaciones no crean ingresos ni gastos.
                    </p>
                </div>

                <section class="insumo-card insumo-operacion-card">
                    <div class="insumo-operacion-extra">
                        <p>¿Necesitas registrar varios insumos en una sola operación?</p>
                        <button type="button" class="insumo-btn insumo-btn-secundario" id="btnAbrirMovimientoMultiple">
                            <i class="fa-solid fa-layer-group"></i> Registrar varios insumos
                        </button>
                    </div>

                    <form id="formMovimiento">

                        <div class="insumo-form-grid">

                            <div class="insumo-campo">
                                <label for="tipoMovimiento">Tipo de operación</label>
                                <select id="tipoMovimiento" required>
                                    <option value="consumo">Consumo manual</option>
                                    <option value="ajuste">Ajuste de inventario</option>
                                </select>
                            </div>

                            <div class="insumo-campo">
                                <label for="insumoMovimiento">Insumo</label>
                                <select id="insumoMovimiento" required>
                                    <option value="">Selecciona un insumo</option>
                                </select>
                            </div>

                            <div class="insumo-campo">
                                <label for="cantidadMovimiento">Cantidad</label>
                                <input
                                    type="number"
                                    id="cantidadMovimiento"
                                    min="0.001"
                                    step="0.001"
                                    placeholder="Cantidad"
                                    required>
                            </div>

                            <div class="insumo-campo">
                                <label for="unidadMovimiento">Unidad de cantidad</label>
                                <select id="unidadMovimiento" required>
                                    <option value="">Selecciona un insumo primero</option>
                                </select>
                                <small id="unidadMovimientoAyuda">
                                    Selecciona un insumo para consultar sus existencias.
                                </small>
                            </div>

                            <div class="insumo-campo" id="campoOperacionAjuste" hidden>
                                <label for="operacionAjuste">Tipo de ajuste</label>
                                <select id="operacionAjuste">
                                    <option value="entrada">Aumentar existencias</option>
                                    <option value="salida">Disminuir existencias</option>
                                </select>
                            </div>

                            <div class="insumo-campo insumo-campo-completo">
                                <label for="observacionMovimiento">Motivo del movimiento</label>
                                <textarea
                                    id="observacionMovimiento"
                                    rows="3"
                                    maxlength="255"
                                    placeholder="Describe el motivo del consumo o ajuste..."
                                    required></textarea>
                            </div>

                        </div>

                        <div class="insumo-ayuda-operacion">
                            <i class="fa-solid fa-circle-info"></i>
                            <span id="textoAyudaMovimiento">
                                El consumo reduce el stock y no registra un gasto.
                            </span>
                        </div>

                        <div class="insumo-form-acciones">
                            <button type="submit" class="insumo-btn insumo-btn-principal">
                                Registrar movimiento
                            </button>
                            <button type="reset" class="insumo-btn insumo-btn-secundario">
                                Limpiar
                            </button>
                        </div>

                    </form>
                </section>

                <section class="insumo-card insumo-historial-card">
                    <div class="insumo-card-header">
                        <div>
                            <h2>Historial de movimientos</h2>
                            <p>Últimos movimientos registrados en tu inventario.</p>
                        </div>

                        <button type="button" class="insumo-btn-texto" id="btnActualizarMovimientos">
                            <i class="fa-solid fa-rotate"></i> Actualizar
                        </button>
                    </div>

                    <div id="tablaMovimientos" class="insumo-tabla-contenedor">
                        <p class="insumo-vacio">Selecciona esta sección para cargar el historial.</p>
                    </div>
                </section>
            </section>

            <!-- =======================================================
                MODAL DE COMPRA MÚLTIPLE
            ======================================================== -->

            <div class="insumo-modal-overlay" id="modalCompraMultiple" hidden>
                <section class="insumo-modal" role="dialog" aria-modal="true" aria-labelledby="tituloCompraMultiple">
                    <div class="insumo-modal-header">
                        <div>
                            <h2 id="tituloCompraMultiple">Registrar compra de varios insumos</h2>
                            <p>Todos los artículos se registrarán como una sola compra y un único gasto de insumos.</p>
                        </div>
                        <button type="button" class="insumo-modal-cerrar" data-cerrar-modal="modalCompraMultiple" aria-label="Cerrar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form id="formCompraMultiple">
                        <div class="insumo-lote-lista" id="filasCompraMultiple"></div>

                        <button type="button" class="insumo-btn insumo-btn-secundario" id="btnAgregarCompraMultiple">
                            <i class="fa-solid fa-plus"></i> Agregar otro insumo
                        </button>

                        <div class="insumo-campo insumo-lote-observacion">
                            <label for="observacionCompraMultiple">Observación general (opcional)</label>
                            <textarea id="observacionCompraMultiple" rows="2" maxlength="255" placeholder="Proveedor, factura o detalles de la compra..."></textarea>
                        </div>

                        <div class="insumo-lote-total">
                            <span>Total de la compra</span>
                            <strong id="totalCompraMultiple">$0</strong>
                        </div>

                        <div class="insumo-form-acciones">
                            <button type="submit" class="insumo-btn insumo-btn-principal">Registrar compra completa</button>
                            <button type="button" class="insumo-btn insumo-btn-secundario" data-cerrar-modal="modalCompraMultiple">Cancelar</button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- =======================================================
                MODAL DE MOVIMIENTOS MÚLTIPLES
            ======================================================== -->

            <div class="insumo-modal-overlay" id="modalMovimientoMultiple" hidden>
                <section class="insumo-modal" role="dialog" aria-modal="true" aria-labelledby="tituloMovimientoMultiple">
                    <div class="insumo-modal-header">
                        <div>
                            <h2 id="tituloMovimientoMultiple">Registrar varios movimientos</h2>
                            <p>Todos los insumos se procesarán juntos. Si uno falla, no se guardará ninguno.</p>
                        </div>
                        <button type="button" class="insumo-modal-cerrar" data-cerrar-modal="modalMovimientoMultiple" aria-label="Cerrar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form id="formMovimientoMultiple">
                        <div class="insumo-form-grid insumo-lote-configuracion">
                            <div class="insumo-campo">
                                <label for="tipoMovimientoMultiple">Tipo de operación</label>
                                <select id="tipoMovimientoMultiple">
                                    <option value="consumo">Consumo manual</option>
                                    <option value="ajuste">Ajuste de inventario</option>
                                </select>
                            </div>
                            <div class="insumo-campo" id="campoOperacionAjusteMultiple" hidden>
                                <label for="operacionAjusteMultiple">Tipo de ajuste</label>
                                <select id="operacionAjusteMultiple">
                                    <option value="entrada">Aumentar existencias</option>
                                    <option value="salida">Disminuir existencias</option>
                                </select>
                            </div>
                        </div>

                        <div class="insumo-lote-lista" id="filasMovimientoMultiple"></div>

                        <button type="button" class="insumo-btn insumo-btn-secundario" id="btnAgregarMovimientoMultiple">
                            <i class="fa-solid fa-plus"></i> Agregar otro insumo
                        </button>

                        <div class="insumo-campo insumo-lote-observacion">
                            <label for="observacionMovimientoMultiple">Motivo del movimiento</label>
                            <textarea id="observacionMovimientoMultiple" rows="2" maxlength="255" required placeholder="Describe el motivo de este consumo o ajuste..."></textarea>
                        </div>

                        <div class="insumo-form-acciones">
                            <button type="submit" class="insumo-btn insumo-btn-principal">Registrar movimientos</button>
                            <button type="button" class="insumo-btn insumo-btn-secundario" data-cerrar-modal="modalMovimientoMultiple">Cancelar</button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- =======================================================
                RECETAS
            ======================================================== -->

            <section class="insumo-panel" id="panel-recetas">

                <div class="insumo-panel-intro">
                    <h2>📖 Recetas y costos estimados</h2>
                    <p>
                        Relaciona cada producto con los insumos que utiliza y define
                        las cantidades necesarias en la unidad base de cada ingrediente.
                    </p>
                </div>

                <section class="insumo-card insumo-recetas-listado-card">
                    <div class="insumo-card-header">
                        <div>
                            <h2>Recetas configuradas</h2>
                            <p>Consulta los productos que ya tienen una receta y administra su composición.</p>
                        </div>
                        <span id="contadorRecetas" class="insumo-contador">0 recetas</span>
                    </div>

                    <div id="listaRecetas" class="insumo-recetas-lista">
                        <p class="insumo-vacio">Cargando recetas...</p>
                    </div>
                </section>

                <section class="insumo-card insumo-receta-card">
                    <div class="insumo-card-header">
                        <div>
                            <h2 id="tituloFormularioReceta">Crear receta</h2>
                            <p id="descripcionFormularioReceta">Selecciona un producto que todavía no tenga receta.</p>
                        </div>
                    </div>

                    <div class="insumo-campo">
                        <label for="productoReceta">Producto</label>
                        <select id="productoReceta" required>
                            <option value="">Selecciona un producto</option>
                        </select>
                    </div>

                    <div class="insumo-receta-encabezado">
                        <div>
                            <h3>Composición de la receta</h3>
                            <p>Agrega los insumos y la cantidad utilizada para elaborar una unidad del producto.</p>
                        </div>

                        <button type="button" class="insumo-btn insumo-btn-secundario" id="btnAgregarIngrediente">
                            <i class="fa-solid fa-plus"></i> Agregar insumo
                        </button>
                    </div>

                    <div id="listaIngredientesReceta" class="insumo-receta-lista">
                        <p class="insumo-vacio">Selecciona un producto para consultar su receta.</p>
                    </div>

                    <div class="insumo-receta-resumen">
                        <div>
                            <span>Costo estimado de insumos</span>
                            <strong id="costoEstimadoReceta">$0</strong>
                        </div>
                        <p>
                            Es una estimación basada en el costo promedio actual de los insumos.
                            No representa la utilidad neta ni incluye mano de obra, servicios u otros gastos.
                        </p>
                    </div>

                    <div class="insumo-form-acciones">
                        <button type="button" class="insumo-btn insumo-btn-principal" id="btnGuardarReceta">
                            Guardar receta
                        </button>
                        <button type="button" class="insumo-btn insumo-btn-secundario" id="btnCancelarEdicionReceta" hidden>
                            Cancelar edición
                        </button>
                    </div>

                </section>
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
