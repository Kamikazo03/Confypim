document.addEventListener('DOMContentLoaded', () => {

    const API_URL = 'ajax/venta.php';

    let ventas = [];

    inicializar();


    /*
     * =========================================================
     * INICIALIZACIÓN
     * =========================================================
     */

    async function inicializar() {

        registrarEventos();

        await cargarVentas();

        await cargarMetricas();

        await cargarInformacionCanceladas();
    }


    /*
     * =========================================================
     * EVENTOS
     * =========================================================
     */

    function registrarEventos() {

        const btnNuevaVenta =
            document.getElementById('btnNuevaVenta');

        if (btnNuevaVenta) {

            btnNuevaVenta.addEventListener(
                'click',
                nuevaVenta
            );
        }


        const buscarVenta =
            document.getElementById('buscarVenta');

        if (buscarVenta) {

            buscarVenta.addEventListener(
                'input',
                filtrarVentas
            );
        }


        const estadoVenta =
            document.getElementById('estadoVenta');

        if (estadoVenta) {

            estadoVenta.addEventListener(
                'change',
                cargarVentas
            );
        }


        document.addEventListener(
            'click',
            manejarAccionesVenta
        );
    }


    /*
     * =========================================================
     * CARGAR VENTAS
     * =========================================================
     */

    async function cargarVentas() {

        try {

            const estado =
                document.getElementById(
                    'estadoVenta'
                )?.value || '';

            let url = API_URL;

            if (estado) {

                url +=
                    `?estado=${encodeURIComponent(
                        estado
                    )}`;
            }


            const response =
                await fetch(url);


            const data =
                await response.json();


            if (!data.success) {

                mostrarError(
                    data.message ||
                    'No fue posible cargar las ventas.'
                );

                return;
            }


            ventas =
                data.data || [];


            renderizarVentas(ventas);


            /*
             * Cuando se cambia el estado,
             * también actualizamos la información
             * de cancelaciones.
             */
            await cargarInformacionCanceladas();

        } catch (error) {

            console.error(
                'Error cargando ventas:',
                error
            );

            mostrarError(
                'No fue posible cargar las ventas.'
            );
        }
    }


    /*
     * =========================================================
     * RENDERIZAR TABLA
     * =========================================================
     */

    function renderizarVentas(lista) {

        const tabla =
            document.getElementById(
                'tablaVentas'
            );


        if (!tabla) {
            return;
        }


        const tbody =
            tabla.querySelector('tbody');


        if (!tbody) {
            return;
        }


        tbody.innerHTML = '';


        if (!lista.length) {

            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="6"
                        class="text-center">

                        No hay ventas para mostrar.

                    </td>
                </tr>
            `;

            return;
        }


        lista.forEach(venta => {

            const fila =
                document.createElement('tr');


            const estadoClase =
                obtenerClaseEstado(
                    venta.estado
                );


            let acciones = `
                <button
                    type="button"
                    class="btn-ver-venta"
                    data-id="${venta.id_venta}"
                    title="Ver venta">

                    <i class="bi bi-eye"></i>

                    Ver

                </button>
            `;


            /*
             * Solo las ventas Pendientes
             * pueden editarse.
             */
            if (venta.estado === 'Pendiente') {

                acciones += `
                    <button
                        type="button"
                        class="btn-editar-venta"
                        data-id="${venta.id_venta}"
                        title="Editar venta">

                        <i class="bi bi-pencil"></i>

                        Editar

                    </button>
                `;
            }


            fila.innerHTML = `

                <td>
                    #${venta.id_venta}
                </td>


                <td>
                    ${escapeHtml(
                        venta.cliente
                    )}
                </td>


                <td>
                    ${formatearFecha(
                        venta.fecha_venta
                    )}
                </td>


                <td>
                    $${formatearNumero(
                        venta.total
                    )}
                </td>


                <td>

                    <span
                        class="badge ${estadoClase}">

                        ${escapeHtml(
                            venta.estado
                        )}

                    </span>

                </td>


                <td>

                    <div
                        class="table-actions">

                        ${acciones}

                    </div>

                </td>

            `;


            tbody.appendChild(fila);
        });
    }


    /*
     * =========================================================
     * FILTRO DE BÚSQUEDA
     * =========================================================
     */

    function filtrarVentas() {

        const input =
            document.getElementById(
                'buscarVenta'
            );


        const texto =
            input
                ? input.value
                    .trim()
                    .toLowerCase()
                : '';


        const filtradas =
            ventas.filter(venta => {

                const id =
                    String(
                        venta.id_venta
                    ).toLowerCase();


                const cliente =
                    String(
                        venta.cliente
                    ).toLowerCase();


                return (
                    id.includes(texto) ||
                    cliente.includes(texto)
                );
            });


        renderizarVentas(
            filtradas
        );
    }


    /*
     * =========================================================
     * ACCIONES
     * =========================================================
     */

    function manejarAccionesVenta(evento) {

        const btnVer =
            evento.target.closest(
                '.btn-ver-venta'
            );


        if (btnVer) {

            const id =
                parseInt(
                    btnVer.dataset.id,
                    10
                );


            verVenta(id);

            return;
        }


        const btnEditar =
            evento.target.closest(
                '.btn-editar-venta'
            );


        if (btnEditar) {

            const id =
                parseInt(
                    btnEditar.dataset.id,
                    10
                );


            editarVenta(id);
        }
    }


    /*
     * =========================================================
     * VER VENTA
     * =========================================================
     */

    async function verVenta(id) {

        try {

            const response =
                await fetch(
                    `${API_URL}?id=${id}`
                );


            const data =
                await response.json();


            if (!data.success) {

                mostrarError(
                    data.message
                );

                return;
            }


            console.log(
                'Detalle de venta:',
                data.data
            );


            /*
             * Por ahora mostramos el resultado
             * en consola y una notificación.
             *
             * El modal de detalle será uno de
             * los componentes que posteriormente
             * podremos convertir en React.
             */
            mostrarToast(
                `Venta #${id} cargada correctamente.`
            );


        } catch (error) {

            console.error(error);

            mostrarError(
                'No fue posible consultar la venta.'
            );
        }
    }


    /*
     * =========================================================
     * EDITAR VENTA
     * =========================================================
     */

    async function editarVenta(id) {

        try {

            const response =
                await fetch(
                    `${API_URL}?id=${id}`
                );


            const data =
                await response.json();


            if (!data.success) {

                mostrarError(
                    data.message
                );

                return;
            }


            if (
                data.data.estado !==
                'Pendiente'
            ) {

                mostrarError(
                    'Solo se pueden editar ventas pendientes.'
                );

                return;
            }


            console.log(
                'Venta para editar:',
                data.data
            );


            mostrarToast(
                `Venta #${id} lista para editar.`
            );


        } catch (error) {

            console.error(error);

            mostrarError(
                'No fue posible cargar la venta.'
            );
        }
    }


    /*
     * =========================================================
     * NUEVA VENTA
     * =========================================================
     */

    function nuevaVenta() {

        console.log(
            'Preparar nueva venta'
        );


        mostrarToast(
            'Formulario de nueva venta preparado.'
        );
    }


    /*
     * =========================================================
     * MÉTRICAS
     * =========================================================
     */

    async function cargarMetricas() {

        try {

            const response =
                await fetch(
                    `${API_URL}?accion=metricas`
                );


            const data =
                await response.json();


            if (!data.success) {
                return;
            }


            actualizarMetricas(
                data.data
            );


        } catch (error) {

            console.error(
                'Error cargando métricas:',
                error
            );
        }
    }


    function actualizarMetricas(
        metricas
    ) {

        const ventasPagadas =
            document.getElementById(
                'metricVentasPagadas'
            );


        const productosVendidos =
            document.getElementById(
                'metricProductosVendidos'
            );


        const productosDiferentes =
            document.getElementById(
                'metricProductosDiferentes'
            );


        const ingresos =
            document.getElementById(
                'metricIngresos'
            );


        if (ventasPagadas) {

            ventasPagadas.textContent =
                formatearNumero(
                    metricas.ventas_pagadas
                );
        }


        if (productosVendidos) {

            productosVendidos.textContent =
                formatearNumero(
                    metricas.productos_vendidos
                );
        }


        if (productosDiferentes) {

            productosDiferentes.textContent =
                formatearNumero(
                    metricas.productos_diferentes
                );
        }


        if (ingresos) {

            ingresos.textContent =
                '$' +
                formatearNumero(
                    metricas.ingresos_totales
                );
        }
    }


    /*
     * =========================================================
     * INFORMACIÓN DE CANCELADAS
     * =========================================================
     */

    async function cargarInformacionCanceladas() {

        try {

            const response =
                await fetch(
                    `${API_URL}?estado=Cancelada`
                );


            const data =
                await response.json();


            if (!data.success) {
                return;
            }


            const canceladas =
                data.data || [];


            actualizarInformacionCanceladas(
                canceladas
            );


        } catch (error) {

            console.error(
                'Error cargando canceladas:',
                error
            );
        }
    }


    function actualizarInformacionCanceladas(
        canceladas
    ) {

        const contenedor =
            document.getElementById(
                'cancelledSalesInfo'
            );


        const total =
            document.getElementById(
                'metricVentasCanceladas'
            );


        const cliente =
            document.getElementById(
                'clienteMasCancelaciones'
            );


        if (!contenedor) {
            return;
        }


        /*
         * La información solamente aparece
         * cuando existen ventas canceladas.
         */
        if (!canceladas.length) {

            contenedor.style.display =
                'none';

            if (total) {
                total.textContent = '0';
            }

            if (cliente) {
                cliente.textContent =
                    'Sin información';
            }

            return;
        }


        contenedor.style.display =
            'flex';


        if (total) {

            total.textContent =
                formatearNumero(
                    canceladas.length
                );
        }


        /*
         * Calculamos el cliente con más
         * ventas canceladas.
         */
        const contadorClientes =
            {};


        canceladas.forEach(venta => {

            const nombre =
                venta.cliente ||
                'Cliente General';


            if (
                !contadorClientes[nombre]
            ) {

                contadorClientes[nombre] =
                    0;
            }


            contadorClientes[nombre]++;
        });


        let clienteMayor =
            'Sin información';


        let cantidadMayor = 0;


        Object.entries(
            contadorClientes
        ).forEach(
            ([nombre, cantidad]) => {

                if (
                    cantidad >
                    cantidadMayor
                ) {

                    clienteMayor =
                        nombre;

                    cantidadMayor =
                        cantidad;
                }
            }
        );


        if (cliente) {

            cliente.textContent =
                `${clienteMayor} (${cantidadMayor})`;
        }
    }


    /*
     * =========================================================
     * UTILIDADES
     * =========================================================
     */

    function obtenerClaseEstado(
        estado
    ) {

        switch (estado) {

            case 'Pagada':
                return 'pagada';

            case 'Pendiente':
                return 'pendiente';

            case 'Cancelada':
                return 'cancelada';

            default:
                return '';
        }
    }


    function formatearNumero(
        numero
    ) {

        return Number(
            numero || 0
        ).toLocaleString(
            'es-CO',
            {
                maximumFractionDigits: 0
            }
        );
    }


    function formatearFecha(
        fecha
    ) {

        if (!fecha) {
            return '-';
        }


        const fechaObjeto =
            new Date(
                fecha.replace(
                    ' ',
                    'T'
                )
            );


        if (
            Number.isNaN(
                fechaObjeto.getTime()
            )
        ) {

            return fecha;
        }


        return fechaObjeto.toLocaleDateString(
            'es-CO',
            {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }
        );
    }


    function escapeHtml(
        texto
    ) {

        const div =
            document.createElement(
                'div'
            );


        div.textContent =
            texto ?? '';


        return div.innerHTML;
    }


    function mostrarToast(
        mensaje
    ) {

        if (
            typeof window.mostrarToast ===
            'function'
        ) {

            window.mostrarToast(
                mensaje
            );

            return;
        }


        console.log(
            mensaje
        );
    }


    function mostrarError(
        mensaje
    ) {

        if (
            typeof window.mostrarToast ===
            'function'
        ) {

            window.mostrarToast(
                mensaje,
                'error'
            );

            return;
        }


        console.error(
            mensaje
        );
    }

});