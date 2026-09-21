'use strict';


/* ============================================================
   CONFIGURACIÓN GENERAL
   ============================================================ */

/*
    URL base del backend de ventas.
    El controlador REST ya fue creado anteriormente.
*/
const API_VENTAS = '/api/ventas';


/*
    URL utilizada para consultar los productos activos.
*/
const API_PRODUCTOS = '/api/productos';


/*
    Variables que almacenan los datos cargados desde el backend.
*/
let ventas = [];

let productos = [];


/*
    Variable utilizada para saber si el formulario está creando
    una venta nueva o editando una venta existente.

    null = creación.
    Número = edición.
*/
let ventaEnEdicion = null;


/* ============================================================
   ELEMENTOS DEL DOM
   ============================================================ */

/*
    Se obtienen los elementos HTML que serán utilizados
    frecuentemente por las funciones JavaScript.
*/
const salesTableBody =
    document.getElementById('sales-table-body');


const searchInput =
    document.getElementById('input-search');


const statusSelect =
    document.getElementById('select-status');


const messageContainer =
    document.getElementById('message-container');


const saleModal =
    document.getElementById('sale-modal');


const detailsModal =
    document.getElementById('details-modal');


const saleForm =
    document.getElementById('sale-form');


const productsContainer =
    document.getElementById('sale-products-container');


const emptyProductsMessage =
    document.getElementById('empty-products-message');


const saleTotalElement =
    document.getElementById('sale-total');


const saleIdInput =
    document.getElementById('input-sale-id');


/*  Referencia al selector de cliente.
    Actualmente solamente contiene la opción
    Cliente General.
*/
const clientSelect =
    document.getElementById('select-client');


const userIdInput =
    document.getElementById('input-user-id');


/* ============================================================
   INICIALIZACIÓN
   ============================================================ */

/*
    DOMContentLoaded se ejecuta cuando el HTML
    ya se encuentra completamente cargado.
*/
document.addEventListener('DOMContentLoaded', () => {

    /*
        Se registran los eventos de los botones,
        formularios y filtros.
    */
    configurarEventos();


    /*
        Se cargan los datos iniciales de la aplicación.
    */
    cargarDatosIniciales();

});


/* ============================================================
   CONFIGURACIÓN DE EVENTOS
   ============================================================ */

function configurarEventos() {

    /*
        Botón para abrir el formulario de una venta nueva.
    */
    document.getElementById('button-new-sale')
        .addEventListener('click', abrirFormularioNuevaVenta);


    /*
        Botón para actualizar la información de la tabla.
    */
    document.getElementById('button-refresh')
        .addEventListener('click', cargarVentas);


    /*
        Los filtros actualizan el listado cada vez
        que cambia su contenido.
    */
    searchInput.addEventListener('input', aplicarFiltros);


    statusSelect.addEventListener('change', aplicarFiltros);


    /*
        Botón para agregar una nueva fila de producto.
    */
    document.getElementById('button-add-product')
        .addEventListener('click', () => {

            agregarFilaProducto();

        });


    /*
        Botones utilizados para cerrar los modales.
    */
    document.getElementById('button-close-sale-modal')
        .addEventListener('click', cerrarFormularioVenta);


    document.getElementById('button-cancel-sale-form')
        .addEventListener('click', cerrarFormularioVenta);


    document.getElementById('button-close-details-modal')
        .addEventListener('click', cerrarModalDetalles);


    /*
        El formulario utiliza submit para guardar
        o actualizar una venta.
    */
    saleForm.addEventListener('submit', guardarVenta);


    /*
        Permite cerrar los modales haciendo clic
        en el fondo exterior.
    */
    saleModal.addEventListener('click', (event) => {

        if (event.target === saleModal) {

            cerrarFormularioVenta();

        }

    });


    detailsModal.addEventListener('click', (event) => {

        if (event.target === detailsModal) {

            cerrarModalDetalles();

        }

    });

}


/* ============================================================
   CARGA INICIAL
   ============================================================ */

async function cargarDatosIniciales() {

    /*
        Se cargan productos y ventas.
        Promise.all permite realizar ambas solicitudes
        de manera concurrente.
    */
    try {

        await Promise.all([

            cargarProductos(),

            cargarVentas()

        ]);

    } catch (error) {

        mostrarMensaje(
            'No fue posible cargar los datos iniciales.',
            'error'
        );

        console.error(error);

    }

}


/* ============================================================
   CONSULTA DE PRODUCTOS
   ============================================================ */

