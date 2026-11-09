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
    const raiz = document.documentElement;
    const consultaMovil = window.matchMedia("(max-width: 768px)");

    if (!sidebar || !botonSidebar) {
        raiz.classList.remove("sidebar-initial-collapsed");
        raiz.classList.add("sidebar-ready");
        return;
    }

    // En dispositivos móviles, el Sidebar siempre inicia colapsado.
    // En pantallas mayores se recupera la preferencia guardada.
    aplicarEstadoInicialSidebar(sidebar, consultaMovil.matches);

    // Habilitar las transiciones únicamente después de aplicar
    // el estado inicial para evitar la animación al cargar la vista.
    raiz.classList.remove("sidebar-initial-collapsed");
    raiz.classList.add("sidebar-ready");

    botonSidebar.addEventListener("click", () => {
        alternarSidebar(sidebar, consultaMovil.matches);
    });

    // Si cambia el tamaño de la ventana, aplicar el comportamiento
    // correspondiente al nuevo tipo de pantalla.
    const cambioDispositivo = (evento) => {
        if (evento.matches) {
            // Al entrar al tamaño móvil, contraer sin guardar el cambio.
            sidebar.classList.add("collapsed");
        } else {
            // Al volver a escritorio, recuperar la preferencia del usuario.
            restaurarEstadoSidebar(sidebar);
        }
    };

    if (typeof consultaMovil.addEventListener === "function") {
        consultaMovil.addEventListener("change", cambioDispositivo);
    } else {
        // Compatibilidad con navegadores que utilizan la API anterior.
        consultaMovil.addListener(cambioDispositivo);
    }
}

/**
 * Aplicar el estado inicial del Sidebar.
 *
 * @param {HTMLElement} sidebar
 * @param {boolean} esMovil
 */
function aplicarEstadoInicialSidebar(sidebar, esMovil) {

    if (esMovil) {
        sidebar.classList.add("collapsed");
        return;
    }

    restaurarEstadoSidebar(sidebar);
}

/**
 * Restaurar el estado almacenado del Sidebar.
 *
 * @param {HTMLElement} sidebar
 */
function restaurarEstadoSidebar(sidebar) {

    let estado = "expanded";

    try {
        estado = localStorage.getItem("sidebarState") || "expanded";
    } catch (error) {
        // Si LocalStorage no está disponible, se utiliza el estado expandido.
    }

    sidebar.classList.toggle("collapsed", estado === "collapsed");
}

/**
 * Alternar el estado del Sidebar.
 *
 * @param {HTMLElement} sidebar
 * @param {boolean} esMovil
 */
function alternarSidebar(sidebar, esMovil) {

    sidebar.classList.toggle("collapsed");

    // En móviles, el estado es temporal: al navegar o recargar
    // la vista, el Sidebar volverá a iniciar colapsado.
    if (!esMovil) {
        guardarEstadoSidebar(sidebar);
    }
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

    try {
        localStorage.setItem("sidebarState", estado);
    } catch (error) {
        // La interfaz continúa funcionando aunque no se pueda guardar la preferencia.
    }
}