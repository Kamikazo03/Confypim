package confypim.model;

import java.math.BigDecimal;
import java.time.LocalDateTime;

/**
 * ==========================================================
 * Venta
 * ==========================================================
 *
 * Representa una venta registrada en la base de datos.
 *
 * ==========================================================
 */
public class Venta {

    private int idVenta;
    private LocalDateTime fechaVenta;
    private BigDecimal total;
    private int idUsuario;
    private Integer idCliente;
    private LocalDateTime fechaCreacion;
    private String estado;

    /**
     * Constructor vacío.
     */
    public Venta() {
    }

    /**
     * Constructor completo.
     */
    public Venta(
            int idVenta,
            LocalDateTime fechaVenta,
            BigDecimal total,
            int idUsuario,
            Integer idCliente,
            LocalDateTime fechaCreacion,
            String estado) {

        this.idVenta = idVenta;
        this.fechaVenta = fechaVenta;
        this.total = total;
        this.idUsuario = idUsuario;
        this.idCliente = idCliente;
        this.fechaCreacion = fechaCreacion;
        this.estado = estado;
    }

    public int getIdVenta() {
        return idVenta;
    }

    public void setIdVenta(int idVenta) {
        this.idVenta = idVenta;
    }

    public LocalDateTime getFechaVenta() {
        return fechaVenta;
    }

    public void setFechaVenta(LocalDateTime fechaVenta) {
        this.fechaVenta = fechaVenta;
    }

    public BigDecimal getTotal() {
        return total;
    }

    public void setTotal(BigDecimal total) {
        this.total = total;
    }

    public int getIdUsuario() {
        return idUsuario;
    }

    public void setIdUsuario(int idUsuario) {
        this.idUsuario = idUsuario;
    }

    public Integer getIdCliente() {
        return idCliente;
    }

    public void setIdCliente(Integer idCliente) {
        this.idCliente = idCliente;
    }

    public LocalDateTime getFechaCreacion() {
        return fechaCreacion;
    }

    public void setFechaCreacion(LocalDateTime fechaCreacion) {
        this.fechaCreacion = fechaCreacion;
    }

    public String getEstado() {
        return estado;
    }

    public void setEstado(String estado) {
        this.estado = estado;
    }

    @Override
    public String toString() {

        return String.format(
                "Venta #%d | Fecha: %s | Total: $%s | Usuario: %d | Cliente: %s | Estado: %s",
                idVenta,
                fechaVenta,
                total,
                idUsuario,
                idCliente == null ? "General" : idCliente,
                estado
        );
    }
}