async function cargarProductos() {

    /*
        Se realiza una solicitud GET al endpoint
        que devuelve los productos activos.
    */
    const response = await fetch(API_PRODUCTOS);


    /*
        Si la respuesta no es exitosa,
        se genera un error para manejarlo posteriormente.
    */
    if (!response.ok) {

        throw new Error('No fue posible cargar los productos.');

    }


    /*
        Se convierte la respuesta HTTP en un arreglo JavaScript.
    */
    productos = await response.json();


    /*
        Se valida que la respuesta sea un arreglo.
    */
    if (!Array.isArray(productos)) {

        productos = [];

    }

}


/* ============================================================
   CONSULTA DE VENTAS
   ============================================================ */

async function cargarVentas() {

    try {

        /*
            Se muestra un mensaje temporal mientras
            se consulta el backend.
        */
        salesTableBody.innerHTML = `

            <tr>

                <td colspan="6"
                    class="table-loading">

                    Cargando ventas...

                </td>

            </tr>

        `;


        /*
            Solicitud GET al endpoint de ventas.
        */
        const response = await fetch(API_VENTAS);


        /*
            Se valida el estado de la respuesta.
        */
        if (!response.ok) {

            throw new Error('No fue posible consultar las ventas.');

        }


        /*
            Conversión de la respuesta a formato JSON.
        */
        ventas = await response.json();


        /*
            Se garantiza que la variable sea un arreglo.
        */
        if (!Array.isArray(ventas)) {

            ventas = [];

        }


        /*
            Se actualizan las tarjetas informativas.
        */
        actualizarMetricas();


        /*
            Se aplican los filtros y se dibuja la tabla.
        */
        aplicarFiltros();


    } catch (error) {

        console.error(error);


        salesTableBody.innerHTML = `

            <tr>

                <td colspan="6"
                    class="table-empty">

                    No fue posible cargar las ventas.

                </td>

            </tr>

        `;


        mostrarMensaje(
            'Ocurrió un error al consultar las ventas.',
            'error'
        );

    }

}


/* ============================================================
   MÉTRICAS
   ============================================================ */

function actualizarMetricas() {

    /*
        Se cuentan las ventas según su estado.
    */
    const totalVentas = ventas.length;


    const ventasPendientes = ventas.filter(
        venta => venta.estado === 'Pendiente'
    ).length;


    const ventasPagadas = ventas.filter(
        venta => venta.estado === 'Pagada'
    ).length;


    /*
        Se calcula el ingreso total únicamente
        de las ventas pagadas.
    */
    const ingresosPagados = ventas

        .filter(venta => venta.estado === 'Pagada')

        .reduce((total, venta) => {

            return total + convertirNumero(venta.total);

        }, 0);


    /*
        Se actualizan los valores en el HTML.
    */
    document.getElementById('metric-total-sales')
        .textContent = totalVentas;


    document.getElementById('metric-pending-sales')
        .textContent = ventasPendientes;


    document.getElementById('metric-paid-sales')
        .textContent = ventasPagadas;


    document.getElementById('metric-paid-income')
        .textContent = formatearMoneda(ingresosPagados);

}


/* ============================================================
   FILTROS
   ============================================================ */

function aplicarFiltros() {

    /*
        Se obtiene el texto de búsqueda en minúsculas.
    */
    const textoBusqueda = searchInput.value
        .trim()
        .toLowerCase();


    /*
        Se obtiene el estado seleccionado.
    */
    const estadoSeleccionado = statusSelect.value;


    /*
        Se filtran las ventas según el texto y el estado.
    */
    const ventasFiltradas = ventas.filter(venta => {

        /*
            Se convierten los campos de búsqueda
            a texto para evitar errores con valores nulos.
        */
        const id = String(venta.idVenta ?? '');

        const cliente = String(venta.idCliente ?? '');

        const estado = String(venta.estado ?? '');


        /*
            Se valida si la búsqueda coincide
            con el ID o el cliente.
        */
        const coincideTexto =

            id.toLowerCase().includes(textoBusqueda) ||

            cliente.toLowerCase().includes(textoBusqueda);


        /*
            Si se selecciona "Todos",
            no se filtra por estado.
        */
        const coincideEstado =

            estadoSeleccionado === 'Todos' ||

            estado === estadoSeleccionado;


        return coincideTexto && coincideEstado;

    });


    /*
        Se dibuja nuevamente la tabla.
    */
    renderizarVentas(ventasFiltradas);

}


