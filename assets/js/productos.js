/**==========================================================
 * productos.js
 * ==========================================================
 * Gestiona todas las operaciones del módulo Productos:
 * - Consultar productos
 * - Registrar productos
 * - Editar productos
 * - Desactivar productos
 * ==========================================================
 */

let formProducto;
let productoEditando = null;

document.addEventListener("DOMContentLoaded", () => {

    inicializar();

    /**
     * ==========================================================
     * Inicialización
     * ==========================================================
     */

    function inicializar() {
        formProducto = document.getElementById("formProducto");
        registrarEventos();
        cargarProductos();
    }

    /**
     * ==========================================================
     * Eventos
     * ==========================================================
     */

    function registrarEventos() {
        formProducto.addEventListener(
            "submit",
            guardarProducto
        );
        document
            .getElementById("btnCancelar")
            .addEventListener(
                "click",
                restablecerFormulario
            );
    }

    /**
     * ==========================================================
     * Consultas
     * ==========================================================
     */

    async function cargarProductos() {
        try {
            const productos = await api.get(
                "ajax/producto.php"
            );
            renderizarTabla(productos);
        } catch (error) {
            console.error(error);
            mostrarToast(
                "No fue posible cargar los productos.",
                "error"
            );
        }
    }

    /**
     * ==========================================================
     * Tabla
     * ==========================================================
     */

    function renderizarTabla(productos) {
        const contenedor =
            document.getElementById("tablaProductos");
        if (productos.length === 0) {
            contenedor.innerHTML = `
                <p class="empty-table">
                    No existen productos registrados.
                </p>
            `;
            return;
        }

        let html = `
            <table class="table-products">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;

        productos.forEach((producto) => {
            const claseEstado =
                producto.estado
                    .toLowerCase()
                    .replace(/\s+/g, "-");
            html += `
                <tr>
                    <td>${producto.nombre}</td>
                    <td>
                        $${Number(
                            producto.precio_unitario
                        ).toLocaleString("es-CO")}
                    </td>
                    <td>
                        ${producto.stock_disponible}
                    </td>
                    <td>
                        <span class="badge ${claseEstado}">
                            ${producto.estado}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <button
                                class="btn-action edit"
                                data-id="${producto.id_producto}"
                                title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button
                                class="btn-action delete"
                                data-id="${producto.id_producto}"
                                title="Eliminar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        html += `
                </tbody>

            </table>
        `;

        contenedor.innerHTML = html;

        registrarEventosTabla(contenedor);
    }

    /**
     * ==========================================================
     * Eventos de la tabla
     * ==========================================================
     */

    function registrarEventosTabla(contenedor) {
        contenedor
            .querySelectorAll(".delete")
            .forEach((boton) => {
                boton.addEventListener("click", () => {
                    eliminarProducto(
                        boton.dataset.id
                    );
                });
            });

        contenedor
            .querySelectorAll(".edit")
            .forEach((boton) => {
                boton.addEventListener("click", () => {
                    editarProducto(
                        boton.dataset.id
                    );
                });
            });
    }

    /**
     * ==========================================================
     * Eliminar producto
     * ==========================================================
     */

    async function eliminarProducto(id) {
        if (
            !confirm(
                "¿Desea desactivar este producto?"
            )
        ) {
            return;
        }
        try {
            const respuesta = await api.delete(
                `ajax/producto.php?id=${id}`
            );
            if (respuesta.success) {
                mostrarToast(
                    respuesta.message,
                    "success"
                );
                await cargarProductos();
                return;
            }
            mostrarToast(
                respuesta.message,
                "error"
            );
        } catch (error) {
            console.error(error);
            mostrarToast(
                "No fue posible desactivar el producto.",
                "error"
            );
        }
    }

    /**
     * ==========================================================
     * Editar producto
     * ==========================================================
     */

    async function editarProducto(id) {
        try {
            const respuesta = await api.get(
                `ajax/producto.php?id=${id}`
            );
            if (respuesta.success) {
                activarModoEdicion(
                    respuesta.data
                );
                return;
            }
            mostrarToast(
                respuesta.message,
                "error"
            );
        } catch (error) {
            console.error(error);
            mostrarToast(
                "No fue posible obtener la información del producto.",
                "error"
            );
        }
    }

    /**
     * ==========================================================
     * Modo edición
     * ==========================================================
     */

    function activarModoEdicion(producto) {

        productoEditando = producto.id_producto;

        document.getElementById("idProducto").value =
            producto.id_producto;

        document.getElementById("nombre").value =
            producto.nombre;

        document.getElementById("descripcion").value =
            producto.descripcion;

        document.getElementById("precio").value =
            producto.precio_unitario;

        document.getElementById("stock").value =
            producto.stock_disponible;

        document.getElementById("tituloFormulario").textContent =
            "✏️ Editar producto";

        document.getElementById("descripcionFormulario").textContent =
            "Modifique la información del producto.";

        document.getElementById("btnGuardar").textContent =
            "Actualizar producto";

        document.getElementById("btnCancelar").textContent =
            "Cancelar edición";

        document
            .querySelector(".form-card")
            .classList.add("editing");
    }

    /**
     * ==========================================================
     * Restablecer formulario
     * ==========================================================
     */

    function restablecerFormulario() {

        productoEditando = null;

        formProducto.reset();

        document.getElementById("idProducto").value = "";

        document.getElementById("tituloFormulario").textContent =
            "🧁 Registrar producto";

        document.getElementById("descripcionFormulario").textContent =
            "Complete la información del producto para agregarlo al inventario.";

        document.getElementById("btnGuardar").textContent =
            "Guardar Producto";

        document.getElementById("btnCancelar").textContent =
            "Cancelar";

        document
            .querySelector(".form-card")
            .classList.remove("editing");

        document
            .getElementById("nombre")
            .focus();
    }

    /**
     * ==========================================================
     * Guardar producto
     * ==========================================================
     */

    async function guardarProducto(event) {
        event.preventDefault();

        const producto = {

            id_producto: productoEditando,

            nombre:
                document
                    .getElementById("nombre")
                    .value
                    .trim(),

            descripcion:
                document
                    .getElementById("descripcion")
                    .value
                    .trim(),

            precio_unitario: Number(
                document
                    .getElementById("precio")
                    .value
            ),

            stock_disponible: Number(
                document
                    .getElementById("stock")
                    .value
            )
        };

        try {

            let respuesta;

            if (productoEditando) {
                respuesta = await api.put(
                    "ajax/producto.php",
                    producto
                );

            } else {
                respuesta = await api.post(
                    "ajax/producto.php",
                    producto
                );
            }

            if (respuesta.success) {
                mostrarToast(
                    respuesta.message,
                    "success"
                );

                restablecerFormulario();
                await cargarProductos();
                return;
            }

            mostrarToast(
                respuesta.message,
                "error"
            );

        } catch (error) {
            console.error(error);
            mostrarToast(
                "No fue posible conectar con el servidor.",
                "error"
            );
        }
    }

});