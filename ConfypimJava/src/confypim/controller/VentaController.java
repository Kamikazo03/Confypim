package confypim.controller;

import confypim.dao.VentaDAO;
import confypim.model.DetalleVenta;
import confypim.model.Venta;

import java.math.BigDecimal;
import java.sql.SQLException;
import java.util.List;

/**
 * ==========================================================
 * VentaController
 * ==========================================================
 *
 * Controla las operaciones relacionadas con las ventas
 * y sirve como intermediario entre la aplicación y el DAO.
 *
 * ==========================================================
 */
public class VentaController {

    private final VentaDAO ventaDAO;

    /**
     * Constructor.
     */
    public VentaController() {

        this.ventaDAO = new VentaDAO();
    }

    /**
     * Lista las ventas activas.
     */
    public List<Venta> listarVentas()
            throws SQLException {

        return ventaDAO.listarVentas();
    }

    /**
     * Lista todas las ventas incluyendo canceladas.
     */
    public List<Venta> listarTodasVentas()
            throws SQLException {

        return ventaDAO.listarTodasVentas();
    }

    /**
     * Busca una venta por ID.
     */
    public Venta obtenerVenta(int idVenta)
            throws SQLException {

        return ventaDAO.obtenerVentaPorId(idVenta);
    }

    /**
     * Obtiene los detalles de una venta.
     */
    public List<DetalleVenta> obtenerDetalles(
            int idVenta)
            throws SQLException {

        return ventaDAO.obtenerDetallesVenta(idVenta);
    }

    /**
     * Crea una nueva venta.
     */
    public int crearVenta(
            Venta venta,
            List<DetalleVenta> detalles)
            throws SQLException {

        return ventaDAO.crearVenta(
                venta,
                detalles
        );
    }

    /**
     * Actualiza una venta pendiente.
     */
    public void actualizarVenta(
            Venta venta,
            List<DetalleVenta> detalles)
            throws SQLException {

        ventaDAO.actualizarVenta(
                venta,
                detalles
        );
    }

    /**
     * Paga una venta pendiente.
     */
    public void pagarVenta(int idVenta)
            throws SQLException {

        ventaDAO.pagarVenta(idVenta);
    }

    /**
     * Cancela una venta pendiente.
     */
    public void cancelarVenta(int idVenta)
            throws SQLException {

        ventaDAO.cancelarVenta(idVenta);
    }

    /**
     * Obtiene la cantidad de ventas pagadas.
     */
    public int contarVentasPagadas()
            throws SQLException {

        return ventaDAO.contarVentasPagadas();
    }

    /**
     * Obtiene el total de dinero de ventas pagadas.
     */
    public BigDecimal obtenerTotalVentasPagadas()
            throws SQLException {

        return ventaDAO.obtenerTotalVentasPagadas();
    }

    /**
     * Obtiene el total de productos vendidos.
     */
    public int contarProductosVendidos()
            throws SQLException {

        return ventaDAO.contarProductosVendidos();
    }

    /**
     * Obtiene la cantidad de productos diferentes vendidos.
     */
    public int contarProductosDiferentes()
            throws SQLException {

        return ventaDAO.contarProductosDiferentes();
    }

    /**
     * Obtiene el stock actual de un producto.
     *
     * @param idProducto identificador del producto
     * @return cantidad disponible en inventario
     */
    public int obtenerStockProducto(
            int idProducto)
            throws SQLException {

        return ventaDAO.obtenerStockProducto(
                idProducto
        );
    }
 
}