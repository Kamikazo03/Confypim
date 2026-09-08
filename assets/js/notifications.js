"use strict";

/**==========================================================
 * notifications.js
 * ==========================================================
 *
 * Gestiona las notificaciones visuales del sistema mediante
 * mensajes tipo Toast.
 *
 * Permite mostrar mensajes de éxito, error, información y
 * advertencia en cualquier módulo de Confipym.
 * ==========================================================
 */

/**==========================================================
 * Mostrar notificación Toast
 * ==========================================================
 *
 * @param {string} mensaje
 * @param {string} tipo
 */

function mostrarToast(mensaje, tipo = "success") {
    const contenedor = obtenerContenedorToast();
    const iconos = {
        success: "fa-solid fa-circle-check",
        error: "fa-solid fa-circle-xmark",
        info: "fa-solid fa-circle-info",
        warning: "fa-solid fa-triangle-exclamation"
    };

    const toast = document.createElement("div");
    toast.className = `toast ${tipo}`;

    toast.innerHTML = `
        <i class="${iconos[tipo]}"></i>
        <div class="toast-message">
            ${mensaje}
        </div>
    `;

    contenedor.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3500);
}

/**==========================================================
 * Obtener contenedor Toast
 * ==========================================================
 *
 * Crea el contenedor si aún no existe.
 *
 * @returns {HTMLElement}
 */

function obtenerContenedorToast() {

    let contenedor = document.querySelector(".toast-container");
    if (contenedor) {
        return contenedor;
    }

    contenedor = document.createElement("div");
    contenedor.className = "toast-container";
    document.body.appendChild(contenedor);
    return contenedor;
}