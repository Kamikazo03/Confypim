
package com.confypim.confypim_java_spring.service;

import com.confypim.confypim_java_spring.model.DetalleVenta;
import com.confypim.confypim_java_spring.model.Producto;
import com.confypim.confypim_java_spring.model.Venta;
import com.confypim.confypim_java_spring.repository.ProductoRepository;
import com.confypim.confypim_java_spring.repository.VentaRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

@Service
public class VentaService {

    private final VentaRepository ventaRepository;
    private final ProductoRepository productoRepository;

    public VentaService(
            VentaRepository ventaRepository,
            ProductoRepository productoRepository
    ) {
        this.ventaRepository = ventaRepository;
        this.productoRepository = productoRepository;
    }

    // ==========================================================
    // CONSULTAS
    // ==========================================================

    public List<Venta> listarVentasVisibles() {
        return ventaRepository.listarVentasVisibles();
    }

    public Venta obtenerVentaPorId(Integer idVenta) {
        return ventaRepository.obtenerVentaPorId(idVenta);
    }

    public List<DetalleVenta> obtenerDetallesPorVenta(Integer idVenta) {
        return ventaRepository.obtenerDetallesPorVenta(idVenta);
    }

    // ==========================================================
    // CREAR VENTA
    // ==========================================================

    @Transactional
    public Integer crearVenta(
            Venta venta,
            List<DetalleVenta> detalles
    ) {

        validarDetalles(detalles);

        venta.setTotal(calcularTotal(detalles));

        Integer idVenta = ventaRepository.crearVenta(venta);

        for (DetalleVenta detalle : detalles) {

            detalle.setIdVenta(idVenta);

            Producto producto = productoRepository
                    .obtenerProductoPorId(detalle.getIdProducto());

            validarProducto(producto, detalle);

            detalle.setPrecioUnitario(
                    producto.getPrecioUnitario()
            );

            detalle.setSubtotal(
                    producto.getPrecioUnitario()
                            .multiply(
                                    BigDecimal.valueOf(detalle.getCantidad())
                            )
            );

            ventaRepository.crearDetalle(detalle);
        }

        return idVenta;
    }

    // ==========================================================
    // ACTUALIZAR VENTA PENDIENTE
    // ==========================================================

    @Transactional
    public void actualizarVenta(
            Integer idVenta,
            Venta venta,
            List<DetalleVenta> detalles
    ) {

        Venta ventaExistente = ventaRepository
                .obtenerVentaPorId(idVenta);

        validarVentaPendiente(ventaExistente);
        validarDetalles(detalles);

        venta.setTotal(calcularTotalConProductos(detalles));

        int actualizada = ventaRepository.actualizarVenta(
                idVenta,
                venta
        );

        if (actualizada == 0) {
            throw new IllegalStateException(
                    "La venta no existe o no está pendiente."
            );
        }

        ventaRepository.eliminarDetalles(idVenta);

        for (DetalleVenta detalle : detalles) {

            Producto producto = productoRepository
                    .obtenerProductoPorId(detalle.getIdProducto());

            validarProducto(producto, detalle);

            detalle.setIdVenta(idVenta);
            detalle.setPrecioUnitario(
                    producto.getPrecioUnitario()
            );

            detalle.setSubtotal(
                    producto.getPrecioUnitario()
                            .multiply(
                                    BigDecimal.valueOf(detalle.getCantidad())
                            )
            );

            ventaRepository.crearDetalle(detalle);
        }
    }

    // ==========================================================
    // PAGAR VENTA
    // ==========================================================

    @Transactional
    public void pagarVenta(Integer idVenta) {

        Venta venta = ventaRepository.obtenerVentaPorId(idVenta);

        validarVentaPendiente(venta);

        List<DetalleVenta> detalles =
                ventaRepository.obtenerDetallesPorVenta(idVenta);

        validarDetalles(detalles);

        for (DetalleVenta detalle : detalles) {

            Producto producto = productoRepository
                    .obtenerProductoPorId(detalle.getIdProducto());

            validarProducto(producto, detalle);

            int actualizado = productoRepository.descontarStock(
                    detalle.getIdProducto(),
                    detalle.getCantidad()
            );

            if (actualizado == 0) {
                throw new IllegalStateException(
                        "No hay suficiente stock para el producto: "
                                + producto.getNombre()
                );
            }
        }

        int pagada = ventaRepository.pagarVenta(idVenta);

        if (pagada == 0) {
            throw new IllegalStateException(
                    "No se pudo pagar la venta."
            );
        }
    }

    // ==========================================================
    // CANCELAR VENTA
    // ==========================================================

    @Transactional
    public void cancelarVenta(Integer idVenta) {

        Venta venta = ventaRepository.obtenerVentaPorId(idVenta);

        validarVentaPendiente(venta);

        int cancelada = ventaRepository.cancelarVenta(idVenta);

        if (cancelada == 0) {
            throw new IllegalStateException(
                    "No se pudo cancelar la venta."
            );
        }
    }

    // ==========================================================
    // VALIDACIONES
    // ==========================================================

    private void validarVentaPendiente(Venta venta) {

        if (venta == null) {
            throw new IllegalArgumentException(
                    "La venta no existe."
            );
        }

        if (!"Pendiente".equals(venta.getEstado())) {
            throw new IllegalStateException(
                    "Solo se pueden modificar ventas pendientes."
            );
        }
    }

    private void validarDetalles(List<DetalleVenta> detalles) {

        if (detalles == null || detalles.isEmpty()) {
            throw new IllegalArgumentException(
                    "La venta debe tener al menos un producto."
            );
        }

        Set<Integer> productos = new HashSet<>();

        for (DetalleVenta detalle : detalles) {

            if (detalle.getIdProducto() == null) {
                throw new IllegalArgumentException(
                        "El producto es obligatorio."
                );
            }

            if (detalle.getCantidad() == null
                    || detalle.getCantidad() <= 0) {

                throw new IllegalArgumentException(
                        "La cantidad debe ser mayor que cero."
                );
            }

            if (!productos.add(detalle.getIdProducto())) {
                throw new IllegalArgumentException(
                        "No se permiten productos repetidos."
                );
            }
        }
    }

    private void validarProducto(
            Producto producto,
            DetalleVenta detalle
    ) {

        if (producto == null) {
            throw new IllegalArgumentException(
                    "El producto no existe."
            );
        }

        if (!Boolean.TRUE.equals(producto.getActivo())) {
            throw new IllegalArgumentException(
                    "El producto no está activo."
            );
        }

        if (producto.getStockDisponible() < detalle.getCantidad()) {
            throw new IllegalArgumentException(
                    "Stock insuficiente para el producto: "
                            + producto.getNombre()
            );
        }
    }

    // ==========================================================
    // CÁLCULOS
    // ==========================================================

    private BigDecimal calcularTotal(List<DetalleVenta> detalles) {

        BigDecimal total = BigDecimal.ZERO;

        for (DetalleVenta detalle : detalles) {

            Producto producto = productoRepository
                    .obtenerProductoPorId(detalle.getIdProducto());

            validarProducto(producto, detalle);

            BigDecimal subtotal = producto.getPrecioUnitario()
                    .multiply(
                            BigDecimal.valueOf(detalle.getCantidad())
                    );

            total = total.add(subtotal);
        }

        return total;
    }

    private BigDecimal calcularTotalConProductos(
            List<DetalleVenta> detalles
    ) {

        return calcularTotal(detalles);
    }
}