/* ============================================================
   RENDERIZACIÓN DE LA TABLA
   ============================================================ */

function renderizarVentas(ventasParaMostrar) {

    /*
        Si no existen resultados, se muestra
        un mensaje dentro de la tabla.
    */
    if (ventasParaMostrar.length === 0) {

        salesTableBody.innerHTML = `

            <tr>

                <td colspan="6"
                    class="table-empty">

                    No se encontraron ventas.

                </td>

            </tr>

        `;

        return;

    }


    /*
        Se genera el contenido HTML de cada fila.
    */
    salesTableBody.innerHTML = ventasParaMostrar.map(venta => {

        const estado = String(venta.estado ?? '');

        const claseEstado = obtenerClaseEstado(estado);

        const cliente = obtenerNombreCliente(venta);

        const fecha = formatearFecha(venta.fechaVenta);

        const total = formatearMoneda(venta.total);


        /*
            Las acciones disponibles dependen del estado.
        */
        let acciones = `

            <button type="button"
                    class="action-button"
                    data-action="details"
                    data-id="${venta.idVenta}">

                Ver

            </button>

        `;


        /*
            Las ventas pendientes pueden editarse,
            pagarse o cancelarse.
        */
        if (estado === 'Pendiente') {

            acciones += `

                <button type="button"
                        class="action-button"
                        data-action="edit"
                        data-id="${venta.idVenta}">

                    Editar

                </button>

                <button type="button"
                        class="action-button pay"
                        data-action="pay"
                        data-id="${venta.idVenta}">

                    Pagar

                </button>

                <button type="button"
                        class="action-button cancel"
                        data-action="cancel"
                        data-id="${venta.idVenta}">

                    Cancelar

                </button>

            `;

        }


        /*
            Se retorna una fila completa de la tabla.
        */
        return `

            <tr>

                <td>
                    #${venta.idVenta}
                </td>

                <td>
                    ${escaparHTML(fecha)}
                </td>

                <td>
                    ${escaparHTML(cliente)}
                </td>

                <td>
                    ${total}
                </td>

                <td>

                    <span class="status-badge ${claseEstado}">

                        ${escaparHTML(estado)}

                    </span>

                </td>

                <td>

                    <div class="table-actions">

                        ${acciones}

                    </div>

                </td>

            </tr>

        `;

    }).join('');


    /*
        Se agregan eventos a los botones generados.
    */
    agregarEventosAcciones();

}


/* ============================================================
   EVENTOS DE ACCIONES
   ============================================================ */

function agregarEventosAcciones() {

    /*
        Se obtienen todos los botones de acción
        creados dentro de la tabla.
    */
    const actionButtons =
        document.querySelectorAll('[data-action]');


    actionButtons.forEach(button => {

        button.addEventListener('click', () => {

            const action = button.dataset.action;

            const id = Number(button.dataset.id);


            /*
                Se ejecuta una función según la acción.
            */
            switch (action) {

                case 'details':

                    mostrarDetallesVenta(id);

                    break;


                case 'edit':

                    abrirFormularioEditarVenta(id);

                    break;


                case 'pay':

                    pagarVenta(id);

                    break;


                case 'cancel':

                    cancelarVenta(id);

                    break;


                default:

                    console.warn(
                        'Acción no reconocida:',
                        action
                    );

            }

        });

    });

}


/* ============================================================
   FORMULARIO DE CREACIÓN
   ============================================================ */

function abrirFormularioNuevaVenta() {

    /*
        Se establece el modo de creación.
    */
    ventaEnEdicion = null;


    /*
        Se limpia el formulario.
    */
    saleForm.reset();


    /*
        Se elimina el ID de venta.
    */
    saleIdInput.value = '';


    /*
        Se actualiza el título del modal.
    */
    document.getElementById('sale-modal-title')
        .textContent = 'Nueva venta';


    /*
        Se actualiza el texto del botón.
    */
    document.getElementById('button-save-sale')
        .textContent = 'Guardar venta';


    /*
        Se limpia el listado de productos.
    */
    productsContainer.innerHTML = '';


    /*
        Se agrega una fila inicial.
    */
    agregarFilaProducto();


    /*
        Se muestra el modal.
    */
    abrirModal(saleModal);

}


/* ============================================================
   FORMULARIO DE EDICIÓN
   ============================================================ */

