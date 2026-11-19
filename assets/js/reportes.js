/**
 * ==========================================================
 * reportes.js
 * ==========================================================
 * Gestiona filtros, consultas, pestañas, gráficos y reportes
 * históricos del módulo financiero de Confypim.
 * ==========================================================
 */

"use strict";

document.addEventListener("DOMContentLoaded", () => {

    const ENDPOINT = "ajax/reporte.php";

    const elementos = {
        periodo: document.getElementById("filtroPeriodo"),
        contenedorFechas: document.getElementById("contenedorFechas"),
        fechaInicio: document.getElementById("fechaInicio"),
        fechaFin: document.getElementById("fechaFin"),
        btnAplicar: document.getElementById("btnAplicarPeriodo"),
        btnGuardar: document.getElementById("btnGuardarReporte"),
        periodoActual: document.getElementById("periodoActual")
    };

    let datosActuales = null;
    let periodoActual = null;
    let graficos = {};

    inicializar();

    async function inicializar() {
        registrarEventos();

        const hoy = obtenerFechaLocal(new Date());
        elementos.fechaInicio.value = hoy;
        elementos.fechaFin.value = hoy;

        await aplicarPeriodo();
    }

    function registrarEventos() {
        elementos.periodo.addEventListener("change", gestionarCambioPeriodo);
        elementos.btnAplicar.addEventListener("click", aplicarPeriodo);
        elementos.btnGuardar.addEventListener("click", guardarReporte);
        document.getElementById("btnActualizarHistorial")
            .addEventListener("click", cargarHistorial);

        document.querySelectorAll(".reporte-tab").forEach((boton) => {
            boton.addEventListener("click", () => cambiarPestana(boton.dataset.tab));
        });
    }

    function gestionarCambioPeriodo() {
        const personalizado = elementos.periodo.value === "personalizado";
        elementos.contenedorFechas.hidden = !personalizado;

        if (personalizado) {
            if (!elementos.fechaInicio.value || !elementos.fechaFin.value) {
                establecerRangoRapido("30dias");
            }
        }
    }

    function obtenerFechaLocal(fecha) {
        const anio = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, "0");
        const dia = String(fecha.getDate()).padStart(2, "0");
        return `${anio}-${mes}-${dia}`;
    }

    function establecerRangoRapido(tipo) {
        const hoy = new Date();
        const fin = new Date(hoy);
        const inicio = new Date(hoy);

        if (tipo === "7dias") inicio.setDate(inicio.getDate() - 6);
        if (tipo === "15dias") inicio.setDate(inicio.getDate() - 14);
        if (tipo === "30dias") inicio.setDate(inicio.getDate() - 29);

        if (tipo === "mes") inicio.setDate(1);
        if (tipo === "anio") {
            inicio.setMonth(0, 1);
        }

        elementos.fechaInicio.value = obtenerFechaLocal(inicio);
        elementos.fechaFin.value = obtenerFechaLocal(fin);
    }

    function obtenerRangoSeleccionado() {
        const tipo = elementos.periodo.value;

        if (tipo !== "personalizado") {
            establecerRangoRapido(tipo);
        }

        return {
            inicio: elementos.fechaInicio.value,
            fin: elementos.fechaFin.value
        };
    }

    async function aplicarPeriodo() {
        const rango = obtenerRangoSeleccionado();

        if (!rango.inicio || !rango.fin) {
            mostrarToast("Selecciona las fechas del período.", "warning");
            return;
        }

        if (rango.inicio > rango.fin) {
            mostrarToast("La fecha inicial no puede ser posterior a la fecha final.", "warning");
            return;
        }

        elementos.btnAplicar.disabled = true;
        elementos.btnAplicar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cargando';

        try {
            const parametros = new URLSearchParams({
                accion: "datos",
                inicio: rango.inicio,
                fin: rango.fin
            });

            const respuesta = await fetch(`${ENDPOINT}?${parametros.toString()}`, {
                method: "GET",
                headers: { "Accept": "application/json" }
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || !resultado.success) {
                throw new Error(resultado.message || "No fue posible consultar los reportes.");
            }

            datosActuales = resultado.data;
            periodoActual = rango;

            renderizarResumen(datosActuales);
            renderizarIngresos(datosActuales);
            renderizarGastos(datosActuales);
            renderizarBalance(datosActuales);
            renderizarGraficos(datosActuales);

            elementos.periodoActual.textContent =
                `Mostrando información desde ${formatearFechaCorta(rango.inicio)} hasta ${formatearFechaCorta(rango.fin)}.`;

        } catch (error) {
            console.error("Error al cargar reportes:", error);
            mostrarToast(error.message || "Ocurrió un error al cargar los reportes.", "error");
        } finally {
            elementos.btnAplicar.disabled = false;
            elementos.btnAplicar.innerHTML = '<i class="fa-solid fa-filter"></i> Aplicar';
        }
    }

    function cambiarPestana(nombre) {
        document.querySelectorAll(".reporte-tab").forEach((boton) => {
            const activa = boton.dataset.tab === nombre;
            boton.classList.toggle("active", activa);
            boton.setAttribute("aria-selected", activa ? "true" : "false");
        });

        document.querySelectorAll(".reporte-panel").forEach((panel) => {
            const activo = panel.dataset.panel === nombre;
            panel.hidden = !activo;
            panel.classList.toggle("active", activo);
        });

        if (nombre === "historial") {
            cargarHistorial();
        }

        if (datosActuales && ["resumen", "ingresos", "gastos", "balance", "productos"].includes(nombre)) {
            // Chart.js necesita recalcular el tamaño cuando el canvas estaba oculto.
            requestAnimationFrame(() => {
                Object.values(graficos).forEach((grafico) => {
                    if (grafico && typeof grafico.resize === "function") grafico.resize();
                });
            });
        }
    }

    function renderizarResumen(datos) {
        const resumen = datos.resumen;

        establecerTexto("totalIngresos", formatearMoneda(resumen.ingresos));
        establecerTexto("totalGastos", formatearMoneda(resumen.gastos));
        establecerTexto("totalBalance", formatearMoneda(resumen.balance));

        establecerTexto(
            "detalleIngresos",
            `${resumen.ventas.cantidad} ventas pagadas · ${resumen.ingresos_adicionales.cantidad} ingresos adicionales`
        );
        establecerTexto(
            "detalleGastos",
            `${resumen.cantidad_gastos} gastos registrados`
        );

        establecerTexto("totalVentasPagadas", formatearMoneda(resumen.ventas.total));
        establecerTexto("cantidadVentasPagadas", `${resumen.ventas.cantidad} ventas`);
        establecerTexto("totalIngresosAdicionales", formatearMoneda(resumen.ingresos_adicionales.total));
        establecerTexto("cantidadIngresosAdicionales", `${resumen.ingresos_adicionales.cantidad} registros`);
    }

    function renderizarIngresos(datos) {
        const filas = datos.ingresos || [];
        const cuerpo = document.getElementById("tablaIngresos");

        establecerTexto("contadorIngresos", `${filas.length} registros`);

        if (!filas.length) {
            cuerpo.innerHTML = '<tr><td colspan="5" class="reporte-tabla-vacia">No hay ingresos registrados en este período.</td></tr>';
            return;
        }

        cuerpo.innerHTML = filas.map((fila) => `
            <tr>
                <td>${escaparHTML(formatearFecha(fila.fecha))}</td>
                <td>${escaparHTML(fila.concepto)}</td>
                <td><span class="reporte-etiqueta ${fila.tipo === "Venta" ? "etiqueta-venta" : "etiqueta-adicional"}">${escaparHTML(fila.tipo === "Venta" ? "Venta pagada" : "Adicional")}</span></td>
                <td>${escaparHTML(fila.categoria)}</td>
                <td class="texto-derecha monto-positivo">${formatearMoneda(fila.monto)}</td>
            </tr>
        `).join("");
    }

    function renderizarGastos(datos) {
        const filas = datos.gastos || [];
        const cuerpo = document.getElementById("tablaGastos");

        establecerTexto("contadorGastos", `${filas.length} registros`);

        if (!filas.length) {
            cuerpo.innerHTML = '<tr><td colspan="4" class="reporte-tabla-vacia">No hay gastos registrados en este período.</td></tr>';
            return;
        }

        cuerpo.innerHTML = filas.map((fila) => `
            <tr>
                <td>${escaparHTML(formatearFecha(fila.fecha))}</td>
                <td>${escaparHTML(fila.descripcion)}</td>
                <td>${escaparHTML(fila.categoria)}</td>
                <td class="texto-derecha monto-negativo">${formatearMoneda(fila.monto)}</td>
            </tr>
        `).join("");
    }

    function renderizarBalance(datos) {
        const resumen = datos.resumen;
        establecerTexto("balanceDetalle", formatearMoneda(resumen.balance));
        establecerTexto("balanceIngresos", formatearMoneda(resumen.ingresos));
        establecerTexto("balanceGastos", formatearMoneda(resumen.gastos));
        establecerTexto("balanceResultado", formatearMoneda(resumen.balance));

        const balance = document.getElementById("balanceDetalle");
        balance.classList.toggle("balance-negativo", resumen.balance < 0);
        balance.classList.toggle("balance-positivo", resumen.balance >= 0);

        establecerTexto(
            "estadoBalance",
            resumen.balance < 0
                ? "Los gastos superan los ingresos durante este período."
                : resumen.balance > 0
                    ? "Los ingresos superan los gastos durante este período."
                    : "Los ingresos y gastos se encuentran equilibrados."
        );
    }

    function renderizarGraficos(datos) {
        if (typeof Chart === "undefined") {
            mostrarToast("No se pudo cargar Chart.js. Verifica la conexión a Internet.", "error");
            return;
        }

        Chart.defaults.font.family = "Poppins, sans-serif";
        Chart.defaults.color = "#77716F";

        const evolucion = datos.evolucion || [];
        crearGrafico("evolucion", "graficoEvolucion", {
            type: "line",
            data: {
                labels: evolucion.map((fila) => formatearFechaCorta(fila.fecha)),
                datasets: [
                    {
                        label: "Ingresos",
                        data: evolucion.map((fila) => Number(fila.ingresos)),
                        borderColor: "#7EAD92",
                        backgroundColor: "rgba(126, 173, 146, .14)",
                        fill: true,
                        tension: .32
                    },
                    {
                        label: "Gastos",
                        data: evolucion.map((fila) => Number(fila.gastos)),
                        borderColor: "#D58BA6",
                        backgroundColor: "rgba(213, 139, 166, .12)",
                        fill: true,
                        tension: .32
                    }
                ]
            },
            options: opcionesEjeMonetario()
        });

        const categoriasGasto = datos.categorias_gasto || [];
        const gastosConValor = categoriasGasto.filter((fila) => Number(fila.total) > 0);
        alternarVacio("sinGastosCategoria", gastosConValor.length === 0);
        alternarVacio("sinGastosDetalle", gastosConValor.length === 0);

        const configGastos = configuracionDona(
            gastosConValor.map((fila) => fila.categoria),
            gastosConValor.map((fila) => Number(fila.total))
        );
        crearGrafico("gastosCategoria", "graficoGastosCategoria", configGastos);
        crearGrafico("gastosDetalle", "graficoGastosDetalle", configGastos);

        const categoriasIngreso = (datos.categorias_ingreso || [])
            .filter((fila) => Number(fila.total) > 0);
        alternarVacio("sinIngresosCategoria", categoriasIngreso.length === 0);
        crearGrafico("ingresosCategoria", "graficoIngresosCategoria", configuracionDona(
            categoriasIngreso.map((fila) => fila.categoria),
            categoriasIngreso.map((fila) => Number(fila.total))
        ));

        const productosUnidades = datos.productos_unidades || [];
        const productosIngresos = datos.productos_ingresos || [];

        alternarVacio("sinProductosUnidades", productosUnidades.length === 0);
        alternarVacio("sinProductosIngresos", productosIngresos.length === 0);

        crearGrafico("productosUnidades", "graficoProductosUnidades", configuracionBarras(
            productosUnidades.map((fila) => fila.nombre),
            productosUnidades.map((fila) => Number(fila.unidades_vendidas)),
            "Unidades vendidas",
            false
        ));

        crearGrafico("productosIngresos", "graficoProductosIngresos", configuracionBarras(
            productosIngresos.map((fila) => fila.nombre),
            productosIngresos.map((fila) => Number(fila.ingresos_generados)),
            "Ingresos generados",
            true
        ));

        crearGrafico("balance", "graficoBalance", {
            type: "line",
            data: {
                labels: evolucion.map((fila) => formatearFechaCorta(fila.fecha)),
                datasets: [
                    {
                        label: "Balance diario",
                        data: evolucion.map((fila) => Number(fila.balance)),
                        borderColor: "#B8896A",
                        backgroundColor: "rgba(184, 137, 106, .12)",
                        fill: true,
                        tension: .3
                    },
                    {
                        label: "Balance acumulado",
                        data: evolucion.map((fila) => Number(fila.balance_acumulado)),
                        borderColor: "#8C789B",
                        backgroundColor: "transparent",
                        borderDash: [6, 4],
                        fill: false,
                        tension: .3
                    }
                ]
            },
            options: opcionesEjeMonetario()
        });
    }

    function opcionesEjeMonetario() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: { position: "bottom", labels: { usePointStyle: true, boxWidth: 8, padding: 18 } },
                tooltip: {
                    callbacks: {
                        label: (contexto) => `${contexto.dataset.label}: ${formatearMoneda(contexto.parsed.y)}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (valor) => formatearMonedaCompacta(valor) },
                    grid: { color: "rgba(74, 74, 74, .07)" }
                },
                x: { grid: { display: false } }
            }
        };
    }

    function configuracionDona(etiquetas, valores) {
        return {
            type: "doughnut",
            data: {
                labels: etiquetas,
                datasets: [{
                    data: valores,
                    backgroundColor: ["#EBC8D6", "#B8896A", "#A9C8B4", "#B7A8CB", "#E6C77A", "#A9C7D8", "#D99C8D"],
                    borderColor: "#FFFFFF",
                    borderWidth: 3,
                    hoverOffset: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "66%",
                plugins: {
                    legend: { position: "bottom", labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                    tooltip: {
                        callbacks: {
                            label: (contexto) => `${contexto.label}: ${formatearMoneda(contexto.parsed)}`
                        }
                    }
                }
            }
        };
    }

    function configuracionBarras(etiquetas, valores, titulo, monetario) {
        return {
            type: "bar",
            data: {
                labels: etiquetas,
                datasets: [{
                    label: titulo,
                    data: valores,
                    backgroundColor: "#EBC8D6",
                    borderColor: "#D58BA6",
                    borderWidth: 1,
                    borderRadius: 7,
                    maxBarThickness: 34
                }]
            },
            options: {
                indexAxis: "y",
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (contexto) => monetario
                                ? formatearMoneda(contexto.parsed.x)
                                : `${Number(contexto.parsed.x).toLocaleString("es-CO")} unidades`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: (valor) => monetario
                                ? formatearMonedaCompacta(valor)
                                : Number(valor).toLocaleString("es-CO")
                        },
                        grid: { color: "rgba(74, 74, 74, .07)" }
                    },
                    y: { grid: { display: false } }
                }
            }
        };
    }

    function crearGrafico(clave, canvasId, configuracion) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        if (graficos[clave]) {
            graficos[clave].destroy();
            delete graficos[clave];
        }

        const sinDatos = !configuracion.data.datasets.some((dataset) =>
            dataset.data.some((valor) => Number(valor) !== 0)
        );

        if (sinDatos) return;

        const contenedor = canvas.parentElement;
        contenedor.hidden = false;
        graficos[clave] = new Chart(canvas, configuracion);
    }

    async function guardarReporte() {
        if (!periodoActual || !datosActuales) {
            mostrarToast("Primero debes consultar un período.", "warning");
            return;
        }

        const periodo = `${formatearFechaCorta(periodoActual.inicio)} al ${formatearFechaCorta(periodoActual.fin)}`;

        if (!confirm(`¿Deseas guardar el reporte financiero del período ${periodo}?`)) {
            return;
        }

        elementos.btnGuardar.disabled = true;
        elementos.btnGuardar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando';

        try {
            const respuesta = await fetch(ENDPOINT, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    accion: "guardar",
                    inicio: periodoActual.inicio,
                    fin: periodoActual.fin
                })
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || !resultado.success) {
                throw new Error(resultado.message || "No fue posible guardar el reporte.");
            }

            mostrarToast(resultado.message || "Reporte guardado correctamente.", "success");
            await cargarHistorial();

        } catch (error) {
            console.error("Error al guardar reporte:", error);
            mostrarToast(error.message || "No fue posible guardar el reporte.", "error");
        } finally {
            elementos.btnGuardar.disabled = false;
            elementos.btnGuardar.innerHTML = '<i class="fa-solid fa-bookmark"></i> Guardar reporte';
        }
    }

    async function cargarHistorial() {
        const cuerpo = document.getElementById("tablaHistorial");
        cuerpo.innerHTML = '<tr><td colspan="5" class="reporte-tabla-vacia">Cargando historial...</td></tr>';

        try {
            const respuesta = await fetch(`${ENDPOINT}?accion=historial`, {
                headers: { "Accept": "application/json" }
            });
            const resultado = await respuesta.json();

            if (!respuesta.ok || !resultado.success) {
                throw new Error(resultado.message || "No fue posible cargar el historial.");
            }

            const filas = resultado.data || [];

            if (!filas.length) {
                cuerpo.innerHTML = '<tr><td colspan="5" class="reporte-tabla-vacia">Todavía no has guardado reportes históricos.</td></tr>';
                return;
            }

            cuerpo.innerHTML = filas.map((fila) => `
                <tr>
                    <td>${escaparHTML(formatearFecha(fila.fecha_generacion))}</td>
                    <td>${escaparHTML(formatearFechaCorta(fila.periodo_inicio))} – ${escaparHTML(formatearFechaCorta(fila.periodo_fin))}</td>
                    <td class="texto-derecha monto-positivo">${formatearMoneda(fila.total_ingresos)}</td>
                    <td class="texto-derecha monto-negativo">${formatearMoneda(fila.total_gastos)}</td>
                    <td class="texto-derecha ${Number(fila.balance) < 0 ? "monto-negativo" : "monto-positivo"}">${formatearMoneda(fila.balance)}</td>
                </tr>
            `).join("");

        } catch (error) {
            console.error("Error al cargar historial:", error);
            cuerpo.innerHTML = '<tr><td colspan="5" class="reporte-tabla-vacia">No fue posible cargar el historial.</td></tr>';
            mostrarToast(error.message || "Error al cargar el historial.", "error");
        }
    }

    function alternarVacio(id, mostrar) {
        const elemento = document.getElementById(id);
        if (elemento) elemento.hidden = !mostrar;
    }

    function establecerTexto(id, texto) {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = texto;
    }

    function formatearMoneda(valor) {
        return new Intl.NumberFormat("es-CO", {
            style: "currency",
            currency: "COP",
            maximumFractionDigits: 0
        }).format(Number(valor) || 0);
    }

    function formatearMonedaCompacta(valor) {
        return new Intl.NumberFormat("es-CO", {
            notation: "compact",
            maximumFractionDigits: 1
        }).format(Number(valor) || 0);
    }

    function formatearFecha(fecha) {
        if (!fecha) return "—";

        const texto = String(fecha).replace(" ", "T");
        const objeto = new Date(texto);

        if (Number.isNaN(objeto.getTime())) return String(fecha);

        return new Intl.DateTimeFormat("es-CO", {
            day: "2-digit",
            month: "short",
            year: "numeric"
        }).format(objeto);
    }

    function formatearFechaCorta(fecha) {
        if (!fecha) return "—";

        const partes = String(fecha).split("-");
        if (partes.length !== 3) return String(fecha);

        const objeto = new Date(
            Number(partes[0]),
            Number(partes[1]) - 1,
            Number(partes[2])
        );

        return new Intl.DateTimeFormat("es-CO", {
            day: "2-digit",
            month: "short",
            year: "2-digit"
        }).format(objeto);
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
