
package com.confypim.confypim_java_spring.repository;

import com.confypim.confypim_java_spring.model.DetalleVenta;
import com.confypim.confypim_java_spring.model.Venta;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.jdbc.support.KeyHolder;
import org.springframework.stereotype.Repository;

import java.sql.PreparedStatement;
import java.sql.Statement;
import java.util.List;

@Repository
public class VentaRepository {

    private final JdbcTemplate jdbcTemplate;

    public VentaRepository(JdbcTemplate jdbcTemplate) {
        this.jdbcTemplate = jdbcTemplate;
    }

    // ==========================================================
    // LISTAR VENTAS VISIBLES
    // ==========================================================

    public List<Venta> listarVentasVisibles() {

        String sql = """
                SELECT
                    id_venta,
                    fecha_venta,
                    total,
                    id_usuario,
                    id_cliente,
                    fecha_creacion,
                    estado
                FROM venta
                WHERE estado IN ('Pendiente', 'Pagada')
                ORDER BY fecha_venta DESC
                """;

        return jdbcTemplate.query(sql, (rs, rowNum) -> {

            Venta venta = new Venta();

            venta.setIdVenta(rs.getInt("id_venta"));
            venta.setFechaVenta(
                    rs.getTimestamp("fecha_venta").toLocalDateTime()
            );
            venta.setTotal(rs.getBigDecimal("total"));
            venta.setIdUsuario(rs.getInt("id_usuario"));

            venta.setIdCliente(
                    rs.getObject("id_cliente", Integer.class)
            );

            venta.setFechaCreacion(
                    rs.getTimestamp("fecha_creacion").toLocalDateTime()
            );

            venta.setEstado(rs.getString("estado"));

            return venta;
        });
    }

    // ==========================================================
    // OBTENER VENTA POR ID
    // ==========================================================

    public Venta obtenerVentaPorId(Integer idVenta) {

        String sql = """
                SELECT
                    id_venta,
                    fecha_venta,
                    total,
                    id_usuario,
                    id_cliente,
                    fecha_creacion,
                    estado
                FROM venta
                WHERE id_venta = ?
                """;

        List<Venta> ventas = jdbcTemplate.query(
                sql,
                (rs, rowNum) -> {

                    Venta venta = new Venta();

                    venta.setIdVenta(rs.getInt("id_venta"));
                    venta.setFechaVenta(
                            rs.getTimestamp("fecha_venta").toLocalDateTime()
                    );
                    venta.setTotal(rs.getBigDecimal("total"));
                    venta.setIdUsuario(rs.getInt("id_usuario"));

                    venta.setIdCliente(
                            rs.getObject("id_cliente", Integer.class)
                    );

                    venta.setFechaCreacion(
                            rs.getTimestamp("fecha_creacion").toLocalDateTime()
                    );

                    venta.setEstado(rs.getString("estado"));

                    return venta;
                },
                idVenta
        );

        return ventas.isEmpty() ? null : ventas.get(0);
    }

    // ==========================================================
    // OBTENER DETALLES DE UNA VENTA
    // ==========================================================

    public List<DetalleVenta> obtenerDetallesPorVenta(Integer idVenta) {

        String sql = """
                SELECT
                    id_detalle,
                    id_venta,
                    id_producto,
                    cantidad,
                    precio_unitario,
                    subtotal
                FROM detalle_venta
                WHERE id_venta = ?
                ORDER BY id_detalle ASC
                """;

        return jdbcTemplate.query(sql, (rs, rowNum) -> {

            DetalleVenta detalle = new DetalleVenta();

            detalle.setIdDetalle(rs.getInt("id_detalle"));
            detalle.setIdVenta(rs.getInt("id_venta"));
            detalle.setIdProducto(rs.getInt("id_producto"));
            detalle.setCantidad(rs.getInt("cantidad"));
            detalle.setPrecioUnitario(
                    rs.getBigDecimal("precio_unitario")
            );
            detalle.setSubtotal(
                    rs.getBigDecimal("subtotal")
            );

            return detalle;

        }, idVenta);
    }

    // ==========================================================
    // CREAR VENTA
    // ==========================================================

    public Integer crearVenta(Venta venta) {

        String sql = """
                INSERT INTO venta (
                    total,
                    id_usuario,
                    id_cliente,
                    estado
                )
                VALUES (?, ?, ?, 'Pendiente')
                """;

        KeyHolder keyHolder = new GeneratedKeyHolder();

        jdbcTemplate.update(connection -> {

            PreparedStatement statement = connection.prepareStatement(
                    sql,
                    Statement.RETURN_GENERATED_KEYS
            );

            statement.setBigDecimal(1, venta.getTotal());
            statement.setInt(2, venta.getIdUsuario());

            if (venta.getIdCliente() == null) {
                statement.setNull(3, java.sql.Types.INTEGER);
            } else {
                statement.setInt(3, venta.getIdCliente());
            }

            return statement;

        }, keyHolder);

        Number key = keyHolder.getKey();

        if (key == null) {
            throw new IllegalStateException(
                    "No se pudo obtener el ID de la venta creada."
            );
        }

        return key.intValue();
    }

    // ==========================================================
    // CREAR DETALLE
    // ==========================================================

    public void crearDetalle(DetalleVenta detalle) {

        String sql = """
                INSERT INTO detalle_venta (
                    id_venta,
                    id_producto,
                    cantidad,
                    precio_unitario,
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?)
                """;

        jdbcTemplate.update(
                sql,
                detalle.getIdVenta(),
                detalle.getIdProducto(),
                detalle.getCantidad(),
                detalle.getPrecioUnitario(),
                detalle.getSubtotal()
        );
    }

    // ==========================================================
    // ACTUALIZAR VENTA PENDIENTE
    // ==========================================================

    public int actualizarVenta(Integer idVenta, Venta venta) {

        String sql = """
                UPDATE venta
                SET total = ?,
                    id_cliente = ?
                WHERE id_venta = ?
                  AND estado = 'Pendiente'
                """;

        return jdbcTemplate.update(
                sql,
                venta.getTotal(),
                venta.getIdCliente(),
                idVenta
        );
    }

    // ==========================================================
    // ELIMINAR DETALLES DE UNA VENTA
    // ==========================================================

    public int eliminarDetalles(Integer idVenta) {

        String sql = """
                DELETE FROM detalle_venta
                WHERE id_venta = ?
                """;

        return jdbcTemplate.update(sql, idVenta);
    }

    // ==========================================================
    // PAGAR VENTA
    // ==========================================================

    public int pagarVenta(Integer idVenta) {

        String sql = """
                UPDATE venta
                SET estado = 'Pagada'
                WHERE id_venta = ?
                  AND estado = 'Pendiente'
                """;

        return jdbcTemplate.update(sql, idVenta);
    }

    // ==========================================================
    // CANCELAR VENTA
    // ==========================================================

    public int cancelarVenta(Integer idVenta) {

        String sql = """
                UPDATE venta
                SET estado = 'Cancelada'
                WHERE id_venta = ?
                  AND estado = 'Pendiente'
                """;

        return jdbcTemplate.update(sql, idVenta);
    }
}