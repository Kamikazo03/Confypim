/**
 * ==========================================================
 * Módulo de Ventas - Confypim
 * ==========================================================
 *
 * Interfaz principal desarrollada con React.
 *
 * Funcionalidades:
 * - Consulta de ventas.
 * - Visualización de métricas.
 * - Búsqueda y filtros.
 * - Consulta de detalles.
 * - Pago y cancelación de ventas pendientes.
 *
 * La comunicación con el backend se realiza mediante
 * el servicio ventaService.js.
 * ==========================================================
 */

import React from "react";
import ReactDOM from "react-dom/client";

import VentasApp from "./ventas/components/VentasApp";


/* ==========================================================
  INICIALIZACIÓN DEL MÓDULO DE VENTAS
  ========================================================== */

/**
 * Busca el elemento HTML donde se montará React.
 */
const contenedor =
    document.getElementById(
        "ventas-react-root"
    );


/* ==========================================================
  VALIDAR CONTENEDOR
  ========================================================== */

if (contenedor) {

    /**
     * Crear la raíz de React.
     */
    const root =
        ReactDOM.createRoot(
            contenedor
        );


    /**
     * Renderizar el módulo de Ventas.
     */
    root.render(
        <React.StrictMode>

            <VentasApp />

        </React.StrictMode>
    );
}