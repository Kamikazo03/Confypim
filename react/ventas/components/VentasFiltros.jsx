/**
 * ==========================================================
 * Filtros de Ventas
 * ==========================================================
 *
 * Componente encargado de controlar los filtros utilizados
 * en el listado de Ventas.
 *
 * Permite filtrar por:
 * - Cliente o número de venta.
 * - Estado de la venta.
 *
 * Estados disponibles:
 * - Todos.
 * - Pendiente.
 * - Pagada.
 * - Cancelada.
 * ==========================================================
 */


/* ==========================================================
  COMPONENTE
  ========================================================== */

/**
 * Componente de filtros del listado de Ventas.
 *
 * @param {string} estado - Estado seleccionado.
 * @param {string} busqueda - Texto de búsqueda.
 * @param {Function} onEstadoChange - Actualiza el estado.
 * @param {Function} onBusquedaChange - Actualiza la búsqueda.
 * @returns {JSX.Element} Controles de filtrado.
 */
function VentasFiltros({
    estado,
    busqueda,
    onEstadoChange,
    onBusquedaChange
}) {

    return (

        <div className="ventas-filtros">


            {/* ==================================================
                BÚSQUEDA
                ================================================== */}

            <div className="form-group">

                <label htmlFor="venta-busqueda">

                    Buscar

                </label>

                <input
                    id="venta-busqueda"
                    type="search"
                    placeholder="Buscar por cliente o venta..."
                    value={busqueda}
                    onChange={(evento) =>
                        onBusquedaChange(
                            evento.target.value
                        )
                    }
                />

            </div>


            {/* ==================================================
                ESTADO
                ================================================== */}

            <div className="form-group">

                <label htmlFor="venta-estado">

                    Estado

                </label>

                <select
                    id="venta-estado"
                    value={estado}
                    onChange={(evento) =>
                        onEstadoChange(
                            evento.target.value
                        )
                    }
                >

                    {/* Todas las ventas visibles */}
                    <option value="">
                        Todos
                    </option>


                    {/* Ventas pendientes */}
                    <option value="Pendiente">
                        Pendiente
                    </option>


                    {/* Ventas pagadas */}
                    <option value="Pagada">
                        Pagada
                    </option>


                    {/* Ventas canceladas */}
                    <option value="Cancelada">
                        Cancelada
                    </option>

                </select>

            </div>

        </div>
    );
}

export default VentasFiltros;