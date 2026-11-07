/**
 * ==========================================================
 * Módulo principal de Ventas
 * ==========================================================
 *
 * Componente principal encargado de coordinar la interfaz
 * del módulo de Ventas.
 *
 * Responsabilidades:
 * - Cargar ventas.
 * - Cargar métricas.
 * - Cargar productos.
 * - Aplicar filtros.
 * - Abrir detalles.
 * - Abrir formulario de creación y edición.
 * - Gestionar pago y cancelación.
 * ==========================================================
 */

import React, {
    useCallback,
    useEffect,
    useState
} from "react";


import {
    listarVentas,
    obtenerVenta,
    obtenerMetricas,
    pagarVenta,
    cancelarVenta
} from "../../services/ventaService";


import VentasHeader from "./VentasHeader";
import VentasMetricas from "./VentasMetricas";
import VentasFiltros from "./VentasFiltros";
import VentasTabla from "./VentasTabla";
import VentaDetalle from "./VentaDetalle";
import VentaFormulario from "./VentaFormulario";


/* ==========================================================
  COMPONENTE PRINCIPAL
  ========================================================== */

/**
 * Componente principal del módulo de Ventas.
 *
 * @returns {JSX.Element} Interfaz completa del módulo.
 */
function VentasApp() {


    /* ==========================================================
      ESTADOS GENERALES
      ========================================================== */

    const [ventas, setVentas] =
        useState([]);


    const [productos, setProductos] =
        useState([]);


    const [metricas, setMetricas] =
        useState({});


    const [cargando, setCargando] =
        useState(true);


    const [error, setError] =
        useState("");


    const [procesando, setProcesando] =
        useState(false);


    /* ==========================================================
      ESTADOS DE FILTROS
      ========================================================== */

    /**
     * Una cadena vacía representa "Todos".
     *
     * Esto coincide con:
     *
     * <option value="">
     *     Todos
     * </option>
     */
    const [estadoFiltro, setEstadoFiltro] =
        useState("");


    const [busqueda, setBusqueda] =
        useState("");


    /* ==========================================================
      ESTADOS DE DETALLE
      ========================================================== */

    const [ventaDetalle, setVentaDetalle] =
        useState(null);


    const [mostrarDetalle, setMostrarDetalle] =
        useState(false);


    const [cargandoDetalle, setCargandoDetalle] =
        useState(false);


    /* ==========================================================
      ESTADOS DE FORMULARIO
      ========================================================== */

    const [ventaEditar, setVentaEditar] =
        useState(null);


    const [mostrarFormulario, setMostrarFormulario] =
        useState(false);


    /* ==========================================================
      CARGAR VENTAS
      ========================================================== */

    /**
     * Obtiene el listado de ventas aplicando los filtros
     * seleccionados por el usuario.
     *
     * El servicio recibe:
     * - estado
     * - busqueda
     *
     * La respuesta del backend tiene la estructura:
     *
     * {
     *     success: true,
     *     data: [...]
     * }
     *
     * @returns {Promise<void>}
     */
    const cargarVentas =
        useCallback(async () => {

            try {

                setCargando(true);

                setError("");


                /* ==================================================
                   CONSULTAR VENTAS
                   ================================================== */

                const resultado =
                    await listarVentas(
                        estadoFiltro,
                        busqueda.trim()
                    );


                /* ==================================================
                   GUARDAR DATOS
                   ================================================== */

                setVentas(
                    Array.isArray(
                        resultado?.data
                    )
                        ? resultado.data
                        : []
                );


            } catch (error) {

                setError(
                    error.message ||
                    "No fue posible cargar las ventas."
                );


                setVentas([]);


            } finally {

                setCargando(false);

            }

        }, [
            estadoFiltro,
            busqueda
        ]);


    /* ==========================================================
      CARGAR MÉTRICAS
      ========================================================== */

    /**
     * Obtiene las métricas de las ventas pagadas.
     *
     * La respuesta del backend tiene la estructura:
     *
     * {
     *     success: true,
     *     data: {
     *         ventas_pagadas: ...,
     *         ingresos_totales: ...,
     *         productos_vendidos: ...,
     *         productos_diferentes: ...,
     *         promedio_venta: ...
     *     }
     * }
     *
     * @returns {Promise<void>}
     */
    const cargarMetricas =
        useCallback(async () => {

            try {

                const resultado =
                    await obtenerMetricas();


                /* ==================================================
                   GUARDAR MÉTRICAS
                   ================================================== */

                setMetricas(
                    resultado?.data ||
                    {}
                );


            } catch (error) {

                console.error(
                    "Error al cargar métricas:",
                    error
                );

            }

        }, []);


    /* ==========================================================
      CARGAR PRODUCTOS
      ========================================================== */

    /**
     * Obtiene los productos activos disponibles
     * para registrar una venta.
     *
     * El endpoint de productos devuelve directamente
     * un arreglo de productos.
     *
     * @returns {Promise<void>}
     */
    const cargarProductos =
        useCallback(async () => {

            try {

                const respuesta =
                    await fetch(
                        "/Confypim/ajax/producto.php",
                        {
                            headers: {
                                Accept:
                                    "application/json"
                            }
                        }
                    );


                const datos =
                    await respuesta.json();


                if (!respuesta.ok) {

                    throw new Error(
                        "No fue posible cargar los productos."
                    );

                }


                /* ==================================================
                   GUARDAR PRODUCTOS
                   ================================================== */

                setProductos(

                    Array.isArray(datos)

                        ? datos

                        : (
                            datos?.data ||
                            datos?.productos ||
                            []
                        )

                );


            } catch (error) {

                console.error(
                    "Error al cargar productos:",
                    error
                );

            }

        }, []);


    /* ==========================================================
      CARGA INICIAL
      ========================================================== */

    /**
     * Carga los datos necesarios al iniciar el módulo.
     */
    useEffect(() => {

        cargarMetricas();

        cargarProductos();

    }, [
        cargarMetricas,
        cargarProductos
    ]);


    /* ==========================================================
      CARGAR VENTAS AL CAMBIAR FILTROS
      ========================================================== */

    /**
     * Actualiza el listado cuando cambia:
     * - Estado.
     * - Texto de búsqueda.
     */
    useEffect(() => {

        cargarVentas();

    }, [
        cargarVentas
    ]);


    /* ==========================================================
      VER DETALLE
      ========================================================== */

    /**
     * Obtiene una venta completa y muestra sus detalles.
     *
     * @param {number|string} idVenta
     * @returns {Promise<void>}
     */
    const abrirDetalle =
        async (idVenta) => {

            try {

                setCargandoDetalle(true);

                setError("");


                /* ==================================================
                   CONSULTAR VENTA
                   ================================================== */

                const resultado =
                    await obtenerVenta(
                        idVenta
                    );


                const venta =
                    resultado?.data;


                if (!venta) {

                    throw new Error(
                        "No fue posible encontrar la venta."
                    );

                }


                /* ==================================================
                   MOSTRAR DETALLE
                   ================================================== */

                setVentaDetalle(
                    venta
                );


                setMostrarDetalle(
                    true
                );


            } catch (error) {

                setError(
                    error.message ||
                    "No fue posible obtener el detalle."
                );


            } finally {

                setCargandoDetalle(
                    false
                );

            }

        };


    /* ==========================================================
      CERRAR DETALLE
      ========================================================== */

    /**
     * Cierra la ventana de detalle.
     */
    const cerrarDetalle = () => {

        setMostrarDetalle(false);

        setVentaDetalle(null);

    };


    /* ==========================================================
      CREAR VENTA
      ========================================================== */

    /**
     * Abre el formulario para registrar una nueva venta.
     */
    const abrirFormularioCrear = () => {

        setVentaEditar(null);

        setMostrarFormulario(true);

        setError("");

    };


    /* ==========================================================
      EDITAR VENTA
      ========================================================== */

    /**
     * Obtiene una venta pendiente y abre el formulario
     * de edición.
     *
     * @param {number|string} idVenta
     * @returns {Promise<void>}
     */
    const abrirFormularioEditar =
        async (idVenta) => {

            try {

                setError("");


                /* ==================================================
                   CONSULTAR VENTA
                   ================================================== */

                const resultado =
                    await obtenerVenta(
                        idVenta
                    );


                const venta =
                    resultado?.data;


                if (!venta) {

                    throw new Error(
                        "No fue posible encontrar la venta."
                    );

                }


                /* ==================================================
                   VALIDAR ESTADO
                   ================================================== */

                if (
                    String(
                        venta.estado
                    ).toLowerCase() !==
                    "pendiente"
                ) {

                    throw new Error(
                        "Solo las ventas pendientes pueden modificarse."
                    );

                }


                /* ==================================================
                   ABRIR FORMULARIO
                   ================================================== */

                setVentaEditar(
                    venta
                );


                setMostrarFormulario(
                    true
                );


            } catch (error) {

                setError(
                    error.message ||
                    "No fue posible abrir la venta."
                );

            }

        };


    /* ==========================================================
      CERRAR FORMULARIO
      ========================================================== */

    /**
     * Cierra el formulario de venta.
     */
    const cerrarFormulario = () => {

        setMostrarFormulario(false);

        setVentaEditar(null);

    };


    /* ==========================================================
      VENTA GUARDADA
      ========================================================== */

    /**
     * Actualiza el listado y las métricas después
     * de crear o modificar una venta.
     *
     * @returns {Promise<void>}
     */
    const manejarVentaGuardada =
        async () => {

            cerrarFormulario();


            await cargarVentas();

            await cargarMetricas();

        };


    /* ==========================================================
      PAGAR VENTA
      ========================================================== */

    /**
     * Marca una venta pendiente como pagada.
     *
     * @param {number|string} idVenta
     * @returns {Promise<void>}
     */
    const manejarPagarVenta =
        async (idVenta) => {

            const confirmar =
                window.confirm(
                    "¿Desea marcar esta venta como pagada?"
                );


            if (!confirmar) {

                return;

            }


            try {

                setProcesando(true);

                setError("");


                await pagarVenta(
                    idVenta
                );


                await cargarVentas();

                await cargarMetricas();


            } catch (error) {

                setError(
                    error.message ||
                    "No fue posible pagar la venta."
                );


            } finally {

                setProcesando(false);

            }

        };


    /* ==========================================================
      CANCELAR VENTA
      ========================================================== */

    /**
     * Cancela una venta pendiente.
     *
     * @param {number|string} idVenta
     * @returns {Promise<void>}
     */
    const manejarCancelarVenta =
        async (idVenta) => {

            const confirmar =
                window.confirm(
                    "¿Desea cancelar esta venta?"
                );


            if (!confirmar) {

                return;

            }


            try {

                setProcesando(true);

                setError("");


                await cancelarVenta(
                    idVenta
                );


                await cargarVentas();

                await cargarMetricas();


            } catch (error) {

                setError(
                    error.message ||
                    "No fue posible cancelar la venta."
                );


            } finally {

                setProcesando(false);

            }

        };


    /* ==========================================================
      RENDERIZADO
      ========================================================== */

    return (

        <div className="ventas-app">


            {/* ==================================================
                ENCABEZADO
                ================================================== */}

            <VentasHeader
                onCrearVenta={
                    abrirFormularioCrear
                }
            />


            {/* ==================================================
                MENSAJE DE ERROR
                ================================================== */}

            {error && (

                <div className="ventas-error">

                    {error}

                </div>

            )}


            {/* ==================================================
                MÉTRICAS
                ================================================== */}

            <VentasMetricas
                metricas={metricas}
            />


            {/* ==================================================
                FILTROS
                ================================================== */}

            <VentasFiltros
                estado={estadoFiltro}
                busqueda={busqueda}
                onEstadoChange={
                    setEstadoFiltro
                }
                onBusquedaChange={
                    setBusqueda
                }
            />


            {/* ==================================================
                TABLA
                ================================================== */}

            <VentasTabla
                ventas={ventas}
                cargando={cargando}
                procesando={procesando}
                onDetalle={abrirDetalle}
                onEditar={
                    abrirFormularioEditar
                }
                onPagar={
                    manejarPagarVenta
                }
                onCancelar={
                    manejarCancelarVenta
                }
            />


            {/* ==================================================
                DETALLE
                ================================================== */}

            {mostrarDetalle && (

                <VentaDetalle
                    venta={ventaDetalle}
                    cargando={cargandoDetalle}
                    onCerrar={cerrarDetalle}
                />

            )}


            {/* ==================================================
                FORMULARIO
                ================================================== */}

            {mostrarFormulario && (

                <VentaFormulario
                    venta={ventaEditar}
                    productos={productos}
                    onGuardar={
                        manejarVentaGuardada
                    }
                    onCancelar={
                        cerrarFormulario
                    }
                />

            )}

        </div>
    );
}

export default VentasApp;