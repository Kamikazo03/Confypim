import VentaFila from "./VentaFila";


/* ==========================================================
  TABLA DE VENTAS
  ========================================================== */

/**
 * Muestra el listado de ventas.
 *
 * @param {Array} ventas - Ventas consultadas.
 * @param {boolean} cargando - Indica si se están cargando datos.
 * @param {boolean} procesando - Indica si existe una operación activa.
 * @param {Function} onDetalle - Consulta el detalle.
 * @param {Function} onEditar - Edita una venta.
 * @param {Function} onPagar - Paga una venta.
 * @param {Function} onCancelar - Cancela una venta.
 * @returns {JSX.Element} Tabla de ventas.
 */
function VentasTabla({
    ventas = [],
    cargando = false,
    procesando = false,
    onDetalle,
    onEditar,
    onPagar,
    onCancelar
}) {

    /* ==========================================================
      ESTADO DE CARGA
      ========================================================== */

    if (cargando) {

        return (

            <div className="ventas-tabla-mensaje">

                Cargando ventas...

            </div>
        );
    }


    /* ==========================================================
      LISTADO VACÍO
      ========================================================== */

    if (!ventas.length) {

        return (

            <div className="ventas-tabla-mensaje">

                No se encontraron ventas.

            </div>
        );
    }


    return (

        <div className="ventas-tabla-container">

            <table className="ventas-tabla">

                <thead>

                    <tr>

                        <th>
                            Venta
                        </th>

                        <th>
                            Cliente
                        </th>

                        <th>
                            Fecha
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody>

                    {ventas.map((venta) => (

                        <VentaFila
                            key={
                                venta.id_venta
                            }
                            venta={venta}
                            procesando={procesando}
                            onDetalle={onDetalle}
                            onEditar={onEditar}
                            onPagar={onPagar}
                            onCancelar={onCancelar}
                        />

                    ))}

                </tbody>

            </table>

        </div>
    );
}

export default VentasTabla;