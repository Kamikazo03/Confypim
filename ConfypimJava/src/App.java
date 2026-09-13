import confypim.controller.VentaController;
import confypim.model.DetalleVenta;
import confypim.model.Venta;

import java.math.BigDecimal;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;
import java.util.Scanner;

/**
 * ==========================================================
 * App
 * ==========================================================
 *
 * Punto de entrada de la aplicación Java.
 *
 * Permite probar las operaciones CRUD de ventas mediante
 * un menú de consola.
 *
 * ==========================================================
 */
public class App {

    private static final Scanner SCANNER =
            new Scanner(System.in);

    private static final VentaController CONTROLLER =
            new VentaController();

    /*
     * Usuario utilizado para las pruebas.
     *
     * Se puede cambiar posteriormente cuando se implemente
     * autenticación.
     */
    private static final int USUARIO_PRUEBA = 1;

    public static void main(String[] args) {

        int opcion;

        do {

            mostrarMenu();

            opcion = leerEntero(
                    "Seleccione una opción: "
            );

            try {

                switch (opcion) {

                    case 1 -> listarVentas();

                    case 2 -> buscarVenta();

                    case 3 -> verDetalles();

                    case 4 -> crearVenta();

                    case 5 -> actualizarVenta();

                    case 6 -> pagarVenta();

                    case 7 -> cancelarVenta();

                    case 8 -> mostrarEstadisticas();

                    case 9 -> listarTodasLasVentas();

                    case 0 ->
                            System.out.println(
                                    "\nAplicación finalizada."
                            );

                    default ->
                            System.out.println(
                                    "\nOpción no válida."
                            );
                }

            } catch (SQLException e) {

                System.out.println(
                        "\nERROR DE BASE DE DATOS:"
                );

                System.out.println(
                        e.getMessage()
                );
            }

        } while (opcion != 0);

        SCANNER.close();
    }

    /**
     * Muestra el menú principal.
     */
    private static void mostrarMenu() {

        System.out.println();
        System.out.println(
                "=========================================="
        );
        System.out.println(
                "       CONFYPIM - CRUD DE VENTAS"
        );
        System.out.println(
                "=========================================="
        );
        System.out.println(
                "1. Listar ventas"
        );
        System.out.println(
                "2. Buscar venta por ID"
        );
        System.out.println(
                "3. Ver detalle de una venta"
        );
        System.out.println(
                "4. Crear venta"
        );
        System.out.println(
                "5. Actualizar venta pendiente"
        );
        System.out.println(
                "6. Pagar venta"
        );
        System.out.println(
                "7. Cancelar venta pendiente"
        );
        System.out.println(
                "8. Ver estadísticas"
        );
        System.out.println(
                "9. Ver todas las ventas"
        );
        System.out.println(
                "0. Salir"
        );
        System.out.println(
                "=========================================="
        );
    }

    /**
     * Lista las ventas que no están canceladas.
     */
    private static void listarVentas()
            throws SQLException {

        System.out.println();
        System.out.println(
                "========== LISTADO DE VENTAS =========="
        );

        List<Venta> ventas =
                CONTROLLER.listarVentas();

        if (ventas.isEmpty()) {

            System.out.println(
                    "No existen ventas pendientes o pagadas."
            );

            return;
        }

        for (Venta venta : ventas) {

            System.out.println(
                    venta
            );
        }
    }

    /**
     * Busca una venta por ID.
     */
    private static void buscarVenta()
            throws SQLException {

        int idVenta = leerEntero(
                "\nIngrese el ID de la venta: "
        );

        Venta venta =
                CONTROLLER.obtenerVenta(idVenta);

        if (venta == null) {

            System.out.println(
                    "La venta no existe."
            );

            return;
        }

        System.out.println();
        System.out.println(
                "========== VENTA ENCONTRADA =========="
        );

        System.out.println(
                venta
        );
    }

