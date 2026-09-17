package confypim.web;

import confypim.controller.VentaController;
import confypim.model.DetalleVenta;
import confypim.model.Producto;
import confypim.model.Venta;

import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

import java.io.IOException;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

@WebServlet("/ventas")
public class VentaServlet extends HttpServlet {

    private static final long serialVersionUID = 1L;

    private VentaController ventaController;

    @Override
    public void init() throws ServletException {
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
            ventaController = new VentaController();
        } catch (ClassNotFoundException e) {
            throw new ServletException(
                    "No se pudo cargar el controlador de MySQL.", e
            );
        }
    }

    @Override
    protected void doGet(
            HttpServletRequest request,
            HttpServletResponse response
    ) throws ServletException, IOException {

        request.setCharacterEncoding("UTF-8");

        String accion = request.getParameter("accion");

        try {
            if ("editar".equalsIgnoreCase(accion)) {

                cargarVentaParaEditar(request);

            } else if ("detalle".equalsIgnoreCase(accion)) {

                cargarDetalleVenta(request);

            }

            cargarVistaVentas(request, response);

        } catch (SQLException | NumberFormatException e) {

            request.setAttribute(
                    "error",
                    "No se pudo cargar la información de la venta: "
                            + e.getMessage()
            );

            cargarVistaVentas(request, response);
        }
    }

    @Override
    protected void doPost(
            HttpServletRequest request,
            HttpServletResponse response
    ) throws ServletException, IOException {

        request.setCharacterEncoding("UTF-8");

        String accion = request.getParameter("accion");

        if (accion == null || accion.isBlank()) {
            accion = "crear";
        }

        try {

            switch (accion.toLowerCase()) {

                case "crear":
                    procesarCrearVenta(request);
                    break;

                case "actualizar":
                    procesarActualizarVenta(request);
                    break;

                case "pagar":
                    procesarPagarVenta(request);
                    break;

                case "cancelar":
                    procesarCancelarVenta(request);
                    break;

                default:
                    request.setAttribute(
                            "error",
                            "La acción solicitada no es válida."
                    );
            }

        } catch (IllegalArgumentException e) {

            request.setAttribute(
                    "error",
                    e.getMessage()
            );

        } catch (SQLException e) {

            request.setAttribute(
                    "error",
                    "Error en la base de datos: " + e.getMessage()
            );

        } catch (Exception e) {

            request.setAttribute(
                    "error",
                    "Ocurrió un error inesperado: " + e.getMessage()
            );
        }

        cargarVistaVentas(request, response);
    }

    /**
     * Registra una nueva venta en estado Pendiente.
     */
    private void procesarCrearVenta(
            HttpServletRequest request
    ) throws SQLException {

        List<DetalleVenta> detalles = obtenerDetallesDesdeFormulario(request);

        Venta venta = new Venta();

        venta.setIdUsuario(1);
        venta.setIdCliente(null);
        venta.setEstado("Pendiente");

        ventaController.crearVenta(venta, detalles);

        request.setAttribute(
                "mensaje",
                "La venta fue creada correctamente."
        );
    }

    /**
     * Actualiza una venta que se encuentra en estado Pendiente.
     */
    private void procesarActualizarVenta(
            HttpServletRequest request
    ) throws SQLException {

        int idVenta = obtenerIdVenta(request);

        List<DetalleVenta> detalles =
                obtenerDetallesDesdeFormulario(request);

        Venta venta = new Venta();

        venta.setIdVenta(idVenta);
        venta.setIdUsuario(1);
        venta.setIdCliente(null);
        venta.setEstado("Pendiente");

        ventaController.actualizarVenta(venta, detalles);

        request.setAttribute(
                "mensaje",
                "La venta #" + idVenta
                        + " fue actualizada correctamente."
        );
    }

    /**
     * Cambia una venta Pendiente a Pagada y descuenta el stock.
     */
    private void procesarPagarVenta(
            HttpServletRequest request
    ) throws SQLException {

        int idVenta = obtenerIdVenta(request);

        ventaController.pagarVenta(idVenta);

        request.setAttribute(
                "mensaje",
                "La venta #" + idVenta
                        + " fue marcada como pagada."
        );
    }

    /**
     * Cambia una venta Pendiente a Cancelada.
     */
    private void procesarCancelarVenta(
            HttpServletRequest request
    ) throws SQLException {

        int idVenta = obtenerIdVenta(request);

        ventaController.cancelarVenta(idVenta);

        request.setAttribute(
                "mensaje",
                "La venta #" + idVenta
                        + " fue cancelada correctamente."
        );
    }

    /**
     * Obtiene el ID de la venta enviado desde un formulario.
     */
    private int obtenerIdVenta(
            HttpServletRequest request
    ) {

        String idVentaParametro =
                request.getParameter("idVenta");

        if (idVentaParametro == null
                || idVentaParametro.isBlank()) {

            throw new IllegalArgumentException(
                    "No se recibió el identificador de la venta."
            );
        }

        try {

            int idVenta = Integer.parseInt(idVentaParametro);

            if (idVenta <= 0) {
                throw new IllegalArgumentException(
                        "El identificador de la venta no es válido."
                );
            }

            return idVenta;

        } catch (NumberFormatException e) {

            throw new IllegalArgumentException(
                    "El identificador de la venta debe ser numérico."
            );
        }
    }

    /**
     * Obtiene los productos y cantidades enviados desde el formulario.
     */
    private List<DetalleVenta> obtenerDetallesDesdeFormulario(
            HttpServletRequest request
    ) {

        String[] productos =
                request.getParameterValues("producto[]");

        String[] cantidades =
                request.getParameterValues("cantidad[]");

        if (productos == null || cantidades == null) {

            throw new IllegalArgumentException(
                    "Debe agregar al menos un producto."
            );
        }

        if (productos.length == 0
                || cantidades.length == 0) {

            throw new IllegalArgumentException(
                    "Debe agregar al menos un producto."
            );
        }

        if (productos.length != cantidades.length) {

            throw new IllegalArgumentException(
                    "Los productos y las cantidades no coinciden."
            );
        }

        List<DetalleVenta> detalles = new ArrayList<>();

        for (int i = 0; i < productos.length; i++) {

            int idProducto;
            int cantidad;

            try {

                idProducto = Integer.parseInt(productos[i]);
                cantidad = Integer.parseInt(cantidades[i]);

            } catch (NumberFormatException e) {

                throw new IllegalArgumentException(
                        "Los productos y las cantidades deben ser válidos."
                );
            }

            if (idProducto <= 0) {

                throw new IllegalArgumentException(
                        "El producto seleccionado no es válido."
                );
            }

            if (cantidad <= 0) {

                throw new IllegalArgumentException(
                        "La cantidad debe ser mayor que cero."
                );
            }

            DetalleVenta detalle = new DetalleVenta();

            detalle.setIdProducto(idProducto);
            detalle.setCantidad(cantidad);

            detalles.add(detalle);
        }

        validarProductosDuplicados(detalles);

        return detalles;
    }

    /**
     * Evita que el mismo producto sea agregado más de una vez.
     */
    private void validarProductosDuplicados(
            List<DetalleVenta> detalles
    ) {

        for (int i = 0; i < detalles.size(); i++) {

            for (int j = i + 1; j < detalles.size(); j++) {

                if (detalles.get(i).getIdProducto()
                        == detalles.get(j).getIdProducto()) {

                    throw new IllegalArgumentException(
                            "No puede agregar el mismo producto "
                                    + "más de una vez."
                    );
                }
            }
        }
    }

    /**
     * Carga una venta pendiente y sus detalles para editarla.
     */
    private void cargarVentaParaEditar(
            HttpServletRequest request
    ) throws SQLException {

        int idVenta = obtenerIdVenta(request);

        Venta venta = ventaController.obtenerVenta(idVenta);

        if (venta == null) {

            request.setAttribute(
                    "error",
                    "La venta solicitada no existe."
            );

            return;
        }

        if (!"Pendiente".equalsIgnoreCase(venta.getEstado())) {

            request.setAttribute(
                    "error",
                    "Solo se pueden editar ventas pendientes."
            );

            return;
        }

        List<DetalleVenta> detalles =
                ventaController.obtenerDetalles(idVenta);

        request.setAttribute("ventaEditar", venta);
        request.setAttribute("detallesEditar", detalles);
    }

    /**
     * Carga una venta y sus detalles para consulta.
     */
    private void cargarDetalleVenta(
            HttpServletRequest request
    ) throws SQLException {

        int idVenta = obtenerIdVenta(request);

        Venta venta = ventaController.obtenerVenta(idVenta);

        if (venta == null) {

            request.setAttribute(
                    "error",
                    "La venta solicitada no existe."
            );

            return;
        }

        List<DetalleVenta> detalles =
                ventaController.obtenerDetalles(idVenta);

        request.setAttribute("ventaDetalle", venta);
        request.setAttribute("detallesDetalle", detalles);
    }

    /**
     * Carga los datos necesarios para mostrar la vista.
     */
    private void cargarVistaVentas(
            HttpServletRequest request,
            HttpServletResponse response
    ) throws ServletException, IOException {

        try {

            List<Venta> ventas =
                    ventaController.listarVentas();

            List<Producto> productos =
                    ventaController.listarProductosDisponibles();

            request.setAttribute("ventas", ventas);
            request.setAttribute("productos", productos);

            request.getRequestDispatcher(
                    "/ventas/ventas.jsp"
            ).forward(request, response);

        } catch (SQLException e) {

            request.setAttribute(
                    "error",
                    "No se pudo cargar la información de ventas: "
                            + e.getMessage()
            );

            request.getRequestDispatcher(
                    "/ventas/ventas.jsp"
            ).forward(request, response);
        }
    }
}