async function abrirFormularioEditarVenta(idVenta) {

    try {

        /*
            Se consulta la venta completa.
        */
        const response = await fetch(`${API_VENTAS}/${idVenta}`);


        if (!response.ok) {

            throw new Error('No se pudo consultar la venta.');

        }


        const resultado = await response.json();

        const venta = resultado.venta;

        const detalles = resultado.detalles;


        /*
            Solo se pueden editar ventas pendientes.
        */
        if (venta.estado !== 'Pendiente') {

            mostrarMensaje(
                'Solo se pueden editar ventas pendientes.',
                'error'
            );

            return;

        }


        /*
            Se guarda el ID de la venta que se editará.
        */
        ventaEnEdicion = idVenta;


        saleIdInput.value = idVenta;


        /*  Como esta versión utiliza exclusivamente
            Cliente General, se selecciona esa opción
            al editar una venta pendiente.
        */
        clientSelect.value = 'general';


        userIdInput.value = venta.idUsuario ?? '';


        /*
            Se actualiza el título del modal.
        */
        document.getElementById('sale-modal-title')
            .textContent = `Editar venta #${idVenta}`;


        document.getElementById('button-save-sale')
            .textContent = 'Actualizar venta';


        /*
            Se limpian las filas anteriores.
        */
        productsContainer.innerHTML = '';


        /*
            Se agregan los detalles existentes.
        */
        detalles.forEach(detalle => {

            agregarFilaProducto(detalle);

        });


        /*
            Si no existen detalles, se agrega una fila vacía.
        */
        if (!detalles || detalles.length === 0) {

            agregarFilaProducto();

        }


        actualizarTotalFormulario();


        abrirModal(saleModal);


    } catch (error) {

        console.error(error);


        mostrarMensaje(
            'No fue posible cargar la información de la venta.',
            'error'
        );

    }

}


/* ============================================================
   FILAS DE PRODUCTOS
   ============================================================ */

function agregarFilaProducto(detalle = null) {

    /*
        Se crea un contenedor para una línea de producto.
    */
    const row = document.createElement('div');

    row.className = 'product-row';


    /*
        Se crea el campo de selección del producto.
    */
    const productGroup = document.createElement('div');

    productGroup.className = 'form-group product-selector';


    const productLabel = document.createElement('label');

    productLabel.textContent = 'Producto';


    const productSelect = document.createElement('select');

    productSelect.className = 'form-control product-select';

    productSelect.required = true;


    /*
        Opción inicial para obligar al usuario
        a seleccionar un producto válido.
    */
    const defaultOption = document.createElement('option');

    defaultOption.value = '';

    defaultOption.textContent = 'Seleccionar producto';

    defaultOption.disabled = true;

    defaultOption.selected = !detalle;


    productSelect.appendChild(defaultOption);


    /*
        Se agregan los productos activos al selector.
    */
    productos.forEach(producto => {

        const option = document.createElement('option');

        option.value = producto.idProducto;

        option.textContent = producto.nombre;


        /*
            Se almacena información adicional del producto
            mediante atributos data.
        */
        option.dataset.price = producto.precioUnitario;

        option.dataset.stock = producto.stockDisponible;


        /*
            Se selecciona el producto si se está editando.
        */
        if (detalle &&
            Number(detalle.idProducto) === Number(producto.idProducto)) {

            option.selected = true;

        }


        productSelect.appendChild(option);

    });


    productGroup.appendChild(productLabel);

    productGroup.appendChild(productSelect);


    /*
        Se crea el campo de cantidad.
    */
    const quantityGroup = document.createElement('div');

    quantityGroup.className = 'form-group';


    const quantityLabel = document.createElement('label');

    quantityLabel.textContent = 'Cantidad';


    const quantityInput = document.createElement('input');

    quantityInput.type = 'number';

    quantityInput.className = 'form-control product-quantity';

    quantityInput.min = '1';

    quantityInput.step = '1';

    quantityInput.required = true;

    quantityInput.value = detalle?.cantidad ?? 1;


    quantityGroup.appendChild(quantityLabel);

    quantityGroup.appendChild(quantityInput);


    /*
        Campo visual para mostrar el precio unitario.
    */
    const priceGroup = document.createElement('div');

    priceGroup.className = 'form-group';


    const priceLabel = document.createElement('label');

    priceLabel.textContent = 'Precio';


    const priceElement = document.createElement('div');

    priceElement.className = 'product-price';

    priceElement.textContent = '$0';


    priceGroup.appendChild(priceLabel);

    priceGroup.appendChild(priceElement);


    /*
        Campo visual para mostrar el subtotal.
    */
    const subtotalGroup = document.createElement('div');

    subtotalGroup.className = 'form-group';


    const subtotalLabel = document.createElement('label');

    subtotalLabel.textContent = 'Subtotal';


    const subtotalElement = document.createElement('div');

    subtotalElement.className = 'product-subtotal';

    subtotalElement.textContent = '$0';


    subtotalGroup.appendChild(subtotalLabel);

    subtotalGroup.appendChild(subtotalElement);


    /*
        Botón para eliminar la fila.
    */
    const removeButton = document.createElement('button');

    removeButton.type = 'button';

    removeButton.className = 'remove-product-button';

    removeButton.textContent = '×';

    removeButton.title = 'Eliminar producto';


    removeButton.addEventListener('click', () => {

        row.remove();

        actualizarEstadoProductos();

        actualizarTotalFormulario();

    });


    /*
        Evento que se ejecuta cuando cambia el producto.
    */
    productSelect.addEventListener('change', () => {

        actualizarFilaProducto(row);

        actualizarTotalFormulario();

    });


    /*
        Evento que se ejecuta cuando cambia la cantidad.
    */
    quantityInput.addEventListener('input', () => {

        actualizarFilaProducto(row);

        actualizarTotalFormulario();

    });


    /*
        Se agregan todos los componentes a la fila.
    */
    row.appendChild(productGroup);

    row.appendChild(quantityGroup);

    row.appendChild(priceGroup);

    row.appendChild(subtotalGroup);

    row.appendChild(removeButton);


    /*
        Se agrega la fila al contenedor.
    */
    productsContainer.appendChild(row);


    /*
        Se actualizan los valores visuales.
    */
    actualizarFilaProducto(row);

    actualizarEstadoProductos();

}