    /**
     * Muestra los productos de una venta.
     */
    private static void verDetalles()
            throws SQLException {

        int idVenta = leerEntero(
                "\nIngrese el ID de la venta: "
        );

        Venta venta =
                CONTROLLER.obtenerVenta(idVenta);

        if (venta == null) {

            System.out.println(
                    "La venta no existe."
            );

            return;
        }

        List<DetalleVenta> detalles =
                CONTROLLER.obtenerDetalles(idVenta);

        System.out.println();
        System.out.println(
                "========== DETALLE DE VENTA =========="
        );

        System.out.println(
                venta
        );

        System.out.println();

        for (DetalleVenta detalle : detalles) {

            System.out.println(
                    detalle
            );
        }
    }

    /**
     * Crea una venta nueva.
     */
    private static void crearVenta()
            throws SQLException {

        System.out.println();
        System.out.println(
                "========== CREAR VENTA =========="
        );

        Venta venta = new Venta();

        venta.setIdUsuario(
                USUARIO_PRUEBA
        );

        System.out.println(
                "Usuario responsable: "
                + USUARIO_PRUEBA
        );

        System.out.println(
                "ID cliente (Enter = Cliente General): "
        );

        String clienteTexto =
                SCANNER.nextLine().trim();

        if (clienteTexto.isEmpty()) {

            venta.setIdCliente(null);

        } else {

            venta.setIdCliente(
                    Integer.parseInt(clienteTexto)
            );
        }

        List<DetalleVenta> detalles =
                new ArrayList<>();

        int cantidadProductos = leerEntero(
                "¿Cuántos productos tendrá la venta?: "
        );

        if (cantidadProductos <= 0) {

            System.out.println(
                    "La venta debe tener al menos un producto."
            );

            return;
        }

        for (int i = 1;
             i <= cantidadProductos;
             i++) {

            System.out.println();
            System.out.println(
                    "Producto #" + i
            );

            int idProducto = leerEntero(
                    "ID del producto: "
            );

            int cantidad = leerEntero(
                    "Cantidad: "
            );

            DetalleVenta detalle =
                    new DetalleVenta();

            detalle.setIdProducto(
                    idProducto
            );

            detalle.setCantidad(
                    cantidad
            );

            detalles.add(detalle);
        }

        int idVenta =
                CONTROLLER.crearVenta(
                        venta,
                        detalles
                );

        System.out.println();
        System.out.println(
                "Venta creada correctamente."
        );

        System.out.println(
                "ID generado por MySQL: "
                + idVenta
        );

        Venta ventaCreada =
                CONTROLLER.obtenerVenta(idVenta);

        System.out.println(
                ventaCreada
        );
    }

    /**
     * Actualiza una venta pendiente.
     */
    private static void actualizarVenta()
            throws SQLException {

        System.out.println();
        System.out.println(
                "========== ACTUALIZAR VENTA =========="
        );

        int idVenta = leerEntero(
                "ID de la venta: "
        );

        Venta venta =
                CONTROLLER.obtenerVenta(idVenta);

        if (venta == null) {

            System.out.println(
                    "La venta no existe."
            );

            return;
        }

        if (!"Pendiente".equals(
                venta.getEstado())) {

            System.out.println(
                    "La venta no está pendiente."
            );

            System.out.println(
                    "Solo las ventas pendientes pueden editarse."
            );

            return;
        }

        System.out.println(
                "Venta actual:"
        );

        System.out.println(
                venta
        );

        System.out.println();

        String clienteTexto = SCANNER.nextLine();

        System.out.println(
                "Nuevo ID cliente "
                + "(Enter = mantener cliente actual): "
        );

        clienteTexto =
                SCANNER.nextLine().trim();

        if (!clienteTexto.isEmpty()) {

            venta.setIdCliente(
                    Integer.parseInt(clienteTexto)
            );
        }

        int cantidadProductos = leerEntero(
                "Nueva cantidad de productos: "
        );

        List<DetalleVenta> detalles =
                new ArrayList<>();

        for (int i = 1;
             i <= cantidadProductos;
             i++) {

            System.out.println();
            System.out.println(
                    "Producto #" + i
            );

            int idProducto = leerEntero(
                    "ID del producto: "
            );

            int cantidad = leerEntero(
                    "Cantidad: "
            );

            DetalleVenta detalle =
                    new DetalleVenta();

            detalle.setIdProducto(
                    idProducto
            );

            detalle.setCantidad(
                    cantidad
            );

            detalles.add(detalle);
        }

        CONTROLLER.actualizarVenta(
                venta,
                detalles
        );

        System.out.println();
        System.out.println(
                "Venta actualizada correctamente."
        );
    }

