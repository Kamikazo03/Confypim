/* ==========================================================
   ENCABEZADO DE VENTAS
   ========================================================== */
/**
 * Encabezado de acciones del módulo de Ventas.
 *
 * El encabezado general de la página ya es generado
 * por el layout de Confypim.
 *
 * @param {Function} onCrearVenta - Abre el formulario de creación.
 * @returns {JSX.Element} Acción principal del módulo.
 */
function VentasHeader({
    onCrearVenta
}) {

    return (

        <div className="ventas-header-actions">

            {/* Acción principal */}
            <button
                type="button"
                onClick={onCrearVenta}
            >
                + Nueva venta
            </button>

        </div>
    );
}

export default VentasHeader;