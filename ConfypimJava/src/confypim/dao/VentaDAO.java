package confypim.dao;

import confypim.config.Conexion;
import confypim.model.DetalleVenta;
import confypim.model.Venta;

import java.math.BigDecimal;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * ==========================================================
 * VentaDAO
 * ==========================================================
 *
 * Gestiona las operaciones CRUD y consultas relacionadas
 * con las tablas venta y detalle_venta.
 *
 * También controla las reglas de negocio relacionadas con
 * el inventario durante las operaciones de venta.
 *
 * ==========================================================
 */
public class VentaDAO {

    /*
     * ======================================================
     * CREAR VENTA
     * ======================================================
     */

    /**
     * Crea una nueva venta junto con sus detalles.
     *
     * La venta se crea inicialmente como Pendiente.
     *
     * Antes de crearla se comprueba que cada producto:
     *
     * - Exista.
     * - Esté activo.
     * - Tenga stock suficiente.
     *
     * El stock NO se descuenta al crear una venta pendiente.
     *
     * @param venta objeto Venta
     * @param detalles productos de la venta
     * @return ID de la venta creada
     */
    public int crearVenta(
            Venta venta,
            List<DetalleVenta> detalles)
            throws SQLException {

        if (detalles == null || detalles.isEmpty()) {

            throw new SQLException(
                    "La venta debe tener al menos un producto."
            );
        }

        validarDetalles(detalles);

        String sqlVenta = """
                INSERT INTO venta
                    (total, id_usuario, id_cliente, estado)
                VALUES
                    (?, ?, ?, 'Pendiente')
                """;

        /*
         * FOR UPDATE bloquea temporalmente el registro del
         * producto durante la transacción.
         *
         * Esto permite comprobar el stock de forma segura.
         */
        String sqlProducto = """
                SELECT
                    precio_unitario,
                    stock_disponible,
                    activo
                FROM producto
                WHERE id_producto = ?
                FOR UPDATE
                """;

        String sqlDetalle = """
                INSERT INTO detalle_venta
                    (id_venta, id_producto, cantidad,
                     precio_unitario, subtotal)
                VALUES
                    (?, ?, ?, ?, ?)
                """;

        Connection conexion = null;

        try {

            conexion = Conexion.conectar();
            conexion.setAutoCommit(false);

            BigDecimal total = BigDecimal.ZERO;

            /*
             * ==================================================
             * VALIDAR PRODUCTOS Y STOCK
             * ==================================================
             */

            for (DetalleVenta detalle : detalles) {

                try (
                        PreparedStatement sentenciaProducto =
                                conexion.prepareStatement(sqlProducto)
                ) {

                    sentenciaProducto.setInt(
                            1,
                            detalle.getIdProducto()
                    );

                    try (
                            ResultSet resultado =
                                    sentenciaProducto.executeQuery()
                    ) {

                        if (!resultado.next()) {

                            throw new SQLException(
                                    "El producto "
                                    + detalle.getIdProducto()
                                    + " no existe."
                            );
                        }

                        boolean activo =
                                resultado.getBoolean("activo");

                        if (!activo) {

                            throw new SQLException(
                                    "El producto "
                                    + detalle.getIdProducto()
                                    + " está inactivo."
                            );
                        }

                        BigDecimal precio =
                                resultado.getBigDecimal(
                                        "precio_unitario"
                                );

                        int stock =
                                resultado.getInt(
                                        "stock_disponible"
                                );

                        /*
                         * Comprobamos que la cantidad solicitada
                         * no supere el stock disponible.
                         */
                        if (detalle.getCantidad() > stock) {

                            throw new SQLException(
                                    "Stock insuficiente para el producto "
                                    + detalle.getIdProducto()
                                    + ". Disponible: "
                                    + stock
                                    + ", solicitado: "
                                    + detalle.getCantidad()
                            );
                        }

                        BigDecimal subtotal =
                                precio.multiply(
                                        BigDecimal.valueOf(
                                                detalle.getCantidad()
                                        )
                                );

                        /*
                         * Guardamos el precio actual como
                         * precio histórico de la venta.
                         */
                        detalle.setPrecioUnitario(precio);
                        detalle.setSubtotal(subtotal);

                        total = total.add(subtotal);
                    }
                }
            }

            /*
             * ==================================================
             * INSERTAR CABECERA DE LA VENTA
             * ==================================================
             */

            int idVenta;

            try (
                    PreparedStatement sentenciaVenta =
                            conexion.prepareStatement(
                                    sqlVenta,
                                    Statement.RETURN_GENERATED_KEYS
                            )
            ) {

                sentenciaVenta.setBigDecimal(1, total);

                sentenciaVenta.setInt(
                        2,
                        venta.getIdUsuario()
                );

                if (venta.getIdCliente() == null) {

                    sentenciaVenta.setNull(
                            3,
                            java.sql.Types.INTEGER
                    );

                } else {

                    sentenciaVenta.setInt(
                            3,
                            venta.getIdCliente()
                    );
                }

                sentenciaVenta.executeUpdate();

                try (
                        ResultSet claves =
                                sentenciaVenta.getGeneratedKeys()
                ) {

                    if (!claves.next()) {

                        throw new SQLException(
                                "No fue posible obtener el ID de la venta."
                        );
                    }

                    idVenta = claves.getInt(1);
                }
            }

            /*
             * ==================================================
             * INSERTAR DETALLES
             * ==================================================
             */

            try (
                    PreparedStatement sentenciaDetalle =
                            conexion.prepareStatement(sqlDetalle)
            ) {

                for (DetalleVenta detalle : detalles) {

                    sentenciaDetalle.setInt(
                            1,
                            idVenta
                    );

                    sentenciaDetalle.setInt(
                            2,
                            detalle.getIdProducto()
                    );

                    sentenciaDetalle.setInt(
                            3,
                            detalle.getCantidad()
                    );

                    sentenciaDetalle.setBigDecimal(
                            4,
                            detalle.getPrecioUnitario()
                    );

                    sentenciaDetalle.setBigDecimal(
                            5,
                            detalle.getSubtotal()
                    );

                    sentenciaDetalle.addBatch();
                }

                sentenciaDetalle.executeBatch();
            }

            /*
             * IMPORTANTE:
             * La venta queda Pendiente y el stock no cambia.
             */
            conexion.commit();

            return idVenta;

        } catch (SQLException e) {

            if (conexion != null) {
                conexion.rollback();
            }

            throw e;

        } finally {

            if (conexion != null) {

                conexion.setAutoCommit(true);
                conexion.close();
            }
        }
    }

