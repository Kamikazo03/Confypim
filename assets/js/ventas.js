/**==========================================================
 * Ventas
 * ==========================================================
 * Gestiona la interacción de la interfaz de ventas.
 * Permite:
 * - Buscar productos disponibles
 * - Agregar productos a la venta
 * - Modificar cantidades
 * - Validar el stock disponible
 * - Eliminar productos del carrito
 * - Calcular cantidades y totales
 * - Cancelar la venta actual
 * - Simular el registro de una venta
 * ==========================================================
 */

document.addEventListener("DOMContentLoaded", () => {

    /* ==========================================================
     * ELEMENTOS DEL DOM
     * ========================================================== */

    const buscarProducto = document.getElementById(
        "buscarProductoVenta"
    );

    const productosVenta = document.getElementById(
        "productosVenta"
    );

    const carritoProductos = document.getElementById(
        "carritoProductos"
    );

    const totalProductos = document.getElementById(
        "totalProductosVenta"
    );

    const totalVenta = document.getElementById(
        "totalVenta"
    );

    const btnCancelarVenta = document.getElementById(
        "btnCancelarVenta"
    );

    const btnRegistrarVenta = document.getElementById(
        "btnRegistrarVenta"
    );

    /* ==========================================================
     * ESTADO DE LA VENTA
     * ========================================================== */

    const carrito = [];


    /* ==========================================================
     * EVENTOS DE PRODUCTOS
     * ========================================================== */

    if (productosVenta) {

        productosVenta.addEventListener("click", (evento) => {

            const boton = evento.target.closest(
                "[data-agregar-producto]"
            );

            if (!boton) {
                return;
            }

            const tarjeta = boton.closest(
                ".producto-venta-card"
            );

            if (!tarjeta) {
                return;
            }

            agregarProducto(tarjeta);

        });

    }


    /* ==========================================================
     * BÚSQUEDA DE PRODUCTOS
     * ========================================================== */

    if (buscarProducto) {

        buscarProducto.addEventListener("input", () => {

            const texto = buscarProducto.value
                .trim()
                .toLowerCase();

            const productos = productosVenta.querySelectorAll(
                ".producto-venta-card"
            );

            productos.forEach((producto) => {

                const nombre = producto.dataset.nombre
                    .toLowerCase();

                const descripcion = producto.querySelector(
                    ".producto-venta-info p"
                );

                const textoDescripcion = descripcion
                    ? descripcion.textContent.toLowerCase()
                    : "";

                const coincide =
                    nombre.includes(texto) ||
                    textoDescripcion.includes(texto);

                producto.style.display = coincide
                    ? ""
                    : "none";

            });

        });

    }


    /* ==========================================================
     * EVENTOS DEL CARRITO
     * ========================================================== */

    if (carritoProductos) {

        carritoProductos.addEventListener(
            "click",
            manejarAccionesCarrito
        );

    }


    /* ==========================================================
     * CANCELAR VENTA
     * ========================================================== */

    if (btnCancelarVenta) {

        btnCancelarVenta.addEventListener("click", () => {

            if (carrito.length === 0) {

                mostrarNotificacion(
                    "No hay productos para cancelar.",
                    "info"
                );

                return;
            }

            carrito.length = 0;

            actualizarCarrito();

            mostrarNotificacion(
                "La venta actual fue cancelada.",
                "info"
            );

        });

    }


    /* ==========================================================
     * REGISTRAR VENTA
     * ========================================================== */

    if (btnRegistrarVenta) {

        btnRegistrarVenta.addEventListener("click", () => {

            if (carrito.length === 0) {

                mostrarNotificacion(
                    "Agrega al menos un producto a la venta.",
                    "warning"
                );

                return;
            }

            mostrarNotificacion(
                "La venta fue registrada correctamente.",
                "success"
            );

            carrito.length = 0;

            actualizarCarrito();

        });

    }


    /* ==========================================================
     * FUNCIONES PRINCIPALES
     * ========================================================== */

    /**
     * Agrega un producto al carrito de la venta.
     *
     * @param {HTMLElement} tarjeta
     * @returns {void}
     */
    function agregarProducto(tarjeta) {

        const id = Number(tarjeta.dataset.id);

        const nombre = tarjeta.dataset.nombre;

        const precio = Number(tarjeta.dataset.precio);

        const stock = Number(tarjeta.dataset.stock);

        if (stock <= 0) {

            mostrarNotificacion(
                "Este producto no tiene unidades disponibles.",
                "warning"
            );

            return;
        }

        const productoExistente = carrito.find(
            (producto) => producto.id === id
        );

        if (productoExistente) {

            if (
                productoExistente.cantidad >=
                productoExistente.stock
            ) {

                mostrarNotificacion(
                    "No puedes agregar más unidades de este producto.",
                    "warning"
                );

                return;
            }

            productoExistente.cantidad += 1;

        } else {

            carrito.push({
                id,
                nombre,
                precio,
                stock,
                cantidad: 1
            });

        }

        actualizarCarrito();

        mostrarNotificacion(
            `${nombre} fue agregado a la venta.`,
            "success"
        );

    }


    /**
     * Gestiona las acciones realizadas sobre los productos
     * seleccionados dentro del carrito.
     *
     * @param {MouseEvent} evento
     * @returns {void}
     */
    function manejarAccionesCarrito(evento) {

        const boton = evento.target.closest("button");

        if (!boton) {
            return;
        }

        const id = Number(boton.dataset.id);

        if (!id) {
            return;
        }

        if (boton.matches("[data-aumentar]")) {

            aumentarCantidad(id);

            return;
        }

        if (boton.matches("[data-disminuir]")) {

            disminuirCantidad(id);

            return;
        }

        if (boton.matches("[data-eliminar]")) {

            eliminarProducto(id);

        }

    }


    /**
     * Aumenta la cantidad de un producto dentro del carrito.
     *
     * @param {number} id
     * @returns {void}
     */
    function aumentarCantidad(id) {

        const producto = carrito.find(
            (item) => item.id === id
        );

        if (!producto) {
            return;
        }

        if (producto.cantidad >= producto.stock) {

            mostrarNotificacion(
                `Solo hay ${producto.stock} unidades disponibles.`,
                "warning"
            );

            return;
        }

        producto.cantidad += 1;

        actualizarCarrito();

    }


    /**
     * Disminuye la cantidad de un producto dentro del carrito.
     *
     * @param {number} id
     * @returns {void}
     */
    function disminuirCantidad(id) {

        const indice = carrito.findIndex(
            (producto) => producto.id === id
        );

        if (indice === -1) {
            return;
        }

        if (carrito[indice].cantidad > 1) {

            carrito[indice].cantidad -= 1;

        } else {

            carrito.splice(indice, 1);

        }

        actualizarCarrito();

    }


    /**
     * Elimina completamente un producto del carrito.
     *
     * @param {number} id
     * @returns {void}
     */
    function eliminarProducto(id) {

        const indice = carrito.findIndex(
            (producto) => producto.id === id
        );

        if (indice === -1) {
            return;
        }

        const nombre = carrito[indice].nombre;

        carrito.splice(indice, 1);

        actualizarCarrito();

        mostrarNotificacion(
            `${nombre} fue eliminado de la venta.`,
            "info"
        );

    }


    /**
     * Actualiza visualmente el contenido del carrito.
     *
     * @returns {void}
     */
    function actualizarCarrito() {

        renderizarCarrito();

        actualizarResumen();

        actualizarEstadoBoton();

    }


    /**
     * Renderiza los productos agregados a la venta.
     *
     * @returns {void}
     */
    function renderizarCarrito() {

        if (!carritoProductos) {
            return;
        }

        if (carrito.length === 0) {

            carritoProductos.innerHTML = `
                <div class="carrito-empty">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <p>
                        Aún no has agregado productos
                        a la venta.
                    </p>
                </div>
            `;

            return;
        }

        carritoProductos.innerHTML = carrito
            .map((producto) => {

                const subtotal =
                    producto.precio *
                    producto.cantidad;

                return `
                    <article class="carrito-item">

                        <div class="carrito-item-info">

                            <h3>
                                ${escaparHTML(producto.nombre)}
                            </h3>

                            <span>
                                ${formatearMoneda(producto.precio)}
                                c/u
                            </span>

                        </div>

                        <div class="carrito-item-actions">

                            <div class="cantidad-control">

                                <button
                                    type="button"
                                    data-disminuir
                                    data-id="${producto.id}"
                                    aria-label="Disminuir cantidad">

                                    <i class="fa-solid fa-minus"></i>

                                </button>

                                <span>
                                    ${producto.cantidad}
                                </span>

                                <button
                                    type="button"
                                    data-aumentar
                                    data-id="${producto.id}"
                                    aria-label="Aumentar cantidad">

                                    <i class="fa-solid fa-plus"></i>

                                </button>

                            </div>

                            <strong class="carrito-subtotal">

                                ${formatearMoneda(subtotal)}

                            </strong>

                            <button
                                type="button"
                                class="btn-eliminar-carrito"
                                data-eliminar
                                data-id="${producto.id}"
                                aria-label="Eliminar producto">

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        </div>

                    </article>
                `;

            })
            .join("");

    }


    /**
     * Actualiza la información económica de la venta.
     *
     * @returns {void}
     */
    function actualizarResumen() {

        const cantidad = carrito.reduce(
            (total, producto) => {

                return total + producto.cantidad;

            },
            0
        );

        const total = carrito.reduce(
            (acumulado, producto) => {

                return (
                    acumulado +
                    producto.precio * producto.cantidad
                );

            },
            0
        );

        if (totalProductos) {

            totalProductos.textContent = cantidad;

        }

        if (totalVenta) {

            totalVenta.textContent =
                formatearMoneda(total);

        }

    }


    /**
     * Actualiza el estado del botón de registro de venta.
     *
     * @returns {void}
     */
    function actualizarEstadoBoton() {

        if (!btnRegistrarVenta) {
            return;
        }

        btnRegistrarVenta.disabled =
            carrito.length === 0;

    }


    /* ==========================================================
     * FUNCIONES AUXILIARES
     * ========================================================== */

    /**
     * Formatea un valor numérico como moneda colombiana.
     *
     * @param {number} valor
     * @returns {string}
     */
    function formatearMoneda(valor) {

        return new Intl.NumberFormat(
            "es-CO",
            {
                style: "currency",
                currency: "COP",
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }
        ).format(valor);

    }


    /**
     * Escapa caracteres HTML para evitar la inserción de
     * contenido no seguro dentro de la interfaz.
     *
     * @param {string} texto
     * @returns {string}
     */
    function escaparHTML(texto) {

        const elemento =
            document.createElement("div");

        elemento.textContent = texto;

        return elemento.innerHTML;

    }


    /**
     * Muestra una notificación utilizando el sistema global
     * de notificaciones cuando se encuentre disponible.
     *
     * @param {string} mensaje
     * @param {string} tipo
     * @returns {void}
     */
    function mostrarNotificacion(mensaje, tipo = "info") {

        if (
            typeof showNotification === "function"
        ) {

            showNotification(mensaje, tipo);

            return;
        }

        console.log(
            `[${tipo.toUpperCase()}] ${mensaje}`
        );

    }

});