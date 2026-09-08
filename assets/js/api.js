/**==========================================================
 * API Helper
 * ==========================================================
 *
 * Centraliza todas las peticiones HTTP realizadas por el
 * sistema hacia el backend.
 *
 * Proporciona métodos para realizar solicitudes GET, POST,
 * PUT y DELETE utilizando la API Fetch.
 *
 * Cualquier cambio relacionado con autenticación, cabeceras
 * o manejo global de errores deberá realizarse únicamente
 * en este archivo.
 * ==========================================================
 */

const api = {

    /* ==========================================================
     * MÉTODOS PÚBLICOS
     * ========================================================== */

    /**
     * Realizar una petición GET.
     *
     * @param {string} url
     * @returns {Promise<Object>}
     */

    async get(url) {
        return this.request(url);
    },

    /**
     * Realizar una petición POST.
     *
     * @param {string} url
     * @param {Object} datos
     * @returns {Promise<Object>}
     */

    async post(url, datos) {
        return this.request(url, {
            method: "POST",
            body: JSON.stringify(datos)
        });
    },

    /**
     * Realizar una petición PUT.
     *
     * @param {string} url
     * @param {Object} datos
     * @returns {Promise<Object>}
     */

    async put(url, datos) {
        return this.request(url, {
            method: "PUT",
            body: JSON.stringify(datos)
        });
    },

    /**
     * Realizar una petición DELETE.
     *
     * @param {string} url
     * @returns {Promise<Object>}
     */

    async delete(url) {
        return this.request(url, {
            method: "DELETE"
        });
    },

    /* ==========================================================
     * MÉTODOS PRIVADOS
     * ========================================================== */

    /**
     * Ejecuta la petición HTTP.
     *
     * @param {string} url
     * @param {Object} opciones
     * @returns {Promise<Object>}
     */

    async request(url, opciones = {}) {
        const configuracion = {
            headers: {
                "Content-Type": "application/json"
            },
            ...opciones
        };
        const respuesta = await fetch(url, configuracion);
        if (!respuesta.ok) {
            throw new Error(
                `Error HTTP ${respuesta.status}: ${respuesta.statusText}`
            );
        }
        return await respuesta.json();
    }

};