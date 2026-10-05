/**
 * ==========================================================
 * Métricas de Ventas
 * ==========================================================
 *
 * Muestra los principales indicadores del módulo.
 *
 * Las métricas consideran únicamente las ventas
 * que se encuentran en estado Pagada.
 * ==========================================================
 */

import React from "react";


/* ==========================================================
  FORMATO DE MONEDA
  ========================================================== */

/**
 * Formatea valores monetarios en pesos colombianos.
 *
 * @param {number} valor
 * @returns {string}
 */
function formatoMoneda(valor) {

    return new Intl.NumberFormat(
        "es-CO",
        {
            style: "currency",
            currency: "COP",
            minimumFractionDigits: 0
        }
    ).format(
        Number(valor) || 0
    );
}


/* ==========================================================
  COMPONENTE
  ========================================================== */

function VentasMetricas({
    metricas = {}
}) {

    return (

        <section className="ventas-metricas">


            {/* ==================================================
                VENTAS PAGADAS
                ================================================== */}

            <article className="venta-metrica-card">

                <span>
                    Ventas pagadas
                </span>

                <strong>
                    {metricas.ventas_pagadas ?? 0}
                </strong>

            </article>


            {/* ==================================================
                PRODUCTOS VENDIDOS
                ================================================== */}

            <article className="venta-metrica-card">

                <span>
                    Productos vendidos
                </span>

                <strong>
                    {metricas.productos_vendidos ?? 0}
                </strong>

            </article>


            {/* ==================================================
                PRODUCTOS DIFERENTES
                ================================================== */}

            <article className="venta-metrica-card">

                <span>
                    Productos diferentes
                </span>

                <strong>
                    {metricas.productos_diferentes ?? 0}
                </strong>

            </article>


            {/* ==================================================
                INGRESOS TOTALES
                ================================================== */}

            <article className="venta-metrica-card">

                <span>
                    Ingresos totales
                </span>

                <strong>
                    {formatoMoneda(
                        metricas.ingresos_totales
                    )}
                </strong>

            </article>


            {/* ==================================================
                PROMEDIO DE VENTA
                ================================================== */}

            <article className="venta-metrica-card">

                <span>
                    Promedio por venta
                </span>

                <strong>
                    {formatoMoneda(
                        metricas.promedio_venta
                    )}
                </strong>

            </article>

        </section>
    );
}

export default VentasMetricas;