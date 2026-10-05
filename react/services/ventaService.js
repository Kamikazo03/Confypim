/**
 * ==========================================================
 * Servicio de Ventas - Confypim
 * ==========================================================
 *
 * Este archivo centraliza todas las peticiones realizadas
 * desde React hacia el backend del módulo de Ventas.
 *
 * El componente React no realiza directamente las
 * peticiones HTTP. Todas las comunicaciones pasan
 * por este servicio.
 * ==========================================================
 */


/* ==========================================================
  CONFIGURACIÓN DE LA API
  ========================================================== */

/**
 * Ruta principal del endpoint de Ventas.
 */
const API_URL =
    "/Confypim/ajax/venta.php";


/* ==========================================================
  FUNCIÓN GENERAL DE PETICIONES
  ========================================================== */

/**
 * Ejecuta una petición HTTP contra el backend.
 *
 * @param {string} url - Dirección del endpoint.
 * @param {Object} opciones - Opciones de fetch.
 * @returns {Promise<Object>} Respuesta procesada.
 */
async function solicitar(url, opciones = {}) {

    const respuesta =
        await fetch(
            url,
            {
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                ...opciones
            }
        );


    const datos =
        await respuesta.json();


    if (!respuesta.ok) {

        throw new Error(
            datos.message ||
            "No fue posible completar la solicitud."
        );
    }


    if (datos.success === false) {

        throw new Error(
            datos.message ||
            "El servidor rechazó la operación."
        );
    }


    return datos;
}


/* ==========================================================
  LISTAR VENTAS
  ========================================================== */

/**
 * Obtiene las ventas visibles.
 *
 * @param {string} estado - Estado seleccionado.
 * @param {string} busqueda - Texto de búsqueda.
 * @returns {Promise<Object>} Ventas encontradas.
 */
export async function listarVentas(
    estado = "",
    busqueda = ""
) {

    const parametros =
        new URLSearchParams();


    if (estado) {

        parametros.append(
            "estado",
            estado
        );
    }


    if (busqueda) {

        parametros.append(
            "busqueda",
            busqueda
        );
    }


    const url =
        parametros.toString()
            ? `${API_URL}?${parametros.toString()}`
            : API_URL;


    return solicitar(url);
}


/* ==========================================================
  OBTENER VENTA
  ========================================================== */

/**
 * Obtiene una venta completa mediante su identificador.
 *
 * @param {number} idVenta - Identificador de la venta.
 * @returns {Promise<Object>} Venta completa.
 */
export async function obtenerVenta(
    idVenta
) {

    return solicitar(
        `${API_URL}?id=${idVenta}`
    );
}


/* ==========================================================
  OBTENER MÉTRICAS
  ========================================================== */

/**
 * Obtiene las métricas del módulo de Ventas.
 *
 * @returns {Promise<Object>} Métricas.
 */
export async function obtenerMetricas() {

    return solicitar(
        `${API_URL}?accion=metricas`
    );
}


/* ==========================================================
  CREAR VENTA
  ========================================================== */

/**
 * Crea una nueva venta.
 *
 * La venta se registra inicialmente como Pendiente.
 *
 * @param {Object} datos - Información de la venta.
 * @returns {Promise<Object>} Resultado de la operación.
 */
export async function crearVenta(
    datos
) {

    return solicitar(
        API_URL,
        {
            method: "POST",

            body: JSON.stringify(
                datos
            )
        }
    );
}


/* ==========================================================
  ACTUALIZAR VENTA
  ========================================================== */

/**
 * Actualiza una venta pendiente.
 *
 * @param {number} idVenta - Identificador de la venta.
 * @param {Object} datos - Información actualizada.
 * @returns {Promise<Object>} Resultado de la operación.
 */
export async function actualizarVenta(
    idVenta,
    datos
) {

    return solicitar(
        `${API_URL}?id=${idVenta}`,
        {
            method: "PUT",

            body: JSON.stringify(
                datos
            )
        }
    );
}


/* ==========================================================
  PAGAR VENTA
  ========================================================== */

/**
 * Marca una venta como Pagada.
 *
 * El backend se encarga de validar y descontar
 * el stock correspondiente.
 *
 * @param {number} idVenta - Identificador de la venta.
 * @returns {Promise<Object>} Resultado de la operación.
 */
export async function pagarVenta(
    idVenta
) {

    return solicitar(
        `${API_URL}?accion=pagar&id=${idVenta}`,
        {
            method: "PATCH"
        }
    );
}


/* ==========================================================
  CANCELAR VENTA
  ========================================================== */

/**
 * Cancela una venta pendiente.
 *
 * La validación del estado se realiza en el backend.
 *
 * @param {number} idVenta - Identificador de la venta.
 * @returns {Promise<Object>} Resultado de la operación.
 */
export async function cancelarVenta(
    idVenta
) {

    return solicitar(
        `${API_URL}?accion=cancelar&id=${idVenta}`,
        {
            method: "PATCH"
        }
    );
}