/* ============================================================
   ACTUALIZACIÓN DE UNA FILA DE PRODUCTO
   ============================================================ */

function actualizarFilaProducto(row) {

    /*
        Se obtienen los controles de la fila.
    */
    const productSelect =
        row.querySelector('.product-select');


    const quantityInput =
        row.querySelector('.product-quantity');


    const priceElement =
        row.querySelector('.product-price');


    const subtotalElement =
        row.querySelector('.product-subtotal');


    /*
        Se obtiene la opción seleccionada.
    */
    const selectedOption =
        productSelect.options[productSelect.selectedIndex];


    /*
        Si no existe un producto seleccionado,
        se reinician los valores visuales.
    */
    if (!selectedOption ||
        !selectedOption.value) {

        priceElement.textContent = '$0';

        subtotalElement.textContent = '$0';

        return;

    }


    /*
        Se obtiene el precio del producto.
    */
    const price =
        convertirNumero(selectedOption.dataset.price);


    /*
        Se obtiene la cantidad.
    */
    const quantity =
        Math.max(0, convertirNumero(quantityInput.value));


    /*
        Se calcula el subtotal.
    */
    const subtotal = price * quantity;


    /*
        Se actualizan los valores visuales.
    */
    priceElement.textContent = formatearMoneda(price);

    subtotalElement.textContent = formatearMoneda(subtotal);

}


/* ============================================================
   ESTADO DEL CONTENEDOR DE PRODUCTOS
   ============================================================ */

function actualizarEstadoProductos() {

    /*
        Se comprueba si existen filas de productos.
    */
    const cantidadFilas =
        productsContainer.querySelectorAll('.product-row').length;


    /*
        El mensaje se muestra cuando no existen filas.
    */
    emptyProductsMessage.style.display =

        cantidadFilas === 0 ? 'block' : 'none';

}


/* ============================================================
   CÁLCULO DEL TOTAL
   ============================================================ */

function actualizarTotalFormulario() {

    /*
        Se obtienen todas las filas actuales.
    */
    const rows =
        productsContainer.querySelectorAll('.product-row');


    let total = 0;


    /*
        Se recorre cada fila para sumar sus subtotales.
    */
    rows.forEach(row => {

        const productSelect =
            row.querySelector('.product-select');


        const quantityInput =
            row.querySelector('.product-quantity');


        const selectedOption =
            productSelect.options[productSelect.selectedIndex];


        if (!selectedOption ||
            !selectedOption.value) {

            return;

        }


        const price =
            convertirNumero(selectedOption.dataset.price);


        const quantity =
            convertirNumero(quantityInput.value);


        total += price * quantity;

    });


    /*
        Se muestra el total calculado.
    */
    saleTotalElement.textContent =
        formatearMoneda(total);

}


