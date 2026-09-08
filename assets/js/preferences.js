"use strict";

/**==========================================================
 * preferences.js
 * ==========================================================
 *
 * Gestiona las preferencias de interfaz del usuario
 * almacenadas localmente mediante LocalStorage.
 *
 * Actualmente administra el estado expandido o colapsado
 * del menú lateral (Sidebar).
 * ==========================================================
 */

document.addEventListener("DOMContentLoaded", () => {
    inicializarSidebar();
});

/* ==========================================================
 * Sidebar
 * ========================================================== */

/**
 * Inicializa el comportamiento del Sidebar.
 */

function inicializarSidebar() {

    const sidebar = document.getElementById("sidebar");
    const botonSidebar = document.getElementById("toggleSidebar");

    if (!sidebar || !botonSidebar) {
        return;
    }

    restaurarEstadoSidebar(sidebar);

    botonSidebar.addEventListener("click", () => {
        alternarSidebar(sidebar);
    });

}

/**
 * Restaurar el estado almacenado del Sidebar.
 *
 * @param {HTMLElement} sidebar
 */

function restaurarEstadoSidebar(sidebar) {
    const estado = localStorage.getItem("sidebarState");
    if (estado === "collapsed") {
        sidebar.classList.add("collapsed");
    }
}

/**
 * Alternar el estado del Sidebar.
 *
 * @param {HTMLElement} sidebar
 */

function alternarSidebar(sidebar) {
    sidebar.classList.toggle("collapsed");
    guardarEstadoSidebar(sidebar);
}

/**
 * Guardar el estado actual del Sidebar.
 *
 * @param {HTMLElement} sidebar
 */
function guardarEstadoSidebar(sidebar) {
    const estado = sidebar.classList.contains("collapsed")
        ? "collapsed"
        : "expanded";
    localStorage.setItem("sidebarState", estado);
}