<?php

declare(strict_types=1);

require_once ROOT_PATH . '/config/database.php';

/**
 * ==========================================================
 * Modelo financiero
 * ==========================================================
 * Acceso a datos de Ingresos, Gastos y Reportes.
 * Los importes financieros siempre proceden de ingreso y gasto.
 * ==========================================================
 */
class FinanzasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    private function usuario(): int
    {
        $id = obtenerIdUsuarioSesion();
        if ($id === null) {
            throw new RuntimeException('No existe un usuario autenticado.');
        }
        return (int) $id;
    }

    private function rango(?string $desde, ?string $hasta, string $campo, array &$params): string
    {
        $sql = '';
        if ($desde !== null && $desde !== '') {
            $sql .= " AND DATE($campo) >= :desde";
            $params[':desde'] = $desde;
        }
        if ($hasta !== null && $hasta !== '') {
            $sql .= " AND DATE($campo) <= :hasta";
            $params[':hasta'] = $hasta;
        }
        return $sql;
    }

    public function obtenerCategorias(string $tipo): array
    {
        $tabla = $tipo === 'ingresos' ? 'categoria_ingreso' : 'categoria_gasto';
        $id = $tipo === 'ingresos' ? 'id_categoria_ingreso' : 'id_categoria_gasto';
        return $this->db->query("SELECT $id AS id, nombre FROM $tabla ORDER BY $id")->fetchAll();
    }

    public function obtenerResumen(string $tipo, ?string $desde, ?string $hasta): array
    {
        $ingreso = $tipo === 'ingresos';
        $tabla = $ingreso ? 'ingreso' : 'gasto';
        $fecha = $ingreso ? 'fecha_ingreso' : 'fecha';
        $idUsuario = $this->usuario();
        $params = [':usuario' => $idUsuario];
        $whereFecha = $this->rango($desde, $hasta, $fecha, $params);
        $sql = "SELECT COUNT(*) cantidad, COALESCE(SUM(monto),0) total,
                       COALESCE(AVG(monto),0) promedio
                FROM $tabla WHERE id_usuario=:usuario $whereFecha";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $resumen = $stmt->fetch();

        $paramsMes = [':usuario' => $idUsuario];
        $whereMes = $this->rango($desde, $hasta, $fecha, $paramsMes);
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT($fecha,'%Y-%m') periodo, SUM(monto) total
             FROM $tabla WHERE id_usuario=:usuario $whereMes
             GROUP BY DATE_FORMAT($fecha,'%Y-%m') ORDER BY periodo"
        );
        $stmt->execute($paramsMes);
        $evolucion = $stmt->fetchAll();

        $catId = $ingreso ? 'id_categoria_ingreso' : 'id_categoria_gasto';
        $catTabla = $ingreso ? 'categoria_ingreso' : 'categoria_gasto';
        $paramsCat = [':usuario' => $idUsuario];
        $whereCat = $this->rango($desde, $hasta, $fecha, $paramsCat);
        $stmt = $this->db->prepare(
            "SELECT c.$catId AS id_categoria, c.nombre, COALESCE(SUM(m.monto),0) total
             FROM $tabla m JOIN $catTabla c ON c.$catId=m.$catId
             WHERE m.id_usuario=:usuario $whereCat
             GROUP BY c.$catId,c.nombre ORDER BY total DESC"
        );
        $stmt->execute($paramsCat);

        return [
            'total' => (float) $resumen['total'],
            'cantidad' => (int) $resumen['cantidad'],
            'promedio' => (float) $resumen['promedio'],
            'evolucion' => $evolucion,
            'categorias' => $stmt->fetchAll()
        ];
    }

    public function listarMovimientos(string $tipo, ?string $desde, ?string $hasta, ?int $categoria, string $busqueda): array
    {
        $ingreso = $tipo === 'ingresos';
        $tabla = $ingreso ? 'ingreso' : 'gasto';
        $fecha = $ingreso ? 'fecha_ingreso' : 'fecha';
        $idCat = $ingreso ? 'id_categoria_ingreso' : 'id_categoria_gasto';
        $catTabla = $ingreso ? 'categoria_ingreso' : 'categoria_gasto';
        $idCatTabla = $idCat;
        $id = $ingreso ? 'id_ingreso' : 'id_gasto';
        $usuario = $this->usuario();
        $params = [':usuario' => $usuario];
        $where = $this->rango($desde, $hasta, "m.$fecha", $params);
        if ($categoria) {
            $where .= " AND m.$idCat=:categoria";
            $params[':categoria'] = $categoria;
        }
        if ($busqueda !== '') {
            $where .= " AND (c.nombre LIKE :busqueda OR " .
                ($ingreso ? "CAST(m.id_venta AS CHAR)" : "m.descripcion") .
                " LIKE :busqueda)";
            $params[':busqueda'] = '%' . $busqueda . '%';
        }
        $extra = $ingreso
            ? ", m.id_venta, CASE WHEN m.id_venta IS NULL THEN 'Manual' ELSE CONCAT('Venta #',v.numero_venta) END origen"
            : ", m.descripcion, NULL id_venta, 'Manual' origen";
        $joinVenta = $ingreso
            ? " LEFT JOIN venta v ON v.id_venta=m.id_venta AND v.id_usuario=m.id_usuario"
            : "";
        $stmt = $this->db->prepare(
            "SELECT m.$id id, m.monto, m.$fecha fecha, c.nombre categoria
                    $extra
             FROM $tabla m
             JOIN $catTabla c ON c.$idCatTabla=m.$idCat
             $joinVenta
             WHERE m.id_usuario=:usuario $where
             ORDER BY m.$fecha DESC,m.$id DESC LIMIT 500"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerProductos(?string $desde, ?string $hasta): array
    {
        $usuario = $this->usuario();
        $params = [':usuario' => $usuario];
        $where = $this->rango($desde, $hasta, 'v.fecha_venta', $params);
        $stmt = $this->db->prepare(
            "SELECT
                p.id_producto,
                p.nombre,
                SUM(d.cantidad) AS unidades,
                SUM(d.subtotal) AS total
             FROM detalle_venta d
             INNER JOIN venta v
                ON v.id_venta = d.id_venta
               AND v.id_usuario = d.id_usuario
             INNER JOIN producto p
                ON p.id_producto = d.id_producto
               AND p.id_usuario = d.id_usuario
             WHERE d.id_usuario = :usuario
               AND v.estado = 'Pagada' $where
             GROUP BY p.id_producto, p.nombre
             ORDER BY total DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerBalance(?string $desde, ?string $hasta): array
    {
        $usuario = $this->usuario();
        $params = [':usuario' => $usuario];
        $filtroIngreso = $this->rango($desde, $hasta, 'fecha_ingreso', $params);
        $sqlIngreso = "SELECT DATE(fecha_ingreso) fecha,SUM(monto) total
                       FROM ingreso WHERE id_usuario=:usuario $filtroIngreso
                       GROUP BY DATE(fecha_ingreso)";
        $stmt = $this->db->prepare($sqlIngreso);
        $stmt->execute($params);
        $ingresos = [];
        foreach ($stmt->fetchAll() as $r) {
            $ingresos[$r['fecha']] = (float) $r['total'];
        }

        $paramsG = [':usuario' => $usuario];
        $filtroGasto = $this->rango($desde, $hasta, 'fecha', $paramsG);
        $stmt = $this->db->prepare(
            "SELECT DATE(fecha) fecha,SUM(monto) total FROM gasto
             WHERE id_usuario=:usuario $filtroGasto GROUP BY DATE(fecha)"
        );
        $stmt->execute($paramsG);
        $gastos = [];
        foreach ($stmt->fetchAll() as $r) {
            $gastos[$r['fecha']] = (float) $r['total'];
        }

        $dias = array_unique(array_merge(array_keys($ingresos), array_keys($gastos)));
        sort($dias);
        $acumulado = 0;
        $filas = [];
        foreach ($dias as $dia) {
            $in = $ingresos[$dia] ?? 0;
            $out = $gastos[$dia] ?? 0;
            $neto = $in - $out;
            $acumulado += $neto;
            $filas[] = [
                'fecha' => $dia,
                'ingresos' => $in,
                'gastos' => $out,
                'balance' => $neto,
                'acumulado' => $acumulado
            ];
        }
        return $filas;
    }

    public function obtenerTotales(?string $desde, ?string $hasta): array
    {
        $usuario = $this->usuario();

        $paramsIngreso = [':u' => $usuario];
        $filtroIngreso = $this->rango($desde, $hasta, 'fecha_ingreso', $paramsIngreso);

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0)
             FROM ingreso
             WHERE id_usuario = :u $filtroIngreso"
        );
        $stmt->execute($paramsIngreso);
        $ingresos = (float) $stmt->fetchColumn();

        $paramsGasto = [':u' => $usuario];
        $filtroGasto = $this->rango($desde, $hasta, 'fecha', $paramsGasto);

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0)
             FROM gasto
             WHERE id_usuario = :u $filtroGasto"
        );
        $stmt->execute($paramsGasto);
        $gastos = (float) $stmt->fetchColumn();

        return [
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'balance' => $ingresos - $gastos
        ];
    }

    public function guardarReporte(string $desde, string $hasta): int
    {
        $totales = $this->obtenerTotales($desde,$hasta);
        $stmt = $this->db->prepare(
            "INSERT INTO reporte_ganancias
             (periodo_inicio,periodo_fin,total_ingresos,total_gastos,balance,id_usuario)
             VALUES (:desde,:hasta,:ing,:gas,:bal,:usuario)"
        );
        $stmt->execute([
            ':desde' => $desde, ':hasta' => $hasta,
            ':ing' => $totales['ingresos'], ':gas' => $totales['gastos'],
            ':bal' => $totales['balance'], ':usuario' => $this->usuario()
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listarReportes(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_reporte,fecha_generacion,periodo_inicio,periodo_fin,
                    total_ingresos,total_gastos,balance
             FROM reporte_ganancias WHERE id_usuario=:u
             ORDER BY fecha_generacion DESC LIMIT 100"
        );
        $stmt->execute([':u' => $this->usuario()]);
        return $stmt->fetchAll();
    }
}
