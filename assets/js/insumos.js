/**==========================================================
 * insumos.js
 * ==========================================================
 * Gestiona las operaciones del módulo Insumos:
 * - Consultar y administrar el inventario
 * - Registrar compras y entradas
 * - Registrar consumos y ajustes
 * - Consultar el historial de movimientos
 * - Configurar recetas y costos estimados
 * ==========================================================
 */

document.addEventListener("DOMContentLoaded", () => {

    const ENDPOINT_INSUMOS = "ajax/insumo.php";

    let insumos = [];
    let insumosInactivos = [];
    let productos = [];
    let recetaActual = [];
    let recetas = [];
    let productoRecetaEditando = null;

    inicializar();

    /**
     * ==========================================================
     * Inicialización
     * ==========================================================
     */

    async function inicializar() {
        registrarEventos();

        try {
            await Promise.all([
                cargarInsumos(),
                cargarProductos()
            ]);
        } catch (error) {
            console.error(error);
            mostrarToast(
                "No fue posible cargar toda la información de Insumos.",
                "error"
            );
        }
    }

    /**
     * ==========================================================
     * Eventos
     * ==========================================================
     */

    function registrarEventos() {
        document.querySelectorAll(".insumo-tab").forEach((boton) => {
            boton.addEventListener("click", () => {
                cambiarPestana(boton.dataset.tab);
            });
        });

        document.getElementById("formInsumo")
            .addEventListener("submit", guardarInsumo);

        document.getElementById("btnCancelarInsumo")
            .addEventListener("click", restablecerFormularioInsumo);

        document.getElementById("buscarInsumo")
            .addEventListener("input", filtrarInsumos);

        document.getElementById("btnCargarPapelera")
            .addEventListener("click", cargarPapelera);

        document.getElementById("formCompra")
            .addEventListener("submit", registrarCompra);

        document.getElementById("btnAbrirCompraMultiple")
            .addEventListener("click", abrirCompraMultiple);
        document.getElementById("btnAgregarCompraMultiple")
            .addEventListener("click", () => agregarFilaCompraMultiple());
        document.getElementById("formCompraMultiple")
            .addEventListener("submit", registrarCompraMultiple);
        document.getElementById("filasCompraMultiple")
            .addEventListener("change", gestionarCambioFilaCompraMultiple);
        document.getElementById("filasCompraMultiple")
            .addEventListener("input", actualizarTotalCompraMultiple);
        document.getElementById("filasCompraMultiple")
            .addEventListener("click", gestionarEliminarFilaMultiple);

        document.getElementById("btnAbrirMovimientoMultiple")
            .addEventListener("click", abrirMovimientoMultiple);
        document.getElementById("btnAgregarMovimientoMultiple")
            .addEventListener("click", () => agregarFilaMovimientoMultiple());
        document.getElementById("formMovimientoMultiple")
            .addEventListener("submit", registrarMovimientoMultiple);
        document.getElementById("filasMovimientoMultiple")
            .addEventListener("change", gestionarCambioFilaMovimientoMultiple);
        document.getElementById("filasMovimientoMultiple")
            .addEventListener("click", gestionarEliminarFilaMultiple);
        document.getElementById("tipoMovimientoMultiple")
            .addEventListener("change", actualizarTipoMovimientoMultiple);

        document.querySelectorAll("[data-cerrar-modal]").forEach((boton) => {
            boton.addEventListener("click", () => cerrarModal(boton.dataset.cerrarModal));
        });
        document.querySelectorAll(".insumo-modal-overlay").forEach((modal) => {
            modal.addEventListener("click", (event) => {
                if (event.target === modal) cerrarModal(modal.id);
            });
        });

        document.getElementById("insumoCompra")
            .addEventListener("change", actualizarUnidadCompra);

        document.getElementById("formMovimiento")
            .addEventListener("submit", registrarMovimiento);

        document.getElementById("tipoMovimiento")
            .addEventListener("change", actualizarTipoMovimiento);

        document.getElementById("insumoMovimiento")
            .addEventListener("change", actualizarUnidadMovimiento);

        document.getElementById("unidadMovimiento")
            .addEventListener("change", actualizarAyudaMovimiento);

        document.getElementById("btnActualizarMovimientos")
            .addEventListener("click", cargarMovimientos);

        document.getElementById("productoReceta")
            .addEventListener("change", cargarReceta);

        document.getElementById("btnAgregarIngrediente")
            .addEventListener("click", agregarFilaIngrediente);

        document.getElementById("btnGuardarReceta")
            .addEventListener("click", guardarReceta);

        document.getElementById("btnCancelarEdicionReceta")
            .addEventListener("click", cancelarEdicionReceta);

        document.getElementById("listaRecetas")
            .addEventListener("click", gestionarAccionReceta);

        document.getElementById("formCompra")
            .addEventListener("reset", () => {
                setTimeout(actualizarUnidadCompra, 0);
            });

        document.getElementById("formMovimiento")
            .addEventListener("reset", () => {
                setTimeout(actualizarTipoMovimiento, 0);
            });
    }

    /**
     * ==========================================================
     * Navegación interna
     * ==========================================================
     */

    function cambiarPestana(nombre) {
        document.querySelectorAll(".insumo-tab").forEach((boton) => {
            boton.classList.toggle(
                "active",
                boton.dataset.tab === nombre
            );
        });

        document.querySelectorAll(".insumo-panel").forEach((panel) => {
            panel.classList.toggle(
                "active",
                panel.id === `panel-${nombre}`
            );
        });

        if (nombre === "movimientos") {
            cargarMovimientos();
        }

        if (nombre === "recetas") {
            cargarProductos();
        }
    }

    /**
     * ==========================================================
     * Peticiones al backend
     * ==========================================================
     */

    async function solicitar(url, opciones = {}) {
        const respuesta = await fetch(url, {
            headers: {
                "Content-Type": "application/json"
            },
            ...opciones
        });

        const datos = await respuesta.json();

        if (!respuesta.ok) {
            throw new Error(
                datos.message || `Error HTTP ${respuesta.status}`
            );
        }

        return datos;
    }

    async function enviarPOST(datos) {
        return solicitar(ENDPOINT_INSUMOS, {
            method: "POST",
            body: JSON.stringify(datos)
        });
    }

    /**
     * ==========================================================
     * Consultas principales
     * ==========================================================
     */

    async function cargarInsumos() {
        const respuesta = await api.get(ENDPOINT_INSUMOS);

        insumos = Array.isArray(respuesta) ? respuesta : [];

        renderizarTablaInsumos(insumos);
        actualizarSelectoresInsumos();
        await cargarMetricas();
    }

    async function cargarMetricas() {
        const metricas = await api.get(
            `${ENDPOINT_INSUMOS}?accion=metricas`
        );

        document.getElementById("totalInsumos").textContent =
            metricas.total_insumos ?? 0;

        document.getElementById("insumosStockBajo").textContent =
            metricas.stock_bajo ?? 0;

        document.getElementById("insumosAgotados").textContent =
            metricas.agotados ?? 0;

        document.getElementById("valorInventario").textContent =
            formatoMoneda(metricas.valor_estimado ?? 0);
    }

    async function cargarProductos() {
        productos = await api.get(
            `${ENDPOINT_INSUMOS}?accion=productos`
        );

        await cargarRecetas();
        actualizarSelectorProductosReceta();
    }

    async function cargarRecetas() {
        const respuesta = await api.get(
            `${ENDPOINT_INSUMOS}?accion=recetas`
        );

        recetas = respuesta.data || [];
        renderizarRecetas();
    }

    function actualizarSelectorProductosReceta() {
        const selector = document.getElementById("productoReceta");
        const seleccionado = productoRecetaEditando
            ? String(productoRecetaEditando)
            : selector.value;

        const idsConReceta = new Set(
            recetas.map((receta) => String(receta.id_producto))
        );

        const disponibles = productos.filter((producto) =>
            !idsConReceta.has(String(producto.id_producto)) ||
            String(producto.id_producto) === String(productoRecetaEditando)
        );

        selector.innerHTML = `
            <option value="">Selecciona un producto</option>
            ${disponibles.map((producto) => `
                <option value="${producto.id_producto}">
                    ${escaparHTML(producto.nombre)}
                </option>
            `).join("")}
        `;

        if (seleccionado && disponibles.some(
            (producto) => String(producto.id_producto) === seleccionado
        )) {
            selector.value = seleccionado;
        }
    }

    function renderizarRecetas() {
        const contenedor = document.getElementById("listaRecetas");
        const contador = document.getElementById("contadorRecetas");

        contador.textContent =
            `${recetas.length} ${recetas.length === 1 ? "receta" : "recetas"}`;

        if (!recetas.length) {
            contenedor.innerHTML =
                '<p class="insumo-vacio">Todavía no hay productos con recetas configuradas.</p>';
            return;
        }

        contenedor.innerHTML = recetas.map((receta) => `
            <article class="insumo-receta-item">
                <div class="insumo-receta-item-info">
                    <strong>${escaparHTML(receta.producto)}</strong>
                    <span>${Number(receta.total_ingredientes)} ingredientes · ${formatoMoneda(receta.costo_estimado)}</span>
                </div>
                <div class="insumo-receta-item-acciones">
                    <button type="button" class="insumo-accion editar"
                        data-receta-accion="editar" data-id="${receta.id_producto}"
                        title="Editar receta" aria-label="Editar receta de ${escaparHTML(receta.producto)}">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button type="button" class="insumo-accion eliminar"
                        data-receta-accion="eliminar" data-id="${receta.id_producto}"
                        title="Eliminar receta" aria-label="Eliminar receta de ${escaparHTML(receta.producto)}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </article>
        `).join("");
    }

    async function gestionarAccionReceta(event) {
        const boton = event.target.closest("[data-receta-accion]");
        if (!boton) return;

        const idProducto = Number(boton.dataset.id);

        if (boton.dataset.recetaAccion === "editar") {
            productoRecetaEditando = idProducto;
            actualizarSelectorProductosReceta();
            document.getElementById("productoReceta").value = String(idProducto);
            document.getElementById("tituloFormularioReceta").textContent = "Editar receta";
            document.getElementById("descripcionFormularioReceta").textContent =
                "Modifica los insumos y las cantidades de esta receta.";
            document.getElementById("btnGuardarReceta").textContent = "Actualizar receta";
            document.getElementById("btnCancelarEdicionReceta").hidden = false;

            await cargarReceta();

            document.getElementById("productoReceta").scrollIntoView({
                behavior: "smooth",
                block: "center"
            });
            return;
        }

        const receta = recetas.find(
            (item) => Number(item.id_producto) === idProducto
        );

        if (!receta || !confirm(`¿Deseas eliminar la receta de "${receta.producto}"? El producto y sus datos no serán eliminados.`)) {
            return;
        }

        try {
            const respuesta = await solicitar(
                `${ENDPOINT_INSUMOS}?id_producto=${idProducto}`,
                { method: "DELETE" }
            );

            mostrarToast(respuesta.message, "success");

            if (productoRecetaEditando === idProducto) {
                cancelarEdicionReceta();
            }

            await cargarProductos();
        } catch (error) {
            console.error(error);
            mostrarToast(error.message || "No fue posible eliminar la receta.", "error");
        }
    }

    function cancelarEdicionReceta() {
        productoRecetaEditando = null;
        document.getElementById("productoReceta").value = "";
        document.getElementById("tituloFormularioReceta").textContent = "Crear receta";
        document.getElementById("descripcionFormularioReceta").textContent =
            "Selecciona un producto que todavía no tenga receta.";
        document.getElementById("btnGuardarReceta").textContent = "Guardar receta";
        document.getElementById("btnCancelarEdicionReceta").hidden = true;
        document.getElementById("listaIngredientesReceta").innerHTML =
            '<p class="insumo-vacio">Selecciona un producto para configurar su receta.</p>';
        recetaActual = [];
        actualizarCostoReceta();
        actualizarSelectorProductosReceta();
    }

    function renderizarTablaInsumos(lista) {
        const contenedor = document.getElementById("tablaInsumos");

        actualizarContadorInsumos(lista.length);

        if (!lista.length) {
            contenedor.innerHTML = `
                <p class="insumo-vacio">
                    No hay insumos que coincidan con la búsqueda.
                </p>
            `;
            return;
        }

        const filas = lista.map((insumo) => {
            const claseEstado = claseEstadoInsumo(insumo.estado);

            return `
                <tr>
                    <td>
                        <strong>${escaparHTML(insumo.nombre)}</strong>
                    </td>
                    <td>
                        ${formatearCantidad(insumo.cantidad)}
                        <span class="insumo-unidad">${escaparHTML(insumo.unidad_medida)}</span>
                    </td>
                    <td>${formatoMoneda(insumo.costo_unitario)}</td>
                    <td>
                        <span class="insumo-estado ${claseEstado}">
                            ${escaparHTML(insumo.estado)}
                        </span>
                    </td>
                    <td>
                        <div class="insumo-acciones">
                            <button
                                type="button"
                                class="insumo-accion editar"
                                data-accion="editar"
                                data-id="${insumo.id_insumo}"
                                title="Editar insumo"
                                aria-label="Editar ${escaparHTML(insumo.nombre)}">
                                <i class="fa-solid fa-pen"></i>
                            </button>

                            <button
                                type="button"
                                class="insumo-accion eliminar"
                                data-accion="eliminar"
                                data-id="${insumo.id_insumo}"
                                title="Enviar a papelera"
                                aria-label="Desactivar ${escaparHTML(insumo.nombre)}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join("");

        contenedor.innerHTML = `
            <table class="insumo-tabla">
                <thead>
                    <tr>
                        <th>Insumo</th>
                        <th>Existencias</th>
                        <th>Costo promedio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>${filas}</tbody>
            </table>
        `;

        contenedor.querySelectorAll("[data-accion]").forEach((boton) => {
            boton.addEventListener("click", () => {
                const id = Number(boton.dataset.id);

                if (boton.dataset.accion === "editar") {
                    editarInsumo(id);
                } else {
                    desactivarInsumo(id);
                }
            });
        });
    }

    function actualizarContadorInsumos(total) {
        document.getElementById("contadorInsumos").textContent =
            `${total} ${total === 1 ? "insumo" : "insumos"}`;
    }

    function filtrarInsumos() {
        const busqueda = document.getElementById("buscarInsumo")
            .value
            .trim()
            .toLocaleLowerCase("es");

        const filtrados = insumos.filter((insumo) =>
            insumo.nombre.toLocaleLowerCase("es").includes(busqueda)
        );

        renderizarTablaInsumos(filtrados);
    }

    /**
     * ==========================================================
     * Crear y editar insumos
     * ==========================================================
     */

    async function guardarInsumo(event) {
        event.preventDefault();

        const id = document.getElementById("idInsumo").value;

        const datos = {
            accion: "crear",
            nombre: document.getElementById("nombreInsumo").value.trim(),
            unidad_medida: document.getElementById("unidadInsumo").value
        };

        try {
            let respuesta;

            if (id) {
                respuesta = await solicitar(ENDPOINT_INSUMOS, {
                    method: "PUT",
                    body: JSON.stringify({
                        id_insumo: Number(id),
                        nombre: datos.nombre
                    })
                });
            } else {
                respuesta = await enviarPOST(datos);
            }

            if (!respuesta.success) {
                mostrarToast(respuesta.message, "error");
                return;
            }

            mostrarToast(respuesta.message, "success");
            restablecerFormularioInsumo();
            await cargarInsumos();

        } catch (error) {
            console.error(error);
            mostrarToast(
                error.message || "No fue posible guardar el insumo.",
                "error"
            );
        }
    }

    function editarInsumo(id) {
        const insumo = insumos.find(
            (item) => Number(item.id_insumo) === id
        );

        if (!insumo) {
            mostrarToast("No fue posible encontrar el insumo.", "error");
            return;
        }

        document.getElementById("idInsumo").value = insumo.id_insumo;
        document.getElementById("nombreInsumo").value = insumo.nombre;
        document.getElementById("unidadInsumo").value = insumo.unidad_medida;
        document.getElementById("unidadInsumo").disabled = true;

        document.getElementById("notaStockInicial").hidden = true;

        document.getElementById("tituloFormularioInsumo").textContent =
            "✏️ Editar insumo";

        document.getElementById("descripcionFormularioInsumo").textContent =
            "Actualiza el nombre del insumo. La unidad base se conserva.";

        document.getElementById("btnGuardarInsumo").textContent =
            "Actualizar insumo";

        document.querySelector(".insumo-form-card")
            .classList.add("editing");

        document.getElementById("nombreInsumo").focus();
    }

    function restablecerFormularioInsumo() {
        document.getElementById("formInsumo").reset();
        document.getElementById("idInsumo").value = "";
        document.getElementById("notaStockInicial").hidden = false;
        document.getElementById("unidadInsumo").disabled = false;

        document.getElementById("tituloFormularioInsumo").textContent =
            "➕ Registrar insumo";

        document.getElementById("descripcionFormularioInsumo").textContent =
            "Agrega un ingrediente o material a tu inventario.";

        document.getElementById("btnGuardarInsumo").textContent =
            "Guardar insumo";

        document.querySelector(".insumo-form-card")
            .classList.remove("editing");
    }

    async function desactivarInsumo(id) {
        if (!confirm(
            "¿Deseas enviar este insumo a la papelera? Sus movimientos e historial se conservarán."
        )) {
            return;
        }

        try {
            const respuesta = await solicitar(
                `${ENDPOINT_INSUMOS}?id=${id}`,
                { method: "DELETE" }
            );

            mostrarToast(respuesta.message, "success");
            await cargarInsumos();

        } catch (error) {
            console.error(error);
            mostrarToast(
                error.message || "No fue posible desactivar el insumo.",
                "error"
            );
        }
    }

    /**
     * ==========================================================
     * Papelera y restauración
     * ==========================================================
     */

    async function cargarPapelera() {
        const contenedor = document.getElementById("listaPapelera");
        const boton = document.getElementById("btnCargarPapelera");

        if (contenedor.dataset.abierta === "true") {
            contenedor.dataset.abierta = "false";
            contenedor.innerHTML =
                '<p class="insumo-vacio">La papelera está cerrada.</p>';
            boton.textContent = "Ver papelera";
            return;
        }

        try {
            insumosInactivos = await api.get(
                `${ENDPOINT_INSUMOS}?accion=inactivos`
            );

            contenedor.dataset.abierta = "true";
            boton.textContent = "Ocultar papelera";

            if (!insumosInactivos.length) {
                contenedor.innerHTML =
                    '<p class="insumo-vacio">No hay insumos en la papelera.</p>';
                return;
            }

            contenedor.innerHTML = insumosInactivos.map((insumo) => `
                <div class="insumo-papelera-item">
                    <div>
                        <strong>${escaparHTML(insumo.nombre)}</strong>
                        <small>
                            ${formatearCantidad(insumo.cantidad)}
                            ${escaparHTML(insumo.unidad_medida)}
                        </small>
                    </div>

                    <button
                        type="button"
                        class="insumo-btn-restaurar"
                        data-restaurar="${insumo.id_insumo}">
                        <i class="fa-solid fa-rotate-left"></i>
                        Restaurar
                    </button>
                </div>
            `).join("");

            contenedor.querySelectorAll("[data-restaurar]").forEach((botonRestaurar) => {
                botonRestaurar.addEventListener("click", () => {
                    restaurarInsumo(Number(botonRestaurar.dataset.restaurar));
                });
            });

        } catch (error) {
            console.error(error);
            mostrarToast("No fue posible cargar la papelera.", "error");
        }
    }

    async function restaurarInsumo(id) {
        try {
            const respuesta = await enviarPOST({
                accion: "restaurar",
                id_insumo: id
            });

            mostrarToast(respuesta.message, "success");
            await cargarInsumos();
            await cargarPapelera();

        } catch (error) {
            console.error(error);
            mostrarToast(
                error.message || "No fue posible restaurar el insumo.",
                "error"
            );
        }
    }

    /**
     * ==========================================================
     * Selectores de insumos
     * ==========================================================
     */

    function actualizarSelectoresInsumos() {
        const opciones = `
            <option value="">Selecciona un insumo</option>
            ${insumos.map((insumo) => `
                <option value="${insumo.id_insumo}">
                    ${escaparHTML(insumo.nombre)} (${escaparHTML(insumo.unidad_medida)})
                </option>
            `).join("")}
        `;

        document.getElementById("insumoCompra").innerHTML = opciones;
        document.getElementById("insumoMovimiento").innerHTML = opciones;
    }

    function obtenerInsumoSeleccionado(id) {
        return insumos.find(
            (insumo) => String(insumo.id_insumo) === String(id)
        );
    }

    /**
     * ==========================================================
     * Compras
     * ==========================================================
     */

    function actualizarUnidadCompra() {
        const idInsumo = document.getElementById("insumoCompra").value;
        const selector = document.getElementById("unidadCompra");
        const ayuda = document.getElementById("ayudaUnidadCompra");
        const insumo = obtenerInsumoSeleccionado(idInsumo);

        if (!insumo) {
            selector.innerHTML =
                '<option value="">Selecciona un insumo primero</option>';
            return;
        }

        let opciones = [];

        if (insumo.unidad_medida === "g") {
            opciones = [
                { valor: "g", texto: "Gramos (g)" },
                { valor: "kg", texto: "Kilogramos (kg)" }
            ];
        } else if (insumo.unidad_medida === "ml") {
            opciones = [
                { valor: "ml", texto: "Mililitros (ml)" },
                { valor: "l", texto: "Litros (L)" }
            ];
        } else {
            opciones = [
                { valor: "unidades", texto: "Unidades" }
            ];
        }

        selector.innerHTML = opciones.map((opcion) => `
            <option value="${opcion.valor}">${opcion.texto}</option>
        `).join("");

        ayuda.textContent =
            `El inventario se almacena en ${insumo.unidad_medida}. Las conversiones se realizan al registrar la compra.`;
    }

    async function registrarCompra(event) {
        event.preventDefault();

        const datos = {
            accion: "comprar",
            id_insumo: Number(document.getElementById("insumoCompra").value),
            cantidad: Number(document.getElementById("cantidadCompra").value),
            unidad_compra: document.getElementById("unidadCompra").value,
            costo_total: Number(document.getElementById("costoCompra").value),
            observacion: document.getElementById("observacionCompra").value.trim()
        };

        try {
            const respuesta = await enviarPOST(datos);

            mostrarToast(respuesta.message, "success");
            document.getElementById("formCompra").reset();

            await cargarInsumos();
            actualizarUnidadCompra();

        } catch (error) {
            console.error(error);
            mostrarToast(
                error.message || "No fue posible registrar la compra.",
                "error"
            );
        }
    }

    /**
     * ==========================================================
     * Consumos y ajustes
     * ==========================================================
     */

    function actualizarTipoMovimiento() {
        const tipo = document.getElementById("tipoMovimiento").value;
        const campoAjuste = document.getElementById("campoOperacionAjuste");
        const textoAyuda = document.getElementById("textoAyudaMovimiento");

        campoAjuste.hidden = tipo !== "ajuste";

        textoAyuda.textContent = tipo === "consumo"
            ? "El consumo reduce el stock y no registra un gasto."
            : "El ajuste corrige las existencias y no genera un ingreso ni un gasto.";

        actualizarUnidadMovimiento();
    }

    function actualizarUnidadMovimiento() {
        const id = document.getElementById("insumoMovimiento").value;
        const selector = document.getElementById("unidadMovimiento");
        const insumo = obtenerInsumoSeleccionado(id);

        if (!insumo) {
            selector.innerHTML = '<option value="">Selecciona un insumo primero</option>';
            document.getElementById("unidadMovimientoAyuda").textContent =
                "Selecciona un insumo para consultar sus existencias.";
            return;
        }

        let opciones = [];

        if (insumo.unidad_medida === "g") {
            opciones = [{ valor: "g", texto: "Gramos (g)" }, { valor: "kg", texto: "Kilogramos (kg)" }];
        } else if (insumo.unidad_medida === "ml") {
            opciones = [{ valor: "ml", texto: "Mililitros (ml)" }, { valor: "l", texto: "Litros (L)" }];
        } else {
            opciones = [{ valor: "unidades", texto: "Unidades" }];
        }

        selector.innerHTML = opciones.map((opcion) =>
            `<option value="${opcion.valor}">${opcion.texto}</option>`
        ).join("");

        actualizarAyudaMovimiento();
    }

    function actualizarAyudaMovimiento() {
        const id = document.getElementById("insumoMovimiento").value;
        const insumo = obtenerInsumoSeleccionado(id);
        const unidad = document.getElementById("unidadMovimiento").value;
        const ayuda = document.getElementById("unidadMovimientoAyuda");

        if (!insumo) {
            ayuda.textContent = "Selecciona un insumo para consultar sus existencias.";
            return;
        }

        const factor = (unidad === "kg" || unidad === "l") ? 1000 : 1;
        const stockVisible = Number(insumo.cantidad) / factor;
        const nombreUnidad = unidad || insumo.unidad_medida;

        ayuda.textContent =
            `Existencias actuales: ${formatearCantidad(stockVisible)} ${nombreUnidad}. ` +
            `La cantidad se convertirá automáticamente a ${insumo.unidad_medida}.`;
    }

    async function registrarMovimiento(event) {
        event.preventDefault();

        const tipo = document.getElementById("tipoMovimiento").value;

        const idInsumo = Number(document.getElementById("insumoMovimiento").value);
        const insumo = obtenerInsumoSeleccionado(idInsumo);
        const cantidadIngresada = Number(document.getElementById("cantidadMovimiento").value);
        const unidad = document.getElementById("unidadMovimiento").value;
        const factor = (unidad === "kg" || unidad === "l") ? 1000 : 1;

        if (!insumo || !Number.isFinite(cantidadIngresada) || cantidadIngresada <= 0 || !unidad) {
            mostrarToast("Selecciona un insumo, una unidad y una cantidad válida.", "error");
            return;
        }

        const tipoAjuste = document.getElementById("operacionAjuste").value;
        const esSalida = tipo === "consumo" || (tipo === "ajuste" && tipoAjuste === "salida");
        const cantidadBase = cantidadIngresada * factor;

        if (esSalida && cantidadBase > Number(insumo.cantidad)) {
            mostrarToast(
                `No puedes retirar más de ${formatearCantidad(Number(insumo.cantidad) / factor)} ${unidad} disponibles.`,
                "error"
            );
            return;
        }

        const datos = {
            accion: tipo === "consumo" ? "consumir" : "ajustar",
            id_insumo: idInsumo,
            cantidad: cantidadBase,
            observacion: document.getElementById("observacionMovimiento").value.trim()
        };

        if (tipo === "ajuste") {
            datos.operacion = tipoAjuste;
        }

        try {
            const respuesta = await enviarPOST(datos);

            mostrarToast(respuesta.message, "success");
            document.getElementById("formMovimiento").reset();

            await cargarInsumos();
            actualizarUnidadMovimiento();
            await cargarMovimientos();

        } catch (error) {
            console.error(error);
            mostrarToast(
                error.message || "No fue posible registrar el movimiento.",
                "error"
            );
        }
    }

    /**
     * ==========================================================
     * Historial de movimientos
     * ==========================================================
     */

    async function cargarMovimientos() {
        const contenedor = document.getElementById("tablaMovimientos");

        contenedor.innerHTML =
            '<p class="insumo-vacio">Cargando movimientos...</p>';

        try {
            const movimientos = await api.get(
                `${ENDPOINT_INSUMOS}?accion=movimientos`
            );

            renderizarMovimientos(movimientos);

        } catch (error) {
            console.error(error);
            contenedor.innerHTML =
                '<p class="insumo-vacio insumo-error-texto">No fue posible cargar los movimientos.</p>';
        }
    }

    function renderizarMovimientos(movimientos) {
        const contenedor = document.getElementById("tablaMovimientos");

        if (!movimientos.length) {
            contenedor.innerHTML =
                '<p class="insumo-vacio">Todavía no hay movimientos registrados.</p>';
            return;
        }

        contenedor.innerHTML = `
            <table class="insumo-tabla insumo-tabla-movimientos">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Insumo</th>
                        <th>Tipo</th>
                        <th>Operación</th>
                        <th>Cantidad</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    ${movimientos.map((movimiento) => `
                        <tr>
                            <td>${formatearFecha(movimiento.fecha)}</td>
                            <td>${escaparHTML(movimiento.insumo)}</td>
                            <td>
                                <span class="insumo-tipo-movimiento ${movimiento.tipo_movimiento}">
                                    ${etiquetaMovimiento(movimiento.tipo_movimiento)}
                                </span>
                            </td>
                            <td>${movimiento.operacion === "entrada" ? "Entrada" : "Salida"}</td>
                            <td>
                                ${movimiento.operacion === "entrada" ? "+" : "−"}
                                ${formatearCantidad(movimiento.cantidad)}
                                ${escaparHTML(movimiento.unidad_medida)}
                            </td>
                            <td>${escaparHTML(movimiento.observacion || "—")}</td>
                        </tr>
                    `).join("")}
                </tbody>
            </table>
        `;
    }

    /**
     * ==========================================================
     * OPERACIONES MÚLTIPLES
     * ==========================================================
     */

    function opcionesInsumos() {
        return `
            <option value="">Selecciona un insumo</option>
            ${insumos.map((insumo) => `
                <option value="${insumo.id_insumo}">${escaparHTML(insumo.nombre)}</option>
            `).join("")}
        `;
    }

    function opcionesUnidades(unidadBase) {
        if (unidadBase === "g") {
            return '<option value="g">Gramos (g)</option><option value="kg">Kilogramos (kg)</option>';
        }
        if (unidadBase === "ml") {
            return '<option value="ml">Mililitros (ml)</option><option value="l">Litros (l)</option>';
        }
        return '<option value="unidades">Unidades</option>';
    }

    function abrirModal(id) {
        document.getElementById(id).hidden = false;
        document.body.classList.add("insumo-modal-abierto");
    }

    function cerrarModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.hidden = true;
        if (!document.querySelector(".insumo-modal-overlay:not([hidden])")) {
            document.body.classList.remove("insumo-modal-abierto");
        }
    }

    function abrirCompraMultiple() {
        const contenedor = document.getElementById("filasCompraMultiple");
        contenedor.innerHTML = "";
        agregarFilaCompraMultiple();
        agregarFilaCompraMultiple();
        document.getElementById("formCompraMultiple").reset();
        actualizarTotalCompraMultiple();
        abrirModal("modalCompraMultiple");
    }

    function agregarFilaCompraMultiple() {
        const fila = document.createElement("div");
        fila.className = "insumo-lote-fila";
        fila.innerHTML = `
            <div class="insumo-campo">
                <label>Insumo</label>
                <select class="lote-insumo" required>${opcionesInsumos()}</select>
            </div>
            <div class="insumo-campo">
                <label>Cantidad</label>
                <input class="lote-cantidad" type="number" min="0.001" step="0.001" placeholder="Cantidad" required>
            </div>
            <div class="insumo-campo">
                <label>Unidad</label>
                <select class="lote-unidad" required disabled><option value="">Selecciona insumo</option></select>
            </div>
            <div class="insumo-campo">
                <label>Costo total</label>
                <input class="lote-costo" type="number" min="0.01" step="0.01" placeholder="$" required>
            </div>
            <button type="button" class="insumo-lote-quitar" title="Quitar insumo" aria-label="Quitar insumo">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
        document.getElementById("filasCompraMultiple").appendChild(fila);
    }

    function gestionarCambioFilaCompraMultiple(event) {
        if (!event.target.classList.contains("lote-insumo")) return;
        const fila = event.target.closest(".insumo-lote-fila");
        const insumo = obtenerInsumoSeleccionado(Number(event.target.value));
        const selector = fila.querySelector(".lote-unidad");

        if (!insumo) {
            selector.innerHTML = '<option value="">Selecciona insumo</option>';
            selector.disabled = true;
            return;
        }

        selector.innerHTML = opcionesUnidades(insumo.unidad_medida);
        selector.disabled = false;
    }

    function actualizarTotalCompraMultiple() {
        const total = Array.from(document.querySelectorAll("#filasCompraMultiple .lote-costo"))
            .reduce((suma, campo) => suma + (Number(campo.value) || 0), 0);
        document.getElementById("totalCompraMultiple").textContent = formatoMoneda(total);
    }

    function abrirMovimientoMultiple() {
        const contenedor = document.getElementById("filasMovimientoMultiple");
        contenedor.innerHTML = "";
        agregarFilaMovimientoMultiple();
        agregarFilaMovimientoMultiple();
        document.getElementById("formMovimientoMultiple").reset();
        actualizarTipoMovimientoMultiple();
        abrirModal("modalMovimientoMultiple");
    }

    function agregarFilaMovimientoMultiple() {
        const fila = document.createElement("div");
        fila.className = "insumo-lote-fila";
        fila.innerHTML = `
            <div class="insumo-campo">
                <label>Insumo</label>
                <select class="lote-insumo" required>${opcionesInsumos()}</select>
                <small class="lote-stock">Existencias: selecciona un insumo.</small>
            </div>
            <div class="insumo-campo">
                <label>Cantidad</label>
                <input class="lote-cantidad" type="number" min="0.001" step="0.001" placeholder="Cantidad" required>
            </div>
            <div class="insumo-campo">
                <label>Unidad</label>
                <select class="lote-unidad" required disabled><option value="">Selecciona insumo</option></select>
            </div>
            <button type="button" class="insumo-lote-quitar" title="Quitar insumo" aria-label="Quitar insumo">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
        document.getElementById("filasMovimientoMultiple").appendChild(fila);
    }

    function gestionarCambioFilaMovimientoMultiple(event) {
        const fila = event.target.closest(".insumo-lote-fila");
        if (!fila) return;

        if (event.target.classList.contains("lote-insumo")) {
            const insumo = obtenerInsumoSeleccionado(Number(event.target.value));
            const selector = fila.querySelector(".lote-unidad");
            if (!insumo) {
                selector.innerHTML = '<option value="">Selecciona insumo</option>';
                selector.disabled = true;
                fila.querySelector(".lote-stock").textContent = "Existencias: selecciona un insumo.";
                return;
            }
            selector.innerHTML = opcionesUnidades(insumo.unidad_medida);
            selector.disabled = false;
            actualizarAyudaStockFila(fila, insumo);
        } else if (event.target.classList.contains("lote-unidad")) {
            const insumo = obtenerInsumoSeleccionado(Number(fila.querySelector(".lote-insumo").value));
            if (insumo) actualizarAyudaStockFila(fila, insumo);
        }
    }

    function actualizarAyudaStockFila(fila, insumo) {
        const unidad = fila.querySelector(".lote-unidad").value || insumo.unidad_medida;
        const factor = (unidad === "kg" || unidad === "l") ? 1000 : 1;
        fila.querySelector(".lote-stock").textContent =
            `Existencias actuales: ${formatearCantidad(Number(insumo.cantidad) / factor)} ${unidad}`;
    }

    function gestionarEliminarFilaMultiple(event) {
        const boton = event.target.closest(".insumo-lote-quitar");
        if (!boton) return;
        const contenedor = boton.closest(".insumo-lote-lista");
        if (contenedor.querySelectorAll(".insumo-lote-fila").length <= 2) {
            mostrarToast("Debes conservar al menos dos insumos en la operación múltiple.", "info");
            return;
        }
        boton.closest(".insumo-lote-fila").remove();
        actualizarTotalCompraMultiple();
    }

    function actualizarTipoMovimientoMultiple() {
        const tipo = document.getElementById("tipoMovimientoMultiple").value;
        document.getElementById("campoOperacionAjusteMultiple").hidden = tipo !== "ajuste";
    }

    async function registrarCompraMultiple(event) {
        event.preventDefault();
        const filas = Array.from(document.querySelectorAll("#filasCompraMultiple .insumo-lote-fila"));
        const items = filas.map((fila) => ({
            id_insumo: Number(fila.querySelector(".lote-insumo").value),
            cantidad: Number(fila.querySelector(".lote-cantidad").value),
            unidad_compra: fila.querySelector(".lote-unidad").value,
            costo_total: Number(fila.querySelector(".lote-costo").value)
        }));

        if (items.length < 2 || items.some((item) =>
            !item.id_insumo || !item.unidad_compra || item.cantidad <= 0 || item.costo_total <= 0
        )) {
            mostrarToast("Completa correctamente todos los insumos, cantidades y costos.", "error");
            return;
        }
        if (new Set(items.map((item) => item.id_insumo)).size !== items.length) {
            mostrarToast("No repitas un mismo insumo. Cada uno debe aparecer una sola vez.", "error");
            return;
        }

        try {
            const respuesta = await enviarPOST({
                accion: "comprar_multiple",
                items,
                observacion: document.getElementById("observacionCompraMultiple").value.trim()
            });
            mostrarToast(respuesta.message, "success");
            cerrarModal("modalCompraMultiple");
            await cargarInsumos();
            await cargarMovimientos();
        } catch (error) {
            console.error(error);
            mostrarToast(error.message || "No fue posible registrar la compra múltiple.", "error");
        }
    }

    async function registrarMovimientoMultiple(event) {
        event.preventDefault();
        const tipo = document.getElementById("tipoMovimientoMultiple").value;
        const operacion = tipo === "consumo"
            ? "salida"
            : document.getElementById("operacionAjusteMultiple").value;
        const filas = Array.from(document.querySelectorAll("#filasMovimientoMultiple .insumo-lote-fila"));
        const items = filas.map((fila) => ({
            id_insumo: Number(fila.querySelector(".lote-insumo").value),
            cantidad: Number(fila.querySelector(".lote-cantidad").value),
            unidad: fila.querySelector(".lote-unidad").value
        }));

        if (items.length < 2 || items.some((item) =>
            !item.id_insumo || !item.unidad || item.cantidad <= 0
        )) {
            mostrarToast("Completa correctamente todos los insumos y cantidades.", "error");
            return;
        }
        if (new Set(items.map((item) => item.id_insumo)).size !== items.length) {
            mostrarToast("No repitas un mismo insumo. Cada uno debe aparecer una sola vez.", "error");
            return;
        }

        for (const item of items) {
            const insumo = obtenerInsumoSeleccionado(item.id_insumo);
            const factor = (item.unidad === "kg" || item.unidad === "l") ? 1000 : 1;
            const cantidadBase = item.cantidad * factor;
            if (operacion === "salida" && cantidadBase > Number(insumo.cantidad)) {
                mostrarToast(
                    `No puedes retirar ${item.cantidad} ${item.unidad} de ${insumo.nombre}; solo hay ${formatearCantidad(Number(insumo.cantidad) / factor)} disponibles.`,
                    "error"
                );
                return;
            }
            if (insumo.unidad_medida === "unidades" && !Number.isInteger(item.cantidad)) {
                mostrarToast(`La cantidad de ${insumo.nombre} debe ser un número entero.`, "error");
                return;
            }
        }

        try {
            const respuesta = await enviarPOST({
                accion: "movimientos_multiples",
                tipo,
                operacion,
                items,
                observacion: document.getElementById("observacionMovimientoMultiple").value.trim()
            });
            mostrarToast(respuesta.message, "success");
            cerrarModal("modalMovimientoMultiple");
            await cargarInsumos();
            await cargarMovimientos();
        } catch (error) {
            console.error(error);
            mostrarToast(error.message || "No fue posible registrar los movimientos.", "error");
        }
    }

    /**
     * ==========================================================
     * Recetas
     * ==========================================================
     */

    async function cargarReceta() {
        const idProducto = document.getElementById("productoReceta").value;
        const contenedor = document.getElementById("listaIngredientesReceta");

        recetaActual = [];

        if (!idProducto) {
            contenedor.innerHTML =
                '<p class="insumo-vacio">Selecciona un producto para consultar su receta.</p>';
            actualizarCostoReceta();
            return;
        }

        try {
            const respuesta = await api.get(
                `${ENDPOINT_INSUMOS}?accion=receta&id_producto=${idProducto}`
            );

            recetaActual = respuesta.data || [];

            if (!recetaActual.length) {
                contenedor.innerHTML =
                    '<p class="insumo-vacio">Este producto todavía no tiene una receta configurada.</p>';
                actualizarCostoReceta();
                return;
            }

            contenedor.innerHTML = "";
            recetaActual.forEach((ingrediente) => {
                agregarFilaIngrediente(ingrediente);
            });

            actualizarCostoReceta();

        } catch (error) {
            console.error(error);
            contenedor.innerHTML =
                '<p class="insumo-vacio insumo-error-texto">No fue posible cargar la receta.</p>';
        }
    }

    function agregarFilaIngrediente(ingrediente = null) {
        if (!insumos.length) {
            mostrarToast("Primero debes registrar al menos un insumo.", "error");
            return;
        }

        const contenedor = document.getElementById("listaIngredientesReceta");

        const vacio = contenedor.querySelector(".insumo-vacio");
        if (vacio) {
            contenedor.innerHTML = "";
        }

        const fila = document.createElement("div");
        fila.className = "insumo-receta-fila";

        const opciones = insumos.map((insumo) => `
            <option
                value="${insumo.id_insumo}"
                ${ingrediente && Number(ingrediente.id_insumo) === Number(insumo.id_insumo) ? "selected" : ""}>
                ${escaparHTML(insumo.nombre)} (${escaparHTML(insumo.unidad_medida)})
            </option>
        `).join("");

        fila.innerHTML = `
            <div class="insumo-campo">
                <label>Insumo</label>
                <select class="receta-insumo" required>
                    <option value="">Selecciona un insumo</option>
                    ${opciones}
                </select>
            </div>

            <div class="insumo-campo">
                <label>Cantidad necesaria</label>
                <input
                    type="number"
                    class="receta-cantidad"
                    min="0.001"
                    step="0.001"
                    value="${ingrediente ? Number(ingrediente.cantidad_necesaria) : ""}"
                    placeholder="Cantidad en unidad base"
                    required>
                <small class="receta-unidad-ayuda">Unidad base del insumo seleccionado.</small>
            </div>

            <div class="insumo-receta-costo">
                <span>Costo estimado</span>
                <strong>$0</strong>
            </div>

            <button
                type="button"
                class="insumo-receta-eliminar"
                title="Quitar ingrediente"
                aria-label="Quitar ingrediente">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;

        contenedor.appendChild(fila);

        const selector = fila.querySelector(".receta-insumo");
        const cantidad = fila.querySelector(".receta-cantidad");

        selector.addEventListener("change", () => {
            actualizarUnidadFilaReceta(fila);
            actualizarCostoReceta();
        });

        cantidad.addEventListener("input", actualizarCostoReceta);

        fila.querySelector(".insumo-receta-eliminar")
            .addEventListener("click", () => {
                fila.remove();

                if (!contenedor.querySelector(".insumo-receta-fila")) {
                    contenedor.innerHTML =
                        '<p class="insumo-vacio">Agrega los insumos que componen este producto.</p>';
                }

                actualizarCostoReceta();
            });

        actualizarUnidadFilaReceta(fila);
        actualizarCostoReceta();
    }

    function actualizarUnidadFilaReceta(fila) {
        const id = fila.querySelector(".receta-insumo").value;
        const insumo = obtenerInsumoSeleccionado(id);

        fila.querySelector(".receta-unidad-ayuda").textContent = insumo
            ? `Cantidad en ${insumo.unidad_medida}.`
            : "Unidad base del insumo seleccionado.";
    }

    function actualizarCostoReceta() {
        let total = 0;

        document.querySelectorAll(".insumo-receta-fila").forEach((fila) => {
            const id = fila.querySelector(".receta-insumo").value;
            const cantidad = Number(fila.querySelector(".receta-cantidad").value);
            const insumo = obtenerInsumoSeleccionado(id);

            const costo = insumo && cantidad > 0
                ? cantidad * Number(insumo.costo_unitario)
                : 0;

            total += costo;

            fila.querySelector(".insumo-receta-costo strong").textContent =
                formatoMoneda(costo);
        });

        document.getElementById("costoEstimadoReceta").textContent =
            formatoMoneda(total);
    }

    async function guardarReceta() {
        const idProducto = Number(
            document.getElementById("productoReceta").value
        );

        if (!idProducto) {
            mostrarToast("Selecciona un producto para guardar su receta.", "error");
            return;
        }

        const filas = Array.from(
            document.querySelectorAll(".insumo-receta-fila")
        );

        const ingredientes = filas.map((fila) => ({
            id_insumo: Number(fila.querySelector(".receta-insumo").value),
            cantidad_necesaria: Number(fila.querySelector(".receta-cantidad").value)
        }));

        if (!ingredientes.length) {
            mostrarToast("Agrega al menos un insumo a la receta.", "error");
            return;
        }

        if (ingredientes.some((item) =>
            !item.id_insumo || !Number.isFinite(item.cantidad_necesaria) ||
            item.cantidad_necesaria <= 0
        )) {
            mostrarToast("Completa el insumo y la cantidad de cada fila.", "error");
            return;
        }

        try {
            const respuesta = await enviarPOST({
                accion: "guardar_receta",
                id_producto: idProducto,
                ingredientes,
                editar: productoRecetaEditando !== null
            });

            mostrarToast(respuesta.message, "success");
            productoRecetaEditando = null;
            document.getElementById("productoReceta").value = "";
            document.getElementById("tituloFormularioReceta").textContent = "Crear receta";
            document.getElementById("descripcionFormularioReceta").textContent =
                "Selecciona un producto que todavía no tenga receta.";
            document.getElementById("btnGuardarReceta").textContent = "Guardar receta";
            document.getElementById("btnCancelarEdicionReceta").hidden = true;
            document.getElementById("listaIngredientesReceta").innerHTML =
                '<p class="insumo-vacio">Selecciona un producto para configurar su receta.</p>';
            recetaActual = [];
            actualizarCostoReceta();
            await cargarProductos();

        } catch (error) {
            console.error(error);
            mostrarToast(
                error.message || "No fue posible guardar la receta.",
                "error"
            );
        }
    }

    /**
     * ==========================================================
     * Utilidades
     * ==========================================================
     */

    function formatoMoneda(valor) {
        return Number(valor || 0).toLocaleString("es-CO", {
            style: "currency",
            currency: "COP",
            maximumFractionDigits: 0
        });
    }

    function formatearCantidad(valor) {
        return Number(valor || 0).toLocaleString("es-CO", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
    }

    function formatearFecha(fecha) {
        if (!fecha) return "—";

        const valor = new Date(String(fecha).replace(" ", "T"));

        if (Number.isNaN(valor.getTime())) return fecha;

        return valor.toLocaleString("es-CO", {
            dateStyle: "short",
            timeStyle: "short"
        });
    }

    function claseEstadoInsumo(estado) {
        if (estado === "Agotado") return "agotado";
        if (estado === "Stock bajo") return "bajo";
        return "disponible";
    }

    function etiquetaMovimiento(tipo) {
        const etiquetas = {
            entrada: "Compra",
            consumo: "Consumo",
            ajuste: "Ajuste"
        };

        return etiquetas[tipo] || tipo;
    }

    function escaparHTML(valor) {
        return String(valor ?? "").replace(/[&<>"']/g, (caracter) => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        })[caracter]);
    }

});