    /**
     * Paga una venta pendiente.
     */
    private static void pagarVenta()
            throws SQLException {

        System.out.println();
        System.out.println(
                "========== PAGAR VENTA =========="
        );

        int idVenta = leerEntero(
                "ID de la venta: "
        );

        CONTROLLER.pagarVenta(idVenta);

        System.out.println();
        System.out.println(
                "Venta pagada correctamente."
        );

        System.out.println(
                "El stock fue actualizado."
        );
    }

    /**
     * Cancela una venta pendiente.
     */
    private static void cancelarVenta()
            throws SQLException {

        System.out.println();
        System.out.println(
                "========== CANCELAR VENTA =========="
        );

        int idVenta = leerEntero(
                "ID de la venta: "
        );

        CONTROLLER.cancelarVenta(idVenta);

        System.out.println();
        System.out.println(
                "Venta cancelada correctamente."
        );

        System.out.println(
                "La venta ya no aparecerá en el listado principal."
        );
    }

    /**
     * Muestra las estadísticas de ventas pagadas.
     */
    private static void mostrarEstadisticas()
            throws SQLException {

        int ventasPagadas =
                CONTROLLER.contarVentasPagadas();

        BigDecimal total =
                CONTROLLER.obtenerTotalVentasPagadas();

        int productosVendidos =
                CONTROLLER.contarProductosVendidos();

        int productosDiferentes =
                CONTROLLER.contarProductosDiferentes();

        System.out.println();
        System.out.println(
                "========== ESTADÍSTICAS =========="
        );

        System.out.println(
                "Ventas pagadas: "
                + ventasPagadas
        );

        System.out.println(
                "Productos vendidos: "
                + productosVendidos
        );

        System.out.println(
                "Productos diferentes: "
                + productosDiferentes
        );

        System.out.println(
                "Ingresos por ventas: $"
                + total
        );

        if (ventasPagadas > 0) {

            BigDecimal promedio =
                    total.divide(
                            BigDecimal.valueOf(
                                    ventasPagadas
                            ),
                            2,
                            java.math.RoundingMode.HALF_UP
                    );

            System.out.println(
                    "Promedio por venta: $"
                    + promedio
            );
        }
    }

    /**
     * Lista todas las ventas incluyendo canceladas.
     */
    private static void listarTodasLasVentas()
            throws SQLException {

        System.out.println();
        System.out.println(
                "========== TODAS LAS VENTAS =========="
        );

        List<Venta> ventas =
                CONTROLLER.listarTodasVentas();

        if (ventas.isEmpty()) {

            System.out.println(
                    "No existen ventas."
            );

            return;
        }

        for (Venta venta : ventas) {

            System.out.println(
                    venta
            );
        }
    }

    /**
     * Lee un número entero desde consola.
     */
    private static int leerEntero(
            String mensaje) {

        while (true) {

            try {

                System.out.print(mensaje);

                String texto =
                        SCANNER.nextLine().trim();

                return Integer.parseInt(texto);

            } catch (NumberFormatException e) {

                System.out.println(
                        "Ingrese un número entero válido."
                );
            }
        }
    }
}