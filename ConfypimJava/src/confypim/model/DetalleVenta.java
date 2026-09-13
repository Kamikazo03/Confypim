package confypim.model;

import java.math.BigDecimal;

/**
 * ==========================================================
 * DetalleVenta
 * ==========================================================
 *
 * Representa un producto asociado a una venta.
 *
 * ==========================================================
 */
public class DetalleVenta {

    private int idDetalle;
    private int idVenta;
    private int idProducto;
    private int cantidad;
    private BigDecimal precioUnitario;
    private BigDecimal subtotal;

    /**
     * Constructor vacío.
     */
    public DetalleVenta() {
    }

    /**
     * Constructor completo.
     */
    public DetalleVenta(
            int idDetalle,
            int idVenta,
            int idProducto,
            int cantidad,
            BigDecimal precioUnitario,
            BigDecimal subtotal) {

        this.idDetalle = idDetalle;
        this.idVenta = idVenta;
        this.idProducto = idProducto;
        this.cantidad = cantidad;
        this.precioUnitario = precioUnitario;
        this.subtotal = subtotal;
    }

    public int getIdDetalle() {
        return idDetalle;
    }

    public void setIdDetalle(int idDetalle) {
        this.idDetalle = idDetalle;
    }

    public int getIdVenta() {
        return idVenta;
    }

    public void setIdVenta(int idVenta) {
        this.idVenta = idVenta;
    }

    public int getIdProducto() {
        return idProducto;
    }

    public void setIdProducto(int idProducto) {
        this.idProducto = idProducto;
    }

    public int getCantidad() {
        return cantidad;
    }

    public void setCantidad(int cantidad) {
        this.cantidad = cantidad;
    }

    public BigDecimal getPrecioUnitario() {
        return precioUnitario;
    }

    public void setPrecioUnitario(BigDecimal precioUnitario) {
        this.precioUnitario = precioUnitario;
    }

    public BigDecimal getSubtotal() {
        return subtotal;
    }

    public void setSubtotal(BigDecimal subtotal) {
        this.subtotal = subtotal;
    }

    @Override
    public String toString() {

        return String.format(
                "Detalle #%d | Venta: %d | Producto: %d | Cantidad: %d | Precio: $%s | Subtotal: $%s",
                idDetalle,
                idVenta,
                idProducto,
                cantidad,
                precioUnitario,
                subtotal
        );
    }
}