/* ============================================================
   GUARDAR O ACTUALIZAR UNA VENTA
   ============================================================ */

async function guardarVenta(event) {

    /*
        Se evita que el formulario recargue la página.
    */
    event.preventDefault();


    /*
        Se obtiene el ID del usuario responsable
        de registrar la venta.
    */
    const idUsuario = Number(userIdInput.value);

    /*
        Se valida que el ID del usuario sea válido.
    */
    if (!idUsuario || idUsuario < 1) {

        mostrarMensaje(
            'Ingresa un ID de usuario válido.',
            'error'
        );

        return;

    }


    /*
        Validación del usuario responsable.
    */
    if (!idUsuario || idUsuario < 1) {

        mostrarMensaje(
            'Ingresa un ID de usuario válido.',
            'error'
        );

        return;

    }


    /*
        Se construye el listado de detalles.
    */
    const detalles = obtenerDetallesFormulario();


    /*
        Se valida que exista al menos un producto.
    */
    if (detalles.length === 0) {

        mostrarMensaje(
            'Debes agregar al menos un producto.',
            'error'
        );

        return;

    }


    /*
        Se comprueba que no existan productos repetidos.
    */
    const idsProductos = detalles.map(
        detalle => detalle.idProducto
    );


    const existenDuplicados =
        new Set(idsProductos).size !== idsProductos.length;


    if (existenDuplicados) {

        mostrarMensaje(
            'No puedes agregar el mismo producto dos veces.',
            'error'
        );

        return;

    }


    /*
        El backend calcula el total real a partir
        de los precios almacenados en la base de datos.

        Por esta razón, no se envía el total calculado
        por el navegador como valor definitivo.
    */
    const venta = {

        idUsuario: idUsuario,

        /*
            El esquema actual de la base de datos utiliza
            id_cliente como referencia a usuario.

            Como el formulario utiliza un nombre libre,
            por ahora se envía null.
        */
        idCliente: null

    };


    /*
        Se construye el objeto final que recibirá el backend.
    */
    const solicitud = {

        venta: venta,

        detalles: detalles

    };


    /*
        Se determina si se realizará una creación
        o una actualización.
    */
    const esEdicion = ventaEnEdicion !== null;


    const url = esEdicion

        ? `${API_VENTAS}/${ventaEnEdicion}`

        : API_VENTAS;


    const method = esEdicion ? 'PUT' : 'POST';


    try {

        /*
            Se deshabilita el botón para evitar
            envíos repetidos mientras se procesa la solicitud.
        */
        const saveButton =
            document.getElementById('button-save-sale');


        saveButton.disabled = true;


        /*
            Solicitud HTTP al backend.
        */
        const response = await fetch(url, {

            method: method,

            headers: {

                'Content-Type': 'application/json'

            },

            body: JSON.stringify(solicitud)

        });


        /*
            Se intenta leer la respuesta del servidor.
        */
        const resultado = await response.json()
            .catch(() => ({}));


        /*
            Se valida el resultado de la operación.
        */
        if (!response.ok) {

            throw new Error(

                resultado.message ||

                resultado.error ||

                'No fue posible guardar la venta.'

            );

        }


        /*
            Se cierra el formulario si la operación fue exitosa.
        */
        cerrarFormularioVenta();


        /*
            Se muestra el mensaje de confirmación.
        */
        mostrarMensaje(

            esEdicion

                ? 'Venta actualizada correctamente.'

                : 'Venta creada correctamente.',

            'success'

        );


        /*
            Se actualiza la tabla.
        */
        await cargarVentas();


    } catch (error) {

        console.error(error);


        mostrarMensaje(

            error.message ||

            'Ocurrió un error al guardar la venta.',

            'error'

        );


    } finally {

        /*
            Se habilita nuevamente el botón.
        */
        document.getElementById('button-save-sale')
            .disabled = false;

    }

}


/* ============================================================
   OBTENER DETALLES DEL FORMULARIO
   ============================================================ */

function obtenerDetallesFormulario() {

    const rows =
        productsContainer.querySelectorAll('.product-row');


    const detalles = [];


    rows.forEach(row => {

        const productSelect =
            row.querySelector('.product-select');


        const quantityInput =
            row.querySelector('.product-quantity');


        const idProducto =
            Number(productSelect.value);


        const cantidad =
            Number(quantityInput.value);


        /*
            Solo se agregan productos con datos válidos.
        */
        if (idProducto > 0 &&
            cantidad > 0) {

            detalles.push({

                idProducto: idProducto,

                cantidad: cantidad

            });

        }

    });


    return detalles;

}


