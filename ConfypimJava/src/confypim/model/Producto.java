package confypim.model;

import java.math.BigDecimal;

/**
 * ==========================================================
 * Producto
 * ==========================================================
 *
 * Modelo que representa un producto de Confypim.
 *
 * Se utiliza para transportar la información de los
 * productos disponibles entre la capa DAO, el controlador
 * y las vistas de la aplicación web.
 *
 * ==========================================================
 */
public class Producto {

    private int idProducto;
    private String nombre;
    private BigDecimal precio;
    private int stock;
    private boolean activo;

    /**
     * Constructor vacío.
     */
    public Producto() {
    }

    /**
     * Constructor completo.
     *
     * @param idProducto identificador del producto.
     * @param nombre nombre del producto.
     * @param precio precio actual del producto.
     * @param stock cantidad disponible.
     * @param activo indica si el producto está activo.
     */
    public Producto(
            int idProducto,
            String nombre,
            BigDecimal precio,
            int stock,
            boolean activo) {

        this.idProducto = idProducto;
        this.nombre = nombre;
        this.precio = precio;
        this.stock = stock;
        this.activo = activo;
    }

    public int getIdProducto() {
        return idProducto;
    }

    public void setIdProducto(int idProducto) {
        this.idProducto = idProducto;
    }

    public String getNombre() {
        return nombre;
    }

    public void setNombre(String nombre) {
        this.nombre = nombre;
    }

    public BigDecimal getPrecio() {
        return precio;
    }

    public void setPrecio(BigDecimal precio) {
        this.precio = precio;
    }

    public int getStock() {
        return stock;
    }

    public void setStock(int stock) {
        this.stock = stock;
    }

    public boolean isActivo() {
        return activo;
    }

    public void setActivo(boolean activo) {
        this.activo = activo;
    }

    @Override
    public String toString() {
        return String.format(
                "Producto #%d | %s | Precio: $%s | Stock: %d | Activo: %s",
                idProducto,
                nombre,
                precio,
                stock,
                activo
        );
    }
}