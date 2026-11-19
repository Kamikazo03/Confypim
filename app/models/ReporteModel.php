<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

/**
 * ==========================================================
 * ReporteModel
 * ==========================================================
 * Centraliza las consultas financieras y el almacenamiento
 * de reportes históricos del usuario autenticado.
 *
 * Fuentes:
 * - venta: ventas que se encuentran Pagadas.
 * - ingreso: ingresos adicionales, excepto la categoría
 *   Ventas (ID 1), para evitar duplicar las ventas.
 * - gasto: todos los gastos registrados.
 * - reporte_ganancias: fotografías históricas solicitadas
 *   manualmente por el usuario.
 * ==========================================================
 */

class ReporteModel
{
    private PDO $db;

    /** La categoría Ventas se obtiene directamente de la tabla venta. */
    private const ID_CATEGORIA_INGRESO_VENTAS = 1;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    private function obtenerUsuario(): int
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException('No existe un usuario autenticado.');
        }

        return $idUsuario;
    }

    /**
     * Obtener los indicadores principales del período.
     */
    private function obtenerTotales(string $inicio, string $fin): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sqlVentas = "
            SELECT
                COUNT(*) AS cantidad,
                COALESCE(SUM(total), 0) AS total
            FROM venta
            WHERE id_usuario = :id_usuario
              AND estado = 'Pagada'
              AND fecha_venta >= :inicio
              AND fecha_venta < DATE_ADD(:fin, INTERVAL 1 DAY)
        ";

        $stmt = $this->db->prepare($sqlVentas);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':inicio' => $inicio,
            ':fin' => $fin
        ]);
        $ventas = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $sqlIngresos = "
            SELECT
                COUNT(*) AS cantidad,
                COALESCE(SUM(monto), 0) AS total
            FROM ingreso
            WHERE id_usuario = :id_usuario
              AND id_categoria_ingreso <> :categoria_ventas
              AND fecha_ingreso >= :inicio
              AND fecha_ingreso < DATE_ADD(:fin, INTERVAL 1 DAY)
        ";

        $stmt = $this->db->prepare($sqlIngresos);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':categoria_ventas' => self::ID_CATEGORIA_INGRESO_VENTAS,
            ':inicio' => $inicio,
            ':fin' => $fin
        ]);
        $ingresosAdicionales = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $sqlGastos = "
            SELECT
                COUNT(*) AS cantidad,
                COALESCE(SUM(monto), 0) AS total
            FROM gasto
            WHERE id_usuario = :id_usuario
              AND fecha >= :inicio
              AND fecha < DATE_ADD(:fin, INTERVAL 1 DAY)
        ";

        $stmt = $this->db->prepare($sqlGastos);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':inicio' => $inicio,
            ':fin' => $fin
        ]);
        $gastos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalVentas = (float) ($ventas['total'] ?? 0);
        $totalIngresosAdicionales = (float) ($ingresosAdicionales['total'] ?? 0);
        $totalIngresos = $totalVentas + $totalIngresosAdicionales;
        $totalGastos = (float) ($gastos['total'] ?? 0);

        return [
            'ventas' => [
                'cantidad' => (int) ($ventas['cantidad'] ?? 0),
                'total' => $totalVentas
            ],
            'ingresos_adicionales' => [
                'cantidad' => (int) ($ingresosAdicionales['cantidad'] ?? 0),
                'total' => $totalIngresosAdicionales
            ],
            'ingresos' => $totalIngresos,
            'gastos' => $totalGastos,
            'balance' => $totalIngresos - $totalGastos,
            'cantidad_gastos' => (int) ($gastos['cantidad'] ?? 0)
        ];
    }

    /**
     * Obtener todos los datos que alimentan las pestañas.
     */
    public function obtenerDatosPeriodo(string $inicio, string $fin): array
    {
        return [
            'periodo' => [
                'inicio' => $inicio,
                'fin' => $fin
            ],
            'resumen' => $this->obtenerTotales($inicio, $fin),
            'ingresos' => $this->obtenerIngresosDetalle($inicio, $fin),
            'gastos' => $this->obtenerGastosDetalle($inicio, $fin),
            'evolucion' => $this->obtenerEvolucion($inicio, $fin),
            'categorias_gasto' => $this->obtenerGastosPorCategoria($inicio, $fin),
            'categorias_ingreso' => $this->obtenerIngresosPorCategoria($inicio, $fin),
            'productos_unidades' => $this->obtenerProductosMasVendidos($inicio, $fin, 'unidades'),
            'productos_ingresos' => $this->obtenerProductosMasVendidos($inicio, $fin, 'ingresos')
        ];
    }

    /**
     * Listar las ventas pagadas y los ingresos adicionales.
     */
    private function obtenerIngresosDetalle(string $inicio, string $fin): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sqlVentas = "
            SELECT
                v.id_venta AS id,
                'Venta' AS tipo,
                CONCAT('Venta #', v.numero_venta) AS concepto,
                'Ventas' AS categoria,
                v.fecha_venta AS fecha,
                v.total AS monto
            FROM venta v
            WHERE v.id_usuario = :id_usuario
              AND v.estado = 'Pagada'
              AND v.fecha_venta >= :inicio
              AND v.fecha_venta < DATE_ADD(:fin, INTERVAL 1 DAY)
        ";

        $stmt = $this->db->prepare($sqlVentas);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':inicio' => $inicio,
            ':fin' => $fin
        ]);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlAdicionales = "
            SELECT
                i.id_ingreso AS id,
                'Adicional' AS tipo,
                CONCAT('Ingreso adicional #', i.id_ingreso) AS concepto,
                ci.nombre AS categoria,
                i.fecha_ingreso AS fecha,
                i.monto
            FROM ingreso i
            INNER JOIN categoria_ingreso ci
                ON ci.id_categoria_ingreso = i.id_categoria_ingreso
            WHERE i.id_usuario = :id_usuario
              AND i.id_categoria_ingreso <> :categoria_ventas
              AND i.fecha_ingreso >= :inicio
              AND i.fecha_ingreso < DATE_ADD(:fin, INTERVAL 1 DAY)
        ";

        $stmt = $this->db->prepare($sqlAdicionales);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':categoria_ventas' => self::ID_CATEGORIA_INGRESO_VENTAS,
            ':inicio' => $inicio,
            ':fin' => $fin
        ]);
        $adicionales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = array_merge($ventas, $adicionales);

        usort($resultado, static function (array $a, array $b): int {
            return strcmp((string) $b['fecha'], (string) $a['fecha']);
        });

        foreach ($resultado as &$fila) {
            $fila['id'] = (int) $fila['id'];
            $fila['monto'] = (float) $fila['monto'];
        }
        unset($fila);

        return $resultado;
    }

    /**
     * Listar los gastos registrados en el período.
     */
    private function obtenerGastosDetalle(string $inicio, string $fin): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                g.id_gasto AS id,
                g.descripcion,
                g.monto,
                g.fecha,
                cg.nombre AS categoria
            FROM gasto g
            INNER JOIN categoria_gasto cg
                ON cg.id_categoria_gasto = g.id_categoria_gasto
            WHERE g.id_usuario = :id_usuario
              AND g.fecha >= :inicio
              AND g.fecha < DATE_ADD(:fin, INTERVAL 1 DAY)
            ORDER BY g.fecha DESC, g.id_gasto DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':inicio' => $inicio,
            ':fin' => $fin
        ]);

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $fila['id'] = (int) $fila['id'];
            $fila['monto'] = (float) $fila['monto'];
        }
        unset($fila);

        return $filas;
    }

    /**
     * Evolución diaria de ingresos y gastos.
     */
    private function obtenerEvolucion(string $inicio, string $fin): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                movimientos.fecha,
                SUM(movimientos.ingresos) AS ingresos,
                SUM(movimientos.gastos) AS gastos
            FROM (
                SELECT
                    DATE(v.fecha_venta) AS fecha,
                    SUM(v.total) AS ingresos,
                    0 AS gastos
                FROM venta v
                WHERE v.id_usuario = :usuario_venta
                  AND v.estado = 'Pagada'
                  AND v.fecha_venta >= :inicio_venta
                  AND v.fecha_venta < DATE_ADD(:fin_venta, INTERVAL 1 DAY)
                GROUP BY DATE(v.fecha_venta)

                UNION ALL

                SELECT
                    DATE(i.fecha_ingreso) AS fecha,
                    SUM(i.monto) AS ingresos,
                    0 AS gastos
                FROM ingreso i
                WHERE i.id_usuario = :usuario_ingreso
                  AND i.id_categoria_ingreso <> :categoria_ventas
                  AND i.fecha_ingreso >= :inicio_ingreso
                  AND i.fecha_ingreso < DATE_ADD(:fin_ingreso, INTERVAL 1 DAY)
                GROUP BY DATE(i.fecha_ingreso)

                UNION ALL

                SELECT
                    DATE(g.fecha) AS fecha,
                    0 AS ingresos,
                    SUM(g.monto) AS gastos
                FROM gasto g
                WHERE g.id_usuario = :usuario_gasto
                  AND g.fecha >= :inicio_gasto
                  AND g.fecha < DATE_ADD(:fin_gasto, INTERVAL 1 DAY)
                GROUP BY DATE(g.fecha)
            ) AS movimientos
            GROUP BY movimientos.fecha
            ORDER BY movimientos.fecha ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario_venta' => $idUsuario,
            ':inicio_venta' => $inicio,
            ':fin_venta' => $fin,
            ':usuario_ingreso' => $idUsuario,
            ':categoria_ventas' => self::ID_CATEGORIA_INGRESO_VENTAS,
            ':inicio_ingreso' => $inicio,
            ':fin_ingreso' => $fin,
            ':usuario_gasto' => $idUsuario,
            ':inicio_gasto' => $inicio,
            ':fin_gasto' => $fin
        ]);

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $balanceAcumulado = 0.0;

        foreach ($filas as &$fila) {
            $fila['ingresos'] = (float) $fila['ingresos'];
            $fila['gastos'] = (float) $fila['gastos'];
            $fila['balance'] = $fila['ingresos'] - $fila['gastos'];
            $balanceAcumulado += $fila['balance'];
            $fila['balance_acumulado'] = $balanceAcumulado;
        }
        unset($fila);

        return $filas;
    }

    /**
     * Distribución de gastos por categoría.
     */
    private function obtenerGastosPorCategoria(string $inicio, string $fin): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                cg.id_categoria_gasto AS id,
                cg.nombre AS categoria,
                COALESCE(SUM(g.monto), 0) AS total,
                COUNT(g.id_gasto) AS cantidad
            FROM categoria_gasto cg
            LEFT JOIN gasto g
                ON g.id_categoria_gasto = cg.id_categoria_gasto
               AND g.id_usuario = :id_usuario
               AND g.fecha >= :inicio
               AND g.fecha < DATE_ADD(:fin, INTERVAL 1 DAY)
            GROUP BY cg.id_categoria_gasto, cg.nombre
            ORDER BY total DESC, cg.nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':inicio' => $inicio,
            ':fin' => $fin
        ]);

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $fila['id'] = (int) $fila['id'];
            $fila['total'] = (float) $fila['total'];
            $fila['cantidad'] = (int) $fila['cantidad'];
        }
        unset($fila);

        return $filas;
    }

    /**
     * Distribución de ingresos adicionales por categoría.
     * Las ventas se muestran como un tipo independiente.
     */
    private function obtenerIngresosPorCategoria(string $inicio, string $fin): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                ci.id_categoria_ingreso AS id,
                ci.nombre AS categoria,
                COALESCE(SUM(i.monto), 0) AS total,
                COUNT(i.id_ingreso) AS cantidad
            FROM categoria_ingreso ci
            LEFT JOIN ingreso i
                ON i.id_categoria_ingreso = ci.id_categoria_ingreso
               AND i.id_usuario = :id_usuario
               AND i.id_categoria_ingreso <> :categoria_ventas
               AND i.fecha_ingreso >= :inicio
               AND i.fecha_ingreso < DATE_ADD(:fin, INTERVAL 1 DAY)
            WHERE ci.id_categoria_ingreso <> :categoria_ventas_filtro
            GROUP BY ci.id_categoria_ingreso, ci.nombre
            ORDER BY total DESC, ci.nombre ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':categoria_ventas' => self::ID_CATEGORIA_INGRESO_VENTAS,
            ':inicio' => $inicio,
            ':fin' => $fin,
            ':categoria_ventas_filtro' => self::ID_CATEGORIA_INGRESO_VENTAS
        ]);

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $fila['id'] = (int) $fila['id'];
            $fila['total'] = (float) $fila['total'];
            $fila['cantidad'] = (int) $fila['cantidad'];
        }
        unset($fila);

        /*
         * Las ventas pagadas se consultan desde la tabla venta.
         * Se agregan aquí para que la distribución represente
         * todas las fuentes de ingreso sin duplicar registros
         * que eventualmente tengan la categoría Ventas en ingreso.
         */
        $sqlVentas = "
            SELECT
                COUNT(*) AS cantidad,
                COALESCE(SUM(total), 0) AS total
            FROM venta
            WHERE id_usuario = :id_usuario
              AND estado = 'Pagada'
              AND fecha_venta >= :inicio
              AND fecha_venta < DATE_ADD(:fin, INTERVAL 1 DAY)
        ";

        $stmt = $this->db->prepare($sqlVentas);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':inicio' => $inicio,
            ':fin' => $fin
        ]);

        $ventas = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        array_unshift($filas, [
            'id' => self::ID_CATEGORIA_INGRESO_VENTAS,
            'categoria' => 'Ventas',
            'total' => (float) ($ventas['total'] ?? 0),
            'cantidad' => (int) ($ventas['cantidad'] ?? 0)
        ]);

        usort($filas, static function (array $a, array $b): int {
            return $b['total'] <=> $a['total'];
        });

        return $filas;
    }

    /**
     * Obtener el ranking de productos por unidades o ingresos.
     */
    private function obtenerProductosMasVendidos(
        string $inicio,
        string $fin,
        string $criterio
    ): array {
        $idUsuario = $this->obtenerUsuario();

        $orden = $criterio === 'ingresos'
            ? 'ingresos_generados DESC, unidades_vendidas DESC'
            : 'unidades_vendidas DESC, ingresos_generados DESC';

        $sql = "
            SELECT
                p.id_producto,
                p.nombre,
                SUM(dv.cantidad) AS unidades_vendidas,
                SUM(dv.subtotal) AS ingresos_generados
            FROM detalle_venta dv
            INNER JOIN venta v
                ON v.id_venta = dv.id_venta
               AND v.id_usuario = dv.id_usuario
            INNER JOIN producto p
                ON p.id_producto = dv.id_producto
               AND p.id_usuario = dv.id_usuario
            WHERE dv.id_usuario = :id_usuario
              AND v.estado = 'Pagada'
              AND v.fecha_venta >= :inicio
              AND v.fecha_venta < DATE_ADD(:fin, INTERVAL 1 DAY)
            GROUP BY p.id_producto, p.nombre
            ORDER BY {$orden}
            LIMIT 10
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':inicio' => $inicio,
            ':fin' => $fin
        ]);

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $fila['id_producto'] = (int) $fila['id_producto'];
            $fila['unidades_vendidas'] = (int) $fila['unidades_vendidas'];
            $fila['ingresos_generados'] = (float) $fila['ingresos_generados'];
        }
        unset($fila);

        return $filas;
    }

    /**
     * Guardar manualmente una fotografía de los totales del período.
     */
    public function guardarReporte(string $inicio, string $fin): int
    {
        $idUsuario = $this->obtenerUsuario();
        $resumen = $this->obtenerTotales($inicio, $fin);

        $sql = "
            INSERT INTO reporte_ganancias
            (
                periodo_inicio,
                periodo_fin,
                total_ingresos,
                total_gastos,
                balance,
                id_usuario
            )
            VALUES
            (
                :inicio,
                :fin,
                :ingresos,
                :gastos,
                :balance,
                :id_usuario
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':inicio' => $inicio,
            ':fin' => $fin,
            ':ingresos' => $resumen['ingresos'],
            ':gastos' => $resumen['gastos'],
            ':balance' => $resumen['balance'],
            ':id_usuario' => $idUsuario
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Consultar reportes guardados por el usuario actual.
     */
    public function obtenerHistorial(): array
    {
        $idUsuario = $this->obtenerUsuario();

        $sql = "
            SELECT
                id_reporte,
                fecha_generacion,
                periodo_inicio,
                periodo_fin,
                total_ingresos,
                total_gastos,
                balance
            FROM reporte_ganancias
            WHERE id_usuario = :id_usuario
            ORDER BY fecha_generacion DESC, id_reporte DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $fila['id_reporte'] = (int) $fila['id_reporte'];
            $fila['total_ingresos'] = (float) $fila['total_ingresos'];
            $fila['total_gastos'] = (float) $fila['total_gastos'];
            $fila['balance'] = (float) $fila['balance'];
        }
        unset($fila);

        return $filas;
    }
}