/* ============================================================
   CONSULTAR DETALLES DE UNA VENTA
   ============================================================ */

async function mostrarDetallesVenta(idVenta) {

    const detailsContent =
        document.getElementById('sale-details-content');


    detailsContent.innerHTML =
        '<p>Cargando detalles...</p>';


    abrirModal(detailsModal);


    try {

        /*
            Se consulta la venta junto con sus detalles.
        */
        const response =
            await fetch(`${API_VENTAS}/${idVenta}`);


        if (!response.ok) {

            throw new Error(
                'No fue posible consultar los detalles.'
            );

        }


        const resultado =
            await response.json();


        const venta =
            resultado.venta;


        const detalles =
            resultado.detalles;


        /*
            Se genera la información general de la venta.
        */
        const informacionGeneral = `

            <div class="details-information">

                <div class="detail-item">

                    <span>
                        Número de venta
                    </span>

                    <strong>
                        #${venta.idVenta}
                    </strong>

                </div>

                <div class="detail-item">

                    <span>
                        Fecha
                    </span>

                    <strong>
                        ${escaparHTML(
                            formatearFecha(venta.fechaVenta)
                        )}
                    </strong>

                </div>

                <div class="detail-item">

                    <span>
                        Estado
                    </span>

                    <strong>
                        ${escaparHTML(venta.estado)}
                    </strong>

                </div>

                <div class="detail-item">

                    <span>
                        Usuario responsable
                    </span>

                    <strong>
                        ${escaparHTML(
                            String(venta.idUsuario ?? '')
                        )}
                    </strong>

                </div>

                <!--
                Cliente utilizado en esta versión del sistema.
                -->
                <div class="detail-item">

                    <span>
                        Cliente
                    </span>

                    <strong>
                        Cliente General
                    </strong>

                </div>

            </div>

        `;


        /*
            Se genera la tabla de productos.
        */
        const filasDetalles = detalles.map(detalle => {

            const precio =
                convertirNumero(detalle.precioUnitario);


            const subtotal =
                convertirNumero(detalle.subtotal);


            return `

                <tr>

                    <td>
                        #${detalle.idProducto}
                    </td>

                    <td>
                        ${detalle.cantidad}
                    </td>

                    <td>
                        ${formatearMoneda(precio)}
                    </td>

                    <td>
                        ${formatearMoneda(subtotal)}
                    </td>

                </tr>

            `;

        }).join('');


        /*
            Se muestra el resultado completo dentro del modal.
        */
        detailsContent.innerHTML = `

            ${informacionGeneral}

            <table class="details-products">

                <thead>

                    <tr>

                        <th>
                            Producto
                        </th>

                        <th>
                            Cantidad
                        </th>

                        <th>
                            Precio
                        </th>

                        <th>
                            Subtotal
                        </th>

                    </tr>

                </thead>

                <tbody>

                    ${filasDetalles}

                </tbody>

            </table>


            <div class="details-total">

                <span>
                    Total
                </span>

                <strong>
                    ${formatearMoneda(venta.total)}
                </strong>

            </div>

        `;


    } catch (error) {

        console.error(error);


        detailsContent.innerHTML = `

            <p>
                No fue posible cargar los detalles de la venta.
            </p>

        `;

    }

}


/* ============================================================
   PAGAR VENTA
   ============================================================ */

async function pagarVenta(idVenta) {

    /*
        Se solicita confirmación antes de cambiar el estado.
    */
    const confirmar = window.confirm(

        `¿Deseas marcar la venta #${idVenta} como pagada?`

    );


    if (!confirmar) {

        return;

    }


    try {

        /*
            El backend se encarga de validar el stock,
            descontarlo y cambiar el estado de la venta.
        */
        const response =
            await fetch(`${API_VENTAS}/${idVenta}/pagar`, {

                method: 'PATCH'

            });


        const resultado =
            await response.json().catch(() => ({}));


        if (!response.ok) {

            throw new Error(

                resultado.message ||

                resultado.error ||

                'No fue posible pagar la venta.'

            );

        }


        mostrarMensaje(
            'La venta fue marcada como pagada.',
            'success'
        );


        /*
            Se actualiza la información de la tabla.
        */
        await cargarVentas();


    } catch (error) {

        console.error(error);


        mostrarMensaje(

            error.message ||

            'Ocurrió un error al pagar la venta.',

            'error'

        );

    }

}