    /*
     * ======================================================
     * READ - LISTAR VENTAS
     * ======================================================
     */

    /**
     * Lista las ventas activas del sistema.
     *
     * Las ventas Canceladas no aparecen.
     */
    public List<Venta> listarVentas() throws SQLException {

        List<Venta> ventas = new ArrayList<>();

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
                WHERE estado <> 'Cancelada'
                ORDER BY id_venta DESC
                """;

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql);
                ResultSet resultado =
                        sentencia.executeQuery()
        ) {

            while (resultado.next()) {

                ventas.add(mapearVenta(resultado));
            }
        }

        return ventas;
    }

    /**
     * Lista todas las ventas incluyendo las canceladas.
     */
    public List<Venta> listarTodasVentas()
            throws SQLException {

        List<Venta> ventas = new ArrayList<>();

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
                ORDER BY id_venta DESC
                """;

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql);
                ResultSet resultado =
                        sentencia.executeQuery()
        ) {

            while (resultado.next()) {

                ventas.add(mapearVenta(resultado));
            }
        }

        return ventas;
    }

    /**
     * Busca una venta por su ID.
     */
    public Venta obtenerVentaPorId(
            int idVenta)
            throws SQLException {

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

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql)
        ) {

            sentencia.setInt(1, idVenta);

            try (
                    ResultSet resultado =
                            sentencia.executeQuery()
            ) {

                if (resultado.next()) {

                    return mapearVenta(resultado);
                }
            }
        }

        return null;
    }

    /*
     * ======================================================
     * DETALLES
     * ======================================================
     */

    /**
     * Obtiene todos los detalles de una venta.
     */
    public List<DetalleVenta> obtenerDetallesVenta(
            int idVenta)
            throws SQLException {

        List<DetalleVenta> detalles =
                new ArrayList<>();

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
                ORDER BY id_detalle
                """;

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql)
        ) {

            sentencia.setInt(1, idVenta);

            try (
                    ResultSet resultado =
                            sentencia.executeQuery()
            ) {

                while (resultado.next()) {

                    detalles.add(
                            mapearDetalle(resultado)
                    );
                }
            }
        }

        return detalles;
    }

    /*
     * ======================================================
     * UPDATE - ACTUALIZAR VENTA PENDIENTE
     * ======================================================
     */

    /**
     * Actualiza completamente una venta pendiente.
     *
     * Los detalles anteriores son reemplazados.
     *
     * Antes de actualizar se comprueba que cada producto:
     *
     * - Exista.
     * - Esté activo.
     * - Tenga stock suficiente.
     *
     * El stock NO se descuenta al modificar una venta pendiente.
     */
    public void actualizarVenta(
            Venta venta,
            List<DetalleVenta> nuevosDetalles)
            throws SQLException {

        if (nuevosDetalles == null
                || nuevosDetalles.isEmpty()) {

            throw new SQLException(
                    "La venta debe tener al menos un producto."
            );
        }

        validarDetalles(nuevosDetalles);

        String sqlEstado = """
                SELECT estado
                FROM venta
                WHERE id_venta = ?
                FOR UPDATE
                """;

        String sqlEliminarDetalles = """
                DELETE FROM detalle_venta
                WHERE id_venta = ?
                """;

        String sqlActualizarVenta = """
                UPDATE venta
                SET
                    total = ?,
                    id_cliente = ?
                WHERE id_venta = ?
                """;

        /*
         * También obtenemos stock y bloqueamos el producto
         * durante la transacción.
         */
        String sqlProducto = """
                SELECT
                    precio_unitario,
                    stock_disponible,
                    activo
                FROM producto
                WHERE id_producto = ?
                FOR UPDATE
                """;

        String sqlDetalle = """
                INSERT INTO detalle_venta
                    (id_venta, id_producto, cantidad,
                     precio_unitario, subtotal)
                VALUES
                    (?, ?, ?, ?, ?)
                """;

        Connection conexion = null;

        try {

            conexion = Conexion.conectar();
            conexion.setAutoCommit(false);

            /*
             * ==================================================
             * VERIFICAR ESTADO DE LA VENTA
             * ==================================================
             */

            try (
                    PreparedStatement sentenciaEstado =
                            conexion.prepareStatement(sqlEstado)
            ) {

                sentenciaEstado.setInt(
                        1,
                        venta.getIdVenta()
                );

                try (
                        ResultSet resultado =
                                sentenciaEstado.executeQuery()
                ) {

                    if (!resultado.next()) {

                        throw new SQLException(
                                "La venta no existe."
                        );
                    }

                    String estado =
                            resultado.getString("estado");

                    if (!"Pendiente".equals(estado)) {

                        throw new SQLException(
                                "Solo se pueden editar ventas pendientes."
                        );
                    }
                }
            }

            BigDecimal total = BigDecimal.ZERO;

            /*
             * ==================================================
             * VALIDAR PRODUCTOS, PRECIOS Y STOCK
             * ==================================================
             */

            for (DetalleVenta detalle : nuevosDetalles) {

                try (
                        PreparedStatement sentenciaProducto =
                                conexion.prepareStatement(
                                        sqlProducto
                                )
                ) {

                    sentenciaProducto.setInt(
                            1,
                            detalle.getIdProducto()
                    );

                    try (
                            ResultSet resultado =
                                    sentenciaProducto.executeQuery()
                    ) {

                        if (!resultado.next()) {

                            throw new SQLException(
                                    "El producto "
                                    + detalle.getIdProducto()
                                    + " no existe."
                            );
                        }

                        boolean activo =
                                resultado.getBoolean("activo");

                        if (!activo) {

                            throw new SQLException(
                                    "El producto "
                                    + detalle.getIdProducto()
                                    + " está inactivo."
                            );
                        }

                        BigDecimal precio =
                                resultado.getBigDecimal(
                                        "precio_unitario"
                                );

                        int stock =
                                resultado.getInt(
                                        "stock_disponible"
                                );

                        /*
                         * Comprobación de stock durante
                         * la modificación.
                         */
                        if (detalle.getCantidad() > stock) {

                            throw new SQLException(
                                    "Stock insuficiente para el producto "
                                    + detalle.getIdProducto()
                                    + ". Disponible: "
                                    + stock
                                    + ", solicitado: "
                                    + detalle.getCantidad()
                            );
                        }

                        BigDecimal subtotal =
                                precio.multiply(
                                        BigDecimal.valueOf(
                                                detalle.getCantidad()
                                        )
                                );

                        detalle.setPrecioUnitario(precio);
                        detalle.setSubtotal(subtotal);

                        total = total.add(subtotal);
                    }
                }
            }

            /*
             * ==================================================
             * ELIMINAR DETALLES ANTERIORES
             * ==================================================
             */

            try (
                    PreparedStatement sentencia =
                            conexion.prepareStatement(
                                    sqlEliminarDetalles
                            )
            ) {

                sentencia.setInt(
                        1,
                        venta.getIdVenta()
                );

                sentencia.executeUpdate();
            }

            /*
             * ==================================================
             * ACTUALIZAR CABECERA
             * ==================================================
             */

            try (
                    PreparedStatement sentencia =
                            conexion.prepareStatement(
                                    sqlActualizarVenta
                            )
            ) {

                sentencia.setBigDecimal(
                        1,
                        total
                );

                if (venta.getIdCliente() == null) {

                    sentencia.setNull(
                            2,
                            java.sql.Types.INTEGER
                    );

                } else {

                    sentencia.setInt(
                            2,
                            venta.getIdCliente()
                    );
                }

                sentencia.setInt(
                        3,
                        venta.getIdVenta()
                );

                sentencia.executeUpdate();
            }

            /*
             * ==================================================
             * INSERTAR NUEVOS DETALLES
             * ==================================================
             */

            try (
                    PreparedStatement sentencia =
                            conexion.prepareStatement(
                                    sqlDetalle
                            )
            ) {

                for (DetalleVenta detalle : nuevosDetalles) {

                    sentencia.setInt(
                            1,
                            venta.getIdVenta()
                    );

                    sentencia.setInt(
                            2,
                            detalle.getIdProducto()
                    );

                    sentencia.setInt(
                            3,
                            detalle.getCantidad()
                    );

                    sentencia.setBigDecimal(
                            4,
                            detalle.getPrecioUnitario()
                    );

                    sentencia.setBigDecimal(
                            5,
                            detalle.getSubtotal()
                    );

                    sentencia.addBatch();
                }

                sentencia.executeBatch();
            }

            /*
             * La venta continúa Pendiente.
             * El stock no se modifica.
             */
            conexion.commit();

        } catch (SQLException e) {

            if (conexion != null) {
                conexion.rollback();
            }

            throw e;

        } finally {

            if (conexion != null) {

                conexion.setAutoCommit(true);
                conexion.close();
            }
        }
    }

    /*
     * ======================================================
     * PAGAR VENTA
     * ======================================================
     */

    /**
     * Cambia una venta pendiente a Pagada.
     *
     * Antes de pagar se vuelve a comprobar el stock.
     *
     * Si existe stock suficiente:
     *
     * - Se descuenta el inventario.
     * - La venta pasa a Pagada.
     *
     * Si no existe stock suficiente:
     *
     * - No se descuenta nada.
     * - La venta continúa Pendiente.
     */
    public void pagarVenta(
            int idVenta)
            throws SQLException {

        String sqlVenta = """
                SELECT estado
                FROM venta
                WHERE id_venta = ?
                FOR UPDATE
                """;

        String sqlDetalles = """
                SELECT
                    id_producto,
                    cantidad
                FROM detalle_venta
                WHERE id_venta = ?
                """;

        String sqlProducto = """
                SELECT
                    stock_disponible,
                    activo
                FROM producto
                WHERE id_producto = ?
                FOR UPDATE
                """;

        String sqlStock = """
                UPDATE producto
                SET stock_disponible =
                    stock_disponible - ?
                WHERE id_producto = ?
                """;

        String sqlPagar = """
                UPDATE venta
                SET
                    estado = 'Pagada',
                    fecha_venta = CURRENT_TIMESTAMP
                WHERE id_venta = ?
                """;

        Connection conexion = null;

        try {

            conexion = Conexion.conectar();
            conexion.setAutoCommit(false);

            /*
             * ==================================================
             * VERIFICAR VENTA
             * ==================================================
             */

            try (
                    PreparedStatement sentenciaVenta =
                            conexion.prepareStatement(sqlVenta)
            ) {

                sentenciaVenta.setInt(
                        1,
                        idVenta
                );

                try (
                        ResultSet resultado =
                                sentenciaVenta.executeQuery()
                ) {

                    if (!resultado.next()) {

                        throw new SQLException(
                                "La venta no existe."
                        );
                    }

                    String estado =
                            resultado.getString("estado");

                    if (!"Pendiente".equals(estado)) {

                        throw new SQLException(
                                "Solo se pueden pagar ventas pendientes."
                        );
                    }
                }
            }

            /*
             * ==================================================
             * OBTENER DETALLES
             * ==================================================
             */

            List<DetalleVenta> detalles =
                    new ArrayList<>();

            try (
                    PreparedStatement sentenciaDetalles =
                            conexion.prepareStatement(
                                    sqlDetalles
                            )
            ) {

                sentenciaDetalles.setInt(
                        1,
                        idVenta
                );

                try (
                        ResultSet resultado =
                                sentenciaDetalles.executeQuery()
                ) {

                    while (resultado.next()) {

                        DetalleVenta detalle =
                                new DetalleVenta();

                        detalle.setIdProducto(
                                resultado.getInt(
                                        "id_producto"
                                )
                        );

                        detalle.setCantidad(
                                resultado.getInt(
                                        "cantidad"
                                )
                        );

                        detalles.add(detalle);
                    }
                }
            }

            if (detalles.isEmpty()) {

                throw new SQLException(
                        "La venta no tiene productos."
                );
            }

            /*
             * ==================================================
             * VALIDAR STOCK ACTUAL
             * ==================================================
             */

            for (DetalleVenta detalle : detalles) {

                try (
                        PreparedStatement sentenciaProducto =
                                conexion.prepareStatement(
                                        sqlProducto
                                )
                ) {

                    sentenciaProducto.setInt(
                            1,
                            detalle.getIdProducto()
                    );

                    try (
                            ResultSet resultado =
                                    sentenciaProducto.executeQuery()
                    ) {

                        if (!resultado.next()) {

                            throw new SQLException(
                                    "El producto "
                                    + detalle.getIdProducto()
                                    + " no existe."
                            );
                        }

                        boolean activo =
                                resultado.getBoolean(
                                        "activo"
                                );

                        if (!activo) {

                            throw new SQLException(
                                    "El producto "
                                    + detalle.getIdProducto()
                                    + " está inactivo."
                            );
                        }

                        int stock =
                                resultado.getInt(
                                        "stock_disponible"
                                );

                        if (stock < detalle.getCantidad()) {

                            throw new SQLException(
                                    "Stock insuficiente para el producto "
                                    + detalle.getIdProducto()
                                    + ". Disponible: "
                                    + stock
                                    + ", solicitado: "
                                    + detalle.getCantidad()
                            );
                        }
                    }
                }
            }

            /*
             * ==================================================
             * DESCONTAR STOCK
             * ==================================================
             */

            try (
                    PreparedStatement sentenciaStock =
                            conexion.prepareStatement(
                                    sqlStock
                            )
            ) {

                for (DetalleVenta detalle : detalles) {

                    sentenciaStock.setInt(
                            1,
                            detalle.getCantidad()
                    );

                    sentenciaStock.setInt(
                            2,
                            detalle.getIdProducto()
                    );

                    sentenciaStock.addBatch();
                }

                sentenciaStock.executeBatch();
            }

            /*
             * ==================================================
             * CAMBIAR ESTADO
             * ==================================================
             */

            try (
                    PreparedStatement sentenciaPagar =
                            conexion.prepareStatement(
                                    sqlPagar
                            )
            ) {

                sentenciaPagar.setInt(
                        1,
                        idVenta
                );

                sentenciaPagar.executeUpdate();
            }

            conexion.commit();

        } catch (SQLException e) {

            if (conexion != null) {
                conexion.rollback();
            }

            throw e;

        } finally {

            if (conexion != null) {

                conexion.setAutoCommit(true);
                conexion.close();
            }
        }
    }

    /*
     * ======================================================
     * CANCELAR VENTA
     * ======================================================
     */

    /**
     * Cancela una venta pendiente.
     *
     * No modifica el stock porque una venta pendiente
     * nunca había descontado inventario.
     */
    public void cancelarVenta(
            int idVenta)
            throws SQLException {

        String sql = """
                UPDATE venta
                SET estado = 'Cancelada'
                WHERE id_venta = ?
                AND estado = 'Pendiente'
                """;

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql)
        ) {

            sentencia.setInt(
                    1,
                    idVenta
            );

            int filas =
                    sentencia.executeUpdate();

            if (filas == 0) {

                throw new SQLException(
                        "La venta no existe o no está pendiente."
                );
            }
        }
    }

    /*
     * ======================================================
     * CONSULTAR STOCK
     * ======================================================
     */

    /**
     * Obtiene el stock actual de un producto.
     *
     * Este método se utiliza principalmente para comprobar
     * el comportamiento del inventario durante las pruebas.
     *
     * @param idProducto identificador del producto
     * @return stock disponible
     */
    public int obtenerStockProducto(
            int idProducto)
            throws SQLException {

        String sql = """
                SELECT stock_disponible
                FROM producto
                WHERE id_producto = ?
                """;

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql)
        ) {

            sentencia.setInt(
                    1,
                    idProducto
            );

            try (
                    ResultSet resultado =
                            sentencia.executeQuery()
            ) {

                if (!resultado.next()) {

                    throw new SQLException(
                            "El producto "
                            + idProducto
                            + " no existe."
                    );
                }

                return resultado.getInt(
                        "stock_disponible"
                );
            }
        }
    }

    /*
     * ======================================================
     * ESTADÍSTICAS
     * ======================================================
     */

    /**
     * Obtiene la cantidad de ventas pagadas.
     */
    public int contarVentasPagadas()
            throws SQLException {

        String sql = """
                SELECT COUNT(*)
                FROM venta
                WHERE estado = 'Pagada'
                """;

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql);
                ResultSet resultado =
                        sentencia.executeQuery()
        ) {

            if (resultado.next()) {
                return resultado.getInt(1);
            }
        }

        return 0;
    }

    /**
     * Obtiene el dinero total de ventas pagadas.
     */
    public BigDecimal obtenerTotalVentasPagadas()
            throws SQLException {

        String sql = """
                SELECT COALESCE(SUM(total), 0)
                FROM venta
                WHERE estado = 'Pagada'
                """;

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql);
                ResultSet resultado =
                        sentencia.executeQuery()
        ) {

            if (resultado.next()) {
                return resultado.getBigDecimal(1);
            }
        }

        return BigDecimal.ZERO;
    }

    /**
     * Obtiene la cantidad total de productos vendidos.
     */
    public int contarProductosVendidos()
            throws SQLException {

        String sql = """
                SELECT COALESCE(SUM(d.cantidad), 0)
                FROM detalle_venta d
                INNER JOIN venta v
                    ON d.id_venta = v.id_venta
                WHERE v.estado = 'Pagada'
                """;

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql);
                ResultSet resultado =
                        sentencia.executeQuery()
        ) {

            if (resultado.next()) {
                return resultado.getInt(1);
            }
        }

        return 0;
    }

    /**
     * Obtiene la cantidad de productos diferentes vendidos.
     */
    public int contarProductosDiferentes()
            throws SQLException {

        String sql = """
                SELECT COUNT(DISTINCT d.id_producto)
                FROM detalle_venta d
                INNER JOIN venta v
                    ON d.id_venta = v.id_venta
                WHERE v.estado = 'Pagada'
                """;

        try (
                Connection conexion = Conexion.conectar();
                PreparedStatement sentencia =
                        conexion.prepareStatement(sql);
                ResultSet resultado =
                        sentencia.executeQuery()
        ) {

            if (resultado.next()) {
                return resultado.getInt(1);
            }
        }

        return 0;
    }

    /*
     * ======================================================
     * MÉTODOS AUXILIARES
     * ======================================================
     */

    /**
     * Convierte un ResultSet en un objeto Venta.
     */
    private Venta mapearVenta(
            ResultSet resultado)
            throws SQLException {

        Venta venta = new Venta();

        venta.setIdVenta(
                resultado.getInt("id_venta")
        );

        if (resultado.getTimestamp("fecha_venta") != null) {

            venta.setFechaVenta(
                    resultado.getTimestamp(
                            "fecha_venta"
                    ).toLocalDateTime()
            );
        }

        venta.setTotal(
                resultado.getBigDecimal("total")
        );

        venta.setIdUsuario(
                resultado.getInt("id_usuario")
        );

        int idCliente =
                resultado.getInt("id_cliente");

        if (resultado.wasNull()) {

            venta.setIdCliente(null);

        } else {

            venta.setIdCliente(idCliente);
        }

        if (resultado.getTimestamp("fecha_creacion") != null) {

            venta.setFechaCreacion(
                    resultado.getTimestamp(
                            "fecha_creacion"
                    ).toLocalDateTime()
            );
        }

        venta.setEstado(
                resultado.getString("estado")
        );

        return venta;
    }

    /**
     * Convierte un ResultSet en un objeto DetalleVenta.
     */
    private DetalleVenta mapearDetalle(
            ResultSet resultado)
            throws SQLException {

        DetalleVenta detalle =
                new DetalleVenta();

        detalle.setIdDetalle(
                resultado.getInt("id_detalle")
        );

        detalle.setIdVenta(
                resultado.getInt("id_venta")
        );

        detalle.setIdProducto(
                resultado.getInt("id_producto")
        );

        detalle.setCantidad(
                resultado.getInt("cantidad")
        );

        detalle.setPrecioUnitario(
                resultado.getBigDecimal(
                        "precio_unitario"
                )
        );

        detalle.setSubtotal(
                resultado.getBigDecimal(
                        "subtotal"
                )
        );

        return detalle;
    }

    /**
     * Valida que los detalles de una venta sean correctos.
     */
    private void validarDetalles(
            List<DetalleVenta> detalles)
            throws SQLException {

        Set<Integer> productos =
                new HashSet<>();

        for (DetalleVenta detalle : detalles) {

            if (detalle.getCantidad() <= 0) {

                throw new SQLException(
                        "La cantidad debe ser mayor que cero."
                );
            }

            if (detalle.getIdProducto() <= 0) {

                throw new SQLException(
                        "El ID del producto no es válido."
                );
            }

            if (!productos.add(
                    detalle.getIdProducto())) {

                throw new SQLException(
                        "No se permite repetir el mismo producto "
                        + "en una venta."
                );
            }
        }
    }
}