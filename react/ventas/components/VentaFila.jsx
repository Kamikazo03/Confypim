/* ==========================================================
  FILA DE VENTA
  ========================================================== */

/**
 * Representa una venta individual dentro de la tabla.
 *
 * @param {Object} venta - Información de la venta.
 * @param {boolean} procesando - Indica si hay una operación activa.
 * @param {Function} onDetalle - Consulta el detalle.
 * @param {Function} onEditar - Abre la edición.
 * @param {Function} onPagar - Marca como pagada.
 * @param {Function} onCancelar - Cancela la venta.
 * @returns {JSX.Element} Fila de la tabla.
 */
function VentaFila({
    venta,
    procesando = false,
    onDetalle,
    onEditar,
    onPagar,
    onCancelar
}) {

    /* ==========================================================
      DATOS DE LA VENTA
      ========================================================== */

    const idVenta =
        venta.id_venta;


    const cliente =
        venta.cliente ??
        venta.nombre_cliente ??
        "Cliente General";


    const estado =
        venta.estado ??
        "";


    const fecha =
        venta.fecha_venta ??
        venta.fecha ??
        "";


    const total =
        Number(
            venta.total ?? 0
        );


    /* ==========================================================
      FORMATO DE FECHA
      ========================================================== */

    /**
     * Formatea la fecha de la venta.
     *
     * @param {string} valor - Fecha recibida.
     * @returns {string} Fecha formateada.
     */
    const formatearFecha = (valor) => {

        if (!valor) {
            return "—";
        }


        const fechaObjeto =
            new Date(
                valor.replace(" ", "T")
            );


        if (Number.isNaN(
            fechaObjeto.getTime()
        )) {

            return valor;
        }


        return fechaObjeto.toLocaleDateString(
            "es-CO"
        );
    };


    /* ==========================================================
      FORMATO MONETARIO
      ========================================================== */

    /**
     * Formatea el total de la venta.
     *
     * @param {number} valor - Total de la venta.
     * @returns {string} Valor monetario.
     */
    const formatearMoneda = (valor) => {

        return new Intl.NumberFormat(
            "es-CO",
            {
                style: "currency",
                currency: "COP",
                minimumFractionDigits: 0
            }
        ).format(valor);
    };


    /* ==========================================================
      ESTADO CSS
      ========================================================== */

    const estadoClase =
        String(estado)
            .toLowerCase()
            .replace(" ", "-");


    return (

        <tr>

            {/* Identificador */}
            <td>
                #{idVenta}
            </td>


            {/* Cliente */}
            <td>
                {cliente}
            </td>


            {/* Fecha */}
            <td>
                {formatearFecha(fecha)}
            </td>


            {/* Total */}
            <td>
                {formatearMoneda(total)}
            </td>


            {/* Estado */}
            <td>

                <span
                    className={
                        `venta-estado ${estadoClase}`
                    }
                >
                    {estado}
                </span>

            </td>


            {/* Acciones */}
            <td>

                <div className="venta-fila-acciones">

                    {/* Ver detalle */}
                    <button
                        type="button"
                        className="btn-accion"
                        onClick={() =>
                            onDetalle(idVenta)
                        }
                        disabled={procesando}
                    >
                        Ver
                    </button>


                    {/* Editar solamente Pendiente */}
                    {estado.toLowerCase() ===
                        "pendiente" && (

                        <button
                            type="button"
                            className="btn-accion"
                            onClick={() =>
                                onEditar(idVenta)
                            }
                            disabled={procesando}
                        >
                            Editar
                        </button>

                    )}


                    {/* Pagar solamente Pendiente */}
                    {estado.toLowerCase() ===
                        "pendiente" && (

                        <button
                            type="button"
                            className="btn-accion"
                            onClick={() =>
                                onPagar(idVenta)
                            }
                            disabled={procesando}
                        >
                            Pagar
                        </button>

                    )}


                    {/* Cancelar solamente Pendiente */}
                    {estado.toLowerCase() ===
                        "pendiente" && (

                        <button
                            type="button"
                            className="btn-accion"
                            onClick={() =>
                                onCancelar(idVenta)
                            }
                            disabled={procesando}
                        >
                            Cancelar
                        </button>

                    )}

                </div>

            </td>

        </tr>
    );
}

export default VentaFila;