/* ============================================================
   CANCELAR VENTA
   ============================================================ */

async function cancelarVenta(idVenta) {

    /*
        Se solicita confirmación antes de cancelar.
    */
    const confirmar = window.confirm(

        `¿Deseas cancelar la venta #${idVenta}?`

    );


    if (!confirmar) {

        return;

    }


    try {

        /*
            La operación solamente debe aplicarse
            a ventas pendientes.
        */
        const response =
            await fetch(`${API_VENTAS}/${idVenta}/cancelar`, {

                method: 'PATCH'

            });


        const resultado =
            await response.json().catch(() => ({}));


        if (!response.ok) {

            throw new Error(

                resultado.message ||

                resultado.error ||

                'No fue posible cancelar la venta.'

            );

        }


        mostrarMensaje(
            'La venta fue cancelada correctamente.',
            'success'
        );


        await cargarVentas();


    } catch (error) {

        console.error(error);


        mostrarMensaje(

            error.message ||

            'Ocurrió un error al cancelar la venta.',

            'error'

        );

    }

}


/* ============================================================
   FUNCIONES PARA LOS MODALES
   ============================================================ */

function abrirModal(modal) {

    /*
        Se agrega la clase open para mostrar el modal.
    */
    modal.classList.add('open');


    /*
        Se actualiza el atributo de accesibilidad.
    */
    modal.setAttribute('aria-hidden', 'false');


    /*
        Se evita que el contenido de fondo
        se desplace mientras el modal está abierto.
    */
    document.body.style.overflow = 'hidden';

}


function cerrarFormularioVenta() {

    saleModal.classList.remove('open');

    saleModal.setAttribute('aria-hidden', 'true');

    document.body.style.overflow = '';

}


function cerrarModalDetalles() {

    detailsModal.classList.remove('open');

    detailsModal.setAttribute('aria-hidden', 'true');

    document.body.style.overflow = '';

}


/* ============================================================
   FUNCIONES AUXILIARES
   ============================================================ */

function obtenerNombreCliente(venta) {

    /*
        En el esquema actual, idCliente es una referencia
        a la tabla usuario y no un campo de texto.

        Mientras no se implemente una relación específica
        para el nombre del cliente, se muestra un valor
        general o el identificador disponible.
    */
    if (venta.idCliente) {

        return `Cliente #${venta.idCliente}`;

    }


    return 'Cliente General';

}


/*
    Convierte un valor numérico o textual a número.
*/
function convertirNumero(valor) {

    const numero = Number(valor);

    return Number.isFinite(numero) ? numero : 0;

}


/*
    Formatea un número como moneda colombiana.
*/
function formatearMoneda(valor) {

    return new Intl.NumberFormat('es-CO', {

        style: 'currency',

        currency: 'COP',

        maximumFractionDigits: 0

    }).format(convertirNumero(valor));

}


/*
    Convierte una fecha recibida del backend
    a un formato legible para el usuario.
*/
function formatearFecha(fecha) {

    if (!fecha) {

        return 'Sin fecha';

    }


    const fechaObjeto = new Date(fecha);


    if (Number.isNaN(fechaObjeto.getTime())) {

        return String(fecha);

    }


    return fechaObjeto.toLocaleString('es-CO', {

        dateStyle: 'short',

        timeStyle: 'short'

    });

}


/*
    Devuelve la clase CSS correspondiente al estado.
*/
function obtenerClaseEstado(estado) {

    switch (estado) {

        case 'Pendiente':

            return 'status-pending';


        case 'Pagada':

            return 'status-paid';


        case 'Cancelada':

            return 'status-cancelled';


        default:

            return '';

    }

}


/*
    Muestra un mensaje temporal dentro de la interfaz.
*/
function mostrarMensaje(mensaje, tipo = 'info') {

    messageContainer.textContent = mensaje;

    messageContainer.className =
        `message-container visible ${tipo}`;


    /*
        Se elimina el mensaje después de unos segundos.
    */
    window.setTimeout(() => {

        messageContainer.className =
            'message-container';

        messageContainer.textContent = '';

    }, 5000);

}


/*
    Evita que valores recibidos desde el backend
    se interpreten como código HTML.
*/
function escaparHTML(valor) {

    return String(valor ?? '')

        .replaceAll('&', '&amp;')

        .replaceAll('<', '&lt;')

        .replaceAll('>', '&gt;')

        .replaceAll('"', '&quot;')

        .replaceAll("'", '&#039;');

}