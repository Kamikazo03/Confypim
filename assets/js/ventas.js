/**==========================================================
 * Ventas
 * ==========================================================
 * Gestiona las interacciones iniciales del módulo Ventas.
 *
 * En esta primera etapa solamente se realizan pruebas de
 * interfaz. La lógica de negocio y las peticiones al backend
 * serán incorporadas posteriormente.
 * ==========================================================
 */

document.addEventListener("DOMContentLoaded", () => {

    inicializar();

    /**
     * ==========================================================
     * Inicialización
     * ==========================================================
     */

    function inicializar() {

        registrarEventos();

    }


    /**
     * ==========================================================
     * Eventos
     * ==========================================================
     */

    function registrarEventos() {

        document
            .getElementById("btnNuevaVenta")
            .addEventListener(
                "click",
                nuevaVenta
            );


        document
            .querySelectorAll(".btn-ver-venta")
            .forEach((boton) => {

                boton.addEventListener(
                    "click",
                    () => {

                        verVenta(
                            boton.dataset.id
                        );

                    }
                );

            });


        document
            .querySelectorAll(".btn-editar-venta")
            .forEach((boton) => {

                boton.addEventListener(
                    "click",
                    () => {

                        editarVenta(
                            boton.dataset.id
                        );

                    }
                );

            });


        document
            .getElementById("buscarVenta")
            .addEventListener(
                "input",
                filtrarVentas
            );


        document
            .getElementById("estadoVenta")
            .addEventListener(
                "change",
                filtrarVentas
            );

    }


    /**
     * ==========================================================
     * Nueva venta
     * ==========================================================
     */

    function nuevaVenta() {

        mostrarToast(
            "El formulario de nueva venta se implementará en el siguiente módulo.",
            "info"
        );

    }


    /**
     * ==========================================================
     * Ver venta
     * ==========================================================
     */

    function verVenta(id) {

        mostrarToast(
            `Consulta temporal de la venta #${id}.`,
            "info"
        );

    }


    /**
     * ==========================================================
     * Editar venta
     * ==========================================================
     */

    function editarVenta(id) {

        mostrarToast(
            `Edición temporal de la venta #${id}.`,
            "info"
        );

    }


    /**
     * ==========================================================
     * Filtrar ventas
     * ==========================================================
     */

    function filtrarVentas() {

        const texto = document
            .getElementById("buscarVenta")
            .value
            .toLowerCase()
            .trim();

        const estado = document
            .getElementById("estadoVenta")
            .value;

        const filas = document
            .querySelectorAll("#tablaVentas tbody tr");


        filas.forEach((fila) => {

            const contenido =
                fila.textContent.toLowerCase();

            const estadoFila =
                fila
                    .querySelector(".badge")
                    ?.textContent
                    .toLowerCase()
                    .trim();


            const coincideTexto =
                contenido.includes(texto);

            const coincideEstado =
                estado === "todos" ||
                estadoFila === estado;


            fila.style.display =
                coincideTexto && coincideEstado
                    ? ""
                    : "none";

        });

    }

});