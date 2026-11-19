/**
 * ==========================================================
 * finanzas.js
 * ==========================================================
 * Gestiona las consultas, filtros, pestañas y gráficos de los
 * módulos de Ingresos, Gastos y Reportes de Confypim.
 * ==========================================================
 */

(() => {
    'use strict';

    const app = document.getElementById('finanzasApp');
    if (!app)
        return;

    const tipo = app.dataset.tipo;

    const api = app.dataset.api;

    const esReportes = tipo === 'reportes';

    const dinero = new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', maximumFractionDigits: 0
    });

    const numero = new Intl.NumberFormat('es-CO');

    let evolucionChart = null;

    let categoriasChart = null;

    let balanceChart = null;

    let datosActuales = null;

    const $ = (id) => document.getElementById(id);

    const escapar = (valor) => String(valor ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[c]);

    function toast(mensaje, error = false) {
        const nodo = $('finanzasToast');
        if (!nodo)
            return;
        nodo.textContent = mensaje;
        nodo.classList.toggle('error', error);
        nodo.classList.add('visible');
        window.setTimeout(() => nodo.classList.remove('visible'), 3500);
    }

    function fechaLocal(fecha) {
        const anio = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return `${anio}-${mes}-${dia}`;
    }

    function establecerRango(desde, hasta) {
        $('fechaDesde').value = fechaLocal(desde);
        $('fechaHasta').value = fechaLocal(hasta);
    }

    function aplicarPeriodo(valor, consultar = true) {
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const inicio = new Date(hoy);
        const personalizado = valor === 'personalizado';
        $('rangoPersonalizado').hidden = !personalizado;
        $('aplicarFechas').hidden = !personalizado;
        if (valor === 'hoy') {
            establecerRango(hoy, hoy);
        }
        else if (valor === 'ultimos7') {
            inicio.setDate(inicio.getDate() - 6);
            establecerRango(inicio, hoy);
        }
        else if (valor === 'ultimos15') {
            inicio.setDate(inicio.getDate() - 14);
            establecerRango(inicio, hoy);
        }
        else if (valor === 'mes_actual') {
            inicio.setDate(1);
            establecerRango(inicio, hoy);
        }
        else if (valor === 'anio_actual') {
            inicio.setMonth(0, 1);
            establecerRango(inicio, hoy);
        }
        if (consultar && !personalizado)
            cargar();
    }

    function parametros() {
        const p = new URLSearchParams({ tipo });
        if ($('fechaDesde')?.value)
            p.set('desde', $('fechaDesde').value);
        if ($('fechaHasta')?.value)
            p.set('hasta', $('fechaHasta').value);
        if ($('filtroCategoria')?.value)
            p.set('categoria', $('filtroCategoria').value);
        if ($('buscarMovimiento')?.value)
            p.set('busqueda', $('buscarMovimiento').value.trim());
        return p;
    }

    async function solicitar(url, opciones = {}) {
        const respuesta = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', ...(opciones.headers || {}) },
            ...opciones
        });
        const contenido = await respuesta.text();
        let json;
        try {
            json = JSON.parse(contenido);
        }
        catch (error) {
            console.error('Respuesta no JSON del módulo financiero:', contenido);
            throw new Error('El servidor no devolvió una respuesta válida. Revisa el registro de errores de PHP.');
        }
        if (!respuesta.ok || !json.success)
            throw new Error(json.message || 'No fue posible completar la solicitud.');
        return json.data;
    }

    function crearGrafico(canvasId, anterior, config) {
        if (anterior)
            anterior.destroy();
        const canvas = $(canvasId);
        if (!canvas || typeof Chart === 'undefined')
            return null;
        return new Chart(canvas, config);
    }

    function kpi(titulo, valor, icono, detalle = '') {
        return `<article class="finanzas-kpi"><span class="finanzas-kpi-icon">${icono}</span>
            <div><p>${titulo}</p><strong>${valor}</strong>${detalle ? `<small>${detalle}</small>` : ''}</div></article>`;
    }

    function nombreCategoria(nombre) {
        // La categoría contable "Ingredientes" agrupa las compras
        // realizadas desde Insumos; en los movimientos se identifica como Insumos.
        return tipo === 'gastos' && nombre === 'Ingredientes' ? 'Insumos' : nombre;
    }

    function badgeCategoria(nombre, id = 0) {
        return `<span class="finanzas-badge categoria-${Math.max(0, Number(id) || 0)}">${escapar(nombreCategoria(nombre))}</span>`;
    }

    function renderCategoriasSelect(categorias) {
        const select = $('filtroCategoria');
        if (!select)
            return;
        const seleccionado = select.value;
        select.innerHTML = '<option value="">Todas las categorías</option>' +
            categorias.map(c => `<option value="${c.id}">${escapar(c.nombre)}</option>`).join('');
        select.value = seleccionado;
    }

    function renderResumenFinanciero(data) {
        const ingreso = tipo === 'ingresos';
        $('finanzasKpis').innerHTML = [
            kpi(ingreso ? 'Ingresos del periodo' : 'Gastos del periodo', dinero.format(data.resumen.total), ingreso ? '↗' : '↘'),
            kpi('Movimientos registrados', numero.format(data.resumen.cantidad), '▤'),
            kpi('Valor promedio', dinero.format(data.resumen.promedio), '◷')
        ].join('');
        $('evolucionTitulo').textContent = ingreso ? 'Evolución de ingresos' : 'Evolución de gastos';
        evolucionChart = crearGrafico('evolucionChart', evolucionChart, {
            type: 'line',
            data: {
                labels: data.resumen.evolucion.map(x => x.periodo),
                datasets: [{
                        label: ingreso ? 'Ingresos' : 'Gastos',
                        data: data.resumen.evolucion.map(x => Number(x.total)),
                        borderColor: ingreso ? '#D58BA6' : '#B8896A',
                        backgroundColor: ingreso ? 'rgba(213,139,166,.16)' : 'rgba(184,137,106,.16)',
                        fill: true, tension: .35, pointRadius: 3
                    }]
            },
            options: opcionesGrafico()
        });
        categoriasChart = crearGrafico('categoriasChart', categoriasChart, {
            type: 'doughnut',
            data: {
                labels: data.resumen.categorias.map(x => nombreCategoria(x.nombre)),
                datasets: [{
                        data: data.resumen.categorias.map(x => Number(x.total)),
                        backgroundColor: ['#D58BA6', '#B8896A', '#A8B8A0', '#91A8C2', '#D8B86A', '#B7A2C8'],
                        borderWidth: 2, borderColor: '#FFFFFF'
                    }]
            },
            options: { ...opcionesGrafico(), cutout: '62%', plugins: { ...opcionesGrafico().plugins, legend: { position: 'bottom' } } }
        });
        const total = data.resumen.total || 0;
        $('categoriasKpis').innerHTML = kpi('Total clasificado', dinero.format(total), '◈');
        $('categoriasLista').innerHTML = data.categorias.map(c => {
            const item = data.resumen.categorias.find(x => Number(x.id_categoria) === Number(c.id));
            const valor = item ? Number(item.total) : 0;
            const porcentaje = total > 0 ? (valor / total * 100) : 0;
            return `<div class="finanzas-categoria-row">${badgeCategoria(c.nombre, c.id)}
                <div class="finanzas-categoria-bar"><span style="width:${porcentaje.toFixed(2)}%"></span></div>
                <strong>${dinero.format(valor)}</strong><small>${porcentaje.toFixed(1)}%</small></div>`;
        }).join('');
    }

    function opcionesGrafico() {
        return {
            responsive: true, maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `${ctx.dataset.label || 'Total'}: ${dinero.format(ctx.parsed.y ?? ctx.parsed)}` } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: value => dinero.format(value) }, grid: { color: '#F0EBE8' } },
                x: { grid: { display: false } }
            }
        };
    }

    function renderMovimientos(data) {
        const body = $('movimientosBody');
        if (!body)
            return;
        if (!data.movimientos.length) {
            body.innerHTML = '<tr><td colspan="5" class="finanzas-empty">No hay movimientos para los filtros seleccionados.</td></tr>';
            return;
        }
        const esIngreso = tipo === 'ingresos';
        body.innerHTML = data.movimientos.map(m => {
            const concepto = esIngreso
                ? (m.origen === 'Manual' ? 'Ingreso manual' : m.origen)
                : (m.descripcion || 'Gasto registrado');
            const idCat = (data.categorias.find(c => c.nombre === m.categoria) || {}).id || 0;
            return `<tr><td>${escapar(m.fecha)}</td><td><strong>${escapar(concepto)}</strong>
                ${m.id ? `<small class="finanzas-subline">Registro #${m.id}</small>` : ''}</td>
                <td>${badgeCategoria(m.categoria, idCat)}</td>
                <td><span class="finanzas-badge tipo-badge">${esIngreso ? 'Ingreso' : 'Gasto'}</span></td>
                <td class="text-right finanzas-amount">${dinero.format(Number(m.monto))}</td></tr>`;
        }).join('');
    }

    function renderProductos(data) {
        const body = $('productosBody');
        if (!body)
            return;
        if (!data.productos.length) {
            body.innerHTML = '<tr><td colspan="3" class="finanzas-empty">No hay ventas pagadas en el periodo seleccionado.</td></tr>';
            return;
        }
        body.innerHTML = data.productos.map(p => `<tr><td><strong>${escapar(p.nombre)}</strong></td>
            <td>${numero.format(Number(p.unidades))}</td><td class="text-right finanzas-amount">${dinero.format(Number(p.total))}</td></tr>`).join('');
    }

    function renderReportes(data) {
        const t = data.totales;
        $('finanzasKpis').innerHTML = [
            kpi('Ingresos', dinero.format(t.ingresos), '↗'),
            kpi('Gastos', dinero.format(t.gastos), '↘'),
            kpi('Balance', dinero.format(t.balance), '＝', 'Ingresos menos gastos')
        ].join('');
        $('balanceKpis').innerHTML = $('finanzasKpis').innerHTML;
        const labels = data.balance.map(x => x.fecha);
        balanceChart = crearGrafico('evolucionChart', balanceChart, {
            type: 'bar',
            data: { labels, datasets: [
                    { label: 'Ingresos', data: data.balance.map(x => Number(x.ingresos)), backgroundColor: '#D58BA6', borderRadius: 5 },
                    { label: 'Gastos', data: data.balance.map(x => Number(x.gastos)), backgroundColor: '#B8896A', borderRadius: 5 }
                ] },
            options: { ...opcionesGrafico(), plugins: { ...opcionesGrafico().plugins, legend: { display: true, position: 'bottom' } } }
        });
        $('balanceBody').innerHTML = data.balance.length ? data.balance.map(x => `<tr>
            <td>${escapar(x.fecha)}</td><td class="text-right">${dinero.format(Number(x.ingresos))}</td>
            <td class="text-right">${dinero.format(Number(x.gastos))}</td>
            <td class="text-right finanzas-amount">${dinero.format(Number(x.balance))}</td>
            <td class="text-right">${dinero.format(Number(x.acumulado))}</td></tr>`).join('')
            : '<tr><td colspan="5" class="finanzas-empty">No hay movimientos en el periodo seleccionado.</td></tr>';
        $('historialBody').innerHTML = data.historial.length ? data.historial.map(r => `<tr>
            <td>${escapar(r.fecha_generacion)}</td><td>${escapar(r.periodo_inicio)} – ${escapar(r.periodo_fin)}</td>
            <td class="text-right">${dinero.format(Number(r.total_ingresos))}</td>
            <td class="text-right">${dinero.format(Number(r.total_gastos))}</td>
            <td class="text-right finanzas-amount">${dinero.format(Number(r.balance))}</td></tr>`).join('')
            : '<tr><td colspan="5" class="finanzas-empty">Todavía no has guardado reportes históricos.</td></tr>';
    }

    async function cargar() {
        try {
            const data = await solicitar(`${api}?${parametros().toString()}`);
            datosActuales = data;
            if (esReportes)
                renderReportes(data);
            else {
                renderResumenFinanciero(data);
                renderMovimientos(data);
                renderProductos(data);
                renderCategoriasSelect(data.categorias);
            }
        }
        catch (error) {
            toast(error.message, true);
        }
    }

    function cambiarTab(tab) {
        document.querySelectorAll('.finanzas-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
        document.querySelectorAll('.finanzas-panel').forEach(p => p.classList.toggle('active', p.dataset.panel === tab));
    }

    document.querySelectorAll('.finanzas-tab').forEach(b => b.addEventListener('click', () => cambiarTab(b.dataset.tab)));
    $('periodoFecha')?.addEventListener('change', (event) => aplicarPeriodo(event.target.value));
    $('aplicarFechas')?.addEventListener('click', cargar);
    $('filtroCategoria')?.addEventListener('change', cargar);
    $('buscarMovimiento')?.addEventListener('input', () => {
        window.clearTimeout(window.__finanzasSearchTimer);
        window.__finanzasSearchTimer = window.setTimeout(cargar, 250);
    });
    $('guardarReporte')?.addEventListener('click', async () => {
        const desde = $('fechaDesde').value, hasta = $('fechaHasta').value;
        if (!desde || !hasta)
            return toast('Selecciona un rango de fechas antes de guardar el reporte.', true);
        try {
            await solicitar(`${api}?tipo=reportes`, {
                method: 'POST', body: JSON.stringify({ desde, hasta })
            });
            toast('Reporte histórico guardado correctamente.');
            await cargar();
        }
        catch (error) {
            toast(error.message, true);
        }
    });

    aplicarPeriodo($('periodoFecha')?.value || 'mes_actual', false);
    cargar();
})();
