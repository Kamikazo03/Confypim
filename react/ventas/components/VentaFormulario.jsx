/**
 * ==========================================================
 * Formulario de Ventas
 * ==========================================================
 *
 * Permite registrar una nueva venta o modificar una venta
 * que todavía se encuentre en estado Pendiente.
 *
 * Características:
 * - Cliente General.
 * - Mínimo un producto.
 * - Múltiples productos.
 * - Cantidades editables.
 * - Precio obtenido desde los productos.
 * - Total calculado automáticamente.
 * ==========================================================
 */

import React, {
    useEffect,
    useMemo,
    useState
} from "react";

import {
    crearVenta,
    actualizarVenta
} from "../../services/ventaService";


/* ==========================================================
  COMPONENTE
  ========================================================== */

function VentaFormulario({
    venta,
    productos,
    onGuardar,
    onCancelar
}) {

    /* ==========================================================
      CONFIGURACIÓN DEL MODO
      ========================================================== */

    const modoEdicion =
        Boolean(venta);


    /* ==========================================================
      ESTADOS
      ========================================================== */

    const [detalles, setDetalles] =
        useState([
            {
                id_producto: "",
                cantidad: 1
            }
        ]);

    const [error, setError] =
        useState("");

    const [guardando, setGuardando] =
        useState(false);


    /* ==========================================================
      CARGAR VENTA EN MODO EDICIÓN
      ========================================================== */

    useEffect(() => {

        if (!venta) {

            setDetalles([
                {
                    id_producto: "",
                    cantidad: 1
                }
            ]);

            return;
        }

        const detallesVenta =
            venta.detalles ||
            venta.detalle ||
            [];

        if (detallesVenta.length === 0) {

            setDetalles([
                {
                    id_producto: "",
                    cantidad: 1
                }
            ]);

            return;
        }

        setDetalles(
            detallesVenta.map(
                (detalle) => ({
                    id_producto:
                        String(
                            detalle.id_producto
                        ),

                    cantidad:
                        Number(
                            detalle.cantidad
                        ) || 1
                })
            )
        );

    }, [venta]);


    /* ==========================================================
      OBTENER PRODUCTO
      ========================================================== */

    /**
     * Busca un producto mediante su identificador.
     *
     * @param {number|string} idProducto
     * @returns {Object|null}
     */
    const obtenerProducto =
        (idProducto) => {

            return productos.find(
                (producto) =>
                    String(
                        producto.id_producto
                    ) === String(idProducto)
            ) || null;
        };


    /* ==========================================================
      CALCULAR TOTAL
      ========================================================== */

    /**
     * Calcula el total visual de la venta.
     *
     * El cálculo definitivo se realiza nuevamente
     * en el backend.
     */
    const total =
        useMemo(() => {

            return detalles.reduce(
                (acumulado, detalle) => {

                    const producto =
                        obtenerProducto(
                            detalle.id_producto
                        );

                    const precio =
                        Number(
                            producto?.precio_unitario
                        ) || 0;

                    const cantidad =
                        Number(
                            detalle.cantidad
                        ) || 0;

                    return acumulado +
                        (
                            precio *
                            cantidad
                        );

                },
                0
            );

        }, [
            detalles,
            productos
        ]);


    /* ==========================================================
      FORMATO DE MONEDA
      ========================================================== */

    /**
     * Formatea un valor monetario en pesos colombianos.
     *
     * @param {number} valor
     * @returns {string}
     */
    const formatoMoneda =
        (valor) => {

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
      AGREGAR PRODUCTO
      ========================================================== */

    /**
     * Agrega una nueva línea de producto.
     */
    const agregarProducto = () => {

        setDetalles(
            (actuales) => [
                ...actuales,
                {
                    id_producto: "",
                    cantidad: 1
                }
            ]
        );
    };


    /* ==========================================================
      ELIMINAR PRODUCTO
      ========================================================== */

    /**
     * Elimina una línea de producto.
     *
     * Siempre debe permanecer al menos una línea.
     *
     * @param {number} indice
     */
    const eliminarProducto =
        (indice) => {

            setDetalles(
                (actuales) => {

                    if (actuales.length === 1) {

                        return [
                            {
                                id_producto: "",
                                cantidad: 1
                            }
                        ];
                    }

                    return actuales.filter(
                        (_, posicion) =>
                            posicion !== indice
                    );
                }
            );
        };


    /* ==========================================================
      CAMBIAR PRODUCTO
      ========================================================== */

    /**
     * Cambia el producto seleccionado.
     *
     * @param {number} indice
     * @param {string} idProducto
     */
    const cambiarProducto =
        (indice, idProducto) => {

            setDetalles(
                (actuales) =>
                    actuales.map(
                        (detalle, posicion) =>
                            posicion === indice
                                ? {
                                    ...detalle,
                                    id_producto:
                                        idProducto
                                }
                                : detalle
                    )
            );
        };


    /* ==========================================================
      CAMBIAR CANTIDAD
      ========================================================== */

    /**
     * Cambia la cantidad de un producto.
     *
     * @param {number} indice
     * @param {string} cantidad
     */
    const cambiarCantidad =
        (indice, cantidad) => {

            setDetalles(
                (actuales) =>
                    actuales.map(
                        (detalle, posicion) =>
                            posicion === indice
                                ? {
                                    ...detalle,
                                    cantidad:
                                        cantidad
                                }
                                : detalle
                    )
            );
        };


    /* ==========================================================
      VALIDAR FORMULARIO
      ========================================================== */

    /**
     * Valida los datos antes de enviarlos al backend.
     *
     * @returns {boolean}
     */
    const validarFormulario = () => {

        if (detalles.length === 0) {

            setError(
                "La venta debe contener al menos un producto."
            );

            return false;
        }

        const productosSeleccionados =
            new Set();

        for (
            const detalle of detalles
        ) {

            const idProducto =
                String(
                    detalle.id_producto
                );

            const cantidad =
                Number(
                    detalle.cantidad
                );

            if (
                !idProducto ||
                idProducto === "undefined"
            ) {

                setError(
                    "Debe seleccionar un producto en cada línea."
                );

                return false;
            }

            if (cantidad <= 0) {

                setError(
                    "La cantidad debe ser mayor que cero."
                );

                return false;
            }

            if (
                productosSeleccionados.has(
                    idProducto
                )
            ) {

                setError(
                    "No puede agregar el mismo producto más de una vez."
                );

                return false;
            }

            productosSeleccionados.add(
                idProducto
            );
        }

        return true;
    };


    /* ==========================================================
      GUARDAR VENTA
      ========================================================== */

    /**
     * Registra o actualiza la venta.
     *
     * @param {Event} evento
     * @returns {Promise<void>}
     */
    const manejarEnvio =
        async (evento) => {

            evento.preventDefault();

            setError("");

            if (!validarFormulario()) {
                return;
            }

            try {

                setGuardando(true);

                const datos = {

                    /*
                     * Cliente General se representa
                     * mediante un cliente NULL.
                     */
                    id_cliente: null,

                    detalles:
                        detalles.map(
                            (detalle) => ({
                                id_producto:
                                    Number(
                                        detalle.id_producto
                                    ),

                                cantidad:
                                    Number(
                                        detalle.cantidad
                                    )
                            })
                        )
                };


                if (modoEdicion) {

                    await actualizarVenta(
                        venta.id_venta,
                        datos
                    );

                } else {

                    await crearVenta(
                        datos
                    );
                }

                await onGuardar();

            } catch (error) {

                setError(
                    error.message ||
                    "No fue posible guardar la venta."
                );

            } finally {

                setGuardando(false);
            }
        };


    /* ==========================================================
      RENDERIZADO
      ========================================================== */

    return (

        <div className="venta-modal-overlay">

            <div className="venta-modal venta-modal-formulario">


                {/* ==================================================
                    ENCABEZADO
                    ================================================== */}

                <div className="venta-formulario-header">

                    <div>

                        <h2>
                            {modoEdicion
                                ? "Editar venta"
                                : "Crear venta"}
                        </h2>

                        <p>
                            {modoEdicion
                                ? `Venta #${venta.id_venta}`
                                : "Registrar una nueva venta"}
                        </p>

                    </div>


                    <button
                        type="button"
                        className="btn-cerrar"
                        onClick={onCancelar}
                        disabled={guardando}
                    >
                        ×
                    </button>

                </div>


                {/* ==================================================
                    CUERPO
                    ================================================== */}

                <form
                    className="venta-formulario-body"
                    onSubmit={manejarEnvio}
                >

                    {/* ==================================================
                        CLIENTE
                        ================================================== */}

                    <div className="form-group">

                        <label htmlFor="cliente">

                            Cliente

                        </label>

                        <select
                            id="cliente"
                            value="general"
                            disabled
                        >

                            <option value="general">

                                Cliente General

                            </option>

                        </select>

                    </div>


                    {/* ==================================================
                        PRODUCTOS
                        ================================================== */}

                    <div className="venta-productos">

                        <div className="venta-productos-header">

                            <h3>

                                Productos

                            </h3>

                            <button
                                type="button"
                                className="btn-principal"
                                onClick={
                                    agregarProducto
                                }
                                disabled={guardando}
                            >

                                + Agregar producto

                            </button>

                        </div>


                        <div className="venta-productos-lista">

                            {detalles.map(
                                (
                                    detalle,
                                    indice
                                ) => {

                                    const producto =
                                        obtenerProducto(
                                            detalle.id_producto
                                        );

                                    const precio =
                                        Number(
                                            producto?.precio_unitario
                                        ) || 0;

                                    const stock =
                                        Number(
                                            producto?.stock_disponible
                                        ) || 0;

                                    const cantidad =
                                        Number(
                                            detalle.cantidad
                                        ) || 0;

                                    const subtotal =
                                        precio *
                                        cantidad;

                                    return (

                                        <div
                                            className="venta-producto-row"
                                            key={indice}
                                        >


                                            {/* ==================================================
                                                PRODUCTO
                                                ================================================== */}

                                            <div className="form-group">

                                                <label>

                                                    Producto

                                                </label>

                                                <select
                                                    value={
                                                        detalle.id_producto
                                                    }
                                                    onChange={
                                                        (evento) =>
                                                            cambiarProducto(
                                                                indice,
                                                                evento.target.value
                                                            )
                                                    }
                                                    disabled={
                                                        guardando
                                                    }
                                                >

                                                    <option value="">

                                                        Seleccionar producto

                                                    </option>

                                                    {productos.map(
                                                        (item) => (

                                                            <option
                                                                key={
                                                                    item.id_producto
                                                                }
                                                                value={
                                                                    item.id_producto
                                                                }
                                                            >

                                                                {item.nombre}

                                                            </option>

                                                        )
                                                    )}

                                                </select>


                                                {/* Stock disponible */}

                                                {detalle.id_producto && (

                                                    <small className="venta-producto-stock">

                                                        Stock disponible:{" "}

                                                        <strong>

                                                            {stock}

                                                        </strong>

                                                    </small>

                                                )}

                                            </div>


                                            {/* Precio */}

                                            <div className="form-group">

                                                <label>

                                                    Precio

                                                </label>

                                                <input
                                                    type="text"
                                                    value={
                                                        formatoMoneda(
                                                            precio
                                                        )
                                                    }
                                                    readOnly
                                                />

                                            </div>


                                            {/* Cantidad */}

                                            <div className="form-group">

                                                <label>

                                                    Cantidad

                                                </label>

                                                <input
                                                    type="number"
                                                    min="1"
                                                    value={
                                                        detalle.cantidad
                                                    }
                                                    onChange={
                                                        (evento) =>
                                                            cambiarCantidad(
                                                                indice,
                                                                evento.target.value
                                                            )
                                                    }
                                                    disabled={
                                                        guardando
                                                    }
                                                />

                                            </div>


                                            {/* Subtotal */}

                                            <div className="form-group">

                                                <label>

                                                    Subtotal

                                                </label>

                                                <input
                                                    type="text"
                                                    value={
                                                        formatoMoneda(
                                                            subtotal
                                                        )
                                                    }
                                                    readOnly
                                                />

                                            </div>


                                            {/* Eliminar */}

                                            <button
                                                type="button"
                                                className="btn-accion"
                                                onClick={
                                                    () =>
                                                        eliminarProducto(
                                                            indice
                                                        )
                                                }
                                                disabled={
                                                    guardando
                                                }
                                            >

                                                Eliminar

                                            </button>

                                        </div>
                                    );
                                }
                            )}

                        </div>

                    </div>


                    {/* ==================================================
                        ERROR
                        ================================================== */}

                    {error && (

                        <div className="ventas-error">

                            {error}

                        </div>

                    )}


                    {/* ==================================================
                        TOTAL
                        ================================================== */}

                    <div className="venta-formulario-total">

                        <span>

                            Total

                        </span>

                        <strong>

                            {formatoMoneda(total)}

                        </strong>

                    </div>


                    {/* ==================================================
                        ACCIONES
                        ================================================== */}

                    <div className="venta-formulario-acciones">

                        <button
                            type="button"
                            className="btn-secundario"
                            onClick={onCancelar}
                            disabled={guardando}
                        >

                            Cancelar

                        </button>

                        <button
                            type="submit"
                            className="btn-principal"
                            disabled={guardando}
                        >

                            {guardando
                                ? "Guardando..."
                                : (
                                    modoEdicion
                                        ? "Guardar cambios"
                                        : "Crear venta"
                                )}

                        </button>

                    </div>

                </form>

            </div>

        </div>
    );
}

export default VentaFormulario;