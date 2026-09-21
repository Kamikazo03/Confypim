package com.confypim.confypim_java_spring.repository;

import java.util.List;

import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Repository;

import com.confypim.confypim_java_spring.model.Producto;

@Repository
public class ProductoRepository {

    private final JdbcTemplate jdbcTemplate;

    public ProductoRepository(JdbcTemplate jdbcTemplate) {
        this.jdbcTemplate = jdbcTemplate;
    }

    public List<Producto> listarProductosActivos() {

        String sql = """
                SELECT
                    id_producto,
                    nombre,
                    precio_unitario,
                    stock_disponible,
                    activo
                FROM producto
                WHERE activo = 1
                ORDER BY nombre ASC
                """;

        return jdbcTemplate.query(
                sql,
                (resultado, fila) -> {

                    Producto producto = new Producto();

                    producto.setIdProducto(
                            resultado.getInt("id_producto")
                    );

                    producto.setNombre(
                            resultado.getString("nombre")
                    );

                    producto.setPrecioUnitario(
                            resultado.getBigDecimal("precio_unitario")
                    );

                    producto.setStockDisponible(
                            resultado.getInt("stock_disponible")
                    );

                    producto.setActivo(
                            resultado.getBoolean("activo")
                    );

                    return producto;
                }
        );
    }
    
    // ==========================================================
    // OBTENER PRODUCTO POR ID
    // ==========================================================

    public Producto obtenerProductoPorId(Integer idProducto) {

        String sql = """
                SELECT
                    id_producto,
                    nombre,
                    precio_unitario,
                    stock_disponible,
                    activo
                FROM producto
                WHERE id_producto = ?
                """;

        List<Producto> productos = jdbcTemplate.query(
                sql,
                (resultado, fila) -> {

                    Producto producto = new Producto();

                    producto.setIdProducto(
                            resultado.getInt("id_producto")
                    );

                    producto.setNombre(
                            resultado.getString("nombre")
                    );

                    producto.setPrecioUnitario(
                            resultado.getBigDecimal("precio_unitario")
                    );

                    producto.setStockDisponible(
                            resultado.getInt("stock_disponible")
                    );

                    producto.setActivo(
                            resultado.getBoolean("activo")
                    );

                    return producto;
                },
                idProducto
        );

        return productos.isEmpty() ? null : productos.get(0);
    }

    // ==========================================================
    // DESCONTAR STOCK
    // ==========================================================

    public int descontarStock(
            Integer idProducto,
            Integer cantidad
    ) {

        String sql = """
                UPDATE producto
                SET stock_disponible = stock_disponible - ?
                WHERE id_producto = ?
                  AND activo = 1
                  AND stock_disponible >= ?
                """;

        return jdbcTemplate.update(
                sql,
                cantidad,
                idProducto,
                cantidad
        );
    }
}