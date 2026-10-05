/* ==========================================================
  DETALLE DE VENTA
  ========================================================== */

/**
 * Muestra la información detallada de una venta.
 *
 * @param {Object|null} venta - Venta seleccionada.
 * @param {boolean} cargando - Estado de carga.
 * @param {Function} onCerrar - Cierra el detalle.
 * @returns {JSX.Element} Ventana de detalle.
 */
function VentaDetalle({
    venta,
    cargando = false,
    onCerrar
}) {

    /* ==========================================================
      ESTADO DE CARGA
      ========================================================== */

    if (cargando) {

        return (

            <div className="venta-modal-overlay">

                <div className="venta-modal">

                    <div className="ventas-tabla-mensaje">

                        Cargando detalle...

                    </div>

                </div>

            </div>
        );
    }


    /* ==========================================================
      VALIDAR INFORMACIÓN
      ========================================================== */

    if (!venta) {

        return null;
    }


    /* ==========================================================
      DATOS
      ========================================================== */

    const detalles =
        venta.detalles ??
        venta.detalle ??
        [];


    const cliente =
        venta.cliente ??
        venta.nombre_cliente ??
        "Cliente General";


    const total =
        Number(
            venta.total ?? 0
        );


    /* ==========================================================
      FORMATO MONETARIO
      ========================================================== */

    /**
     * Formatea valores monetarios.
     *
     * @param {number} valor - Valor monetario.
     * @returns {string} Valor formateado.
     */
    const formatearMoneda = (valor) => {

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
    };


    return (

        <div className="venta-modal-overlay">

            <div className="venta-modal">

                {/* Encabezado */}
                <div className="venta-formulario-header">

                    <div>

                        <h2>
                            Venta #{venta.id_venta}
                        </h2>

                        <p>
                            Cliente: {cliente}
                        </p>

                    </div>


                    <button
                        type="button"
                        className="btn-cerrar"
                        onClick={onCerrar}
                    >
                        ×
                    </button>

                </div>


                {/* Contenido */}
                <div className="venta-formulario-body">

                    <div>

                        <strong>
                            Estado:
                        </strong>{" "}

                        {venta.estado}

                    </div>


                    {/* Productos */}
                    <div className="venta-productos">

                        <h3>
                            Productos
                        </h3>


                        <div className="venta-productos-lista">

                            {detalles.map(
                                (detalle, indice) => {

                                    const cantidad =
                                        Number(
                                            detalle.cantidad ?? 0
                                        );


                                    const precio =
                                        Number(
                                            detalle.precio_unitario ?? 0
                                        );


                                    const subtotal =
                                        Number(
                                            detalle.subtotal ??
                                            cantidad * precio
                                        );


                                    return (

                                        <div
                                            className="venta-producto-row"
                                            key={
                                                detalle.id_detalle ??
                                                indice
                                            }
                                        >

                                            <div>

                                                <strong>
                                                    {
                                                        detalle.producto ??
                                                        detalle.nombre_producto ??
                                                        `Producto #${detalle.id_producto}`
                                                    }
                                                </strong>

                                            </div>


                                            <div>

                                                <span>
                                                    Precio:
                                                </span>{" "}

                                                {formatearMoneda(
                                                    precio
                                                )}

                                            </div>


                                            <div>

                                                <span>
                                                    Cantidad:
                                                </span>{" "}

                                                {cantidad}

                                            </div>


                                            <div>

                                                <span>
                                                    Subtotal:
                                                </span>{" "}

                                                {formatearMoneda(
                                                    subtotal
                                                )}

                                            </div>

                                        </div>
                                    );
                                }
                            )}

                        </div>

                    </div>


                    {/* Total */}
                    <div className="venta-formulario-total">

                        <span>
                            Total
                        </span>

                        <strong>
                            {formatearMoneda(total)}
                        </strong>

                    </div>

                </div>

            </div>

        </div>
    );
}

export default VentaDetalle;