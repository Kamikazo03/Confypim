<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/InsumoModel.php';

/**
 * ==========================================================
 * InsumoController
 * ==========================================================
 * Controlador encargado de gestionar la lógica de negocio
 * relacionada con los insumos, sus movimientos y las recetas.
 *
 * Actúa como intermediario entre las solicitudes del frontend
 * y el modelo de datos.
 * ==========================================================
 */

class InsumoController
{
    /**
     * Instancia del modelo de insumos.
     */
    private InsumoModel $insumoModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->insumoModel = new InsumoModel();
    }

    /* ==========================================================
     * CONSULTAS
     * ========================================================== */

    public function listarInsumos(): array
    {
        return $this->insumoModel->obtenerTodos();
    }

    public function listarInactivos(): array
    {
        return $this->insumoModel->obtenerInactivos();
    }

    public function obtenerMetricas(): array
    {
        return $this->insumoModel->obtenerMetricas();
    }

    public function obtenerInsumo(int $id): array
    {
        $insumo = $this->insumoModel->obtenerPorId($id);

        if ($insumo === null) {
            return [
                'success' => false,
                'message' => 'El insumo solicitado no existe.'
            ];
        }

        return [
            'success' => true,
            'data' => $insumo
        ];
    }

    public function listarMovimientos(): array
    {
        return $this->insumoModel->obtenerMovimientos();
    }

    public function listarProductos(): array
    {
        return $this->insumoModel->obtenerProductos();
    }

    public function obtenerReceta(int $idProducto): array
    {
        if ($idProducto <= 0) {
            return [
                'success' => false,
                'message' => 'Debe seleccionar un producto válido.'
            ];
        }

        return [
            'success' => true,
            'data' => $this->insumoModel->obtenerReceta($idProducto)
        ];
    }

    /* ==========================================================
     * CRUD DE INSUMOS
     * ========================================================== */

    public function crearInsumo(array $datos): array
    {
        $validacion = $this->validarDatosInsumo($datos);

        if (!$validacion['ok']) {
            return $this->respuestaError($validacion['mensaje'], 400);
        }

        try {
            $this->insumoModel->crear($validacion['datos']);

            return [
                'success' => true,
                'message' => 'Insumo registrado correctamente.',
                'codigo' => 201
            ];

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return $this->respuestaError(
                    'Ya existe un insumo con ese nombre en tu inventario.',
                    409
                );
            }

            throw $e;
        }
    }

    public function actualizarInsumo(array $datos): array
    {
        $id = filter_var(
            $datos['id_insumo'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id <= 0) {
            return $this->respuestaError('Debe indicar un insumo válido.', 400);
        }

        $insumoActual = $this->insumoModel->obtenerPorId($id);

        if ($insumoActual === null) {
            return $this->respuestaError('El insumo que intenta actualizar no existe.', 404);
        }

        // La unidad base se conserva para no alterar las cantidades
        // que ya están registradas en movimientos y recetas.
        $datos['unidad_medida'] = $insumoActual['unidad_medida'];

        $validacion = $this->validarDatosInsumo($datos);

        if (!$validacion['ok']) {
            return $this->respuestaError($validacion['mensaje'], 400);
        }

        try {
            $validacion['datos']['id_insumo'] = $id;
            $this->insumoModel->actualizar($validacion['datos']);

            return [
                'success' => true,
                'message' => 'Insumo actualizado correctamente.',
                'codigo' => 200
            ];

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return $this->respuestaError(
                    'Ya existe otro insumo con ese nombre en tu inventario.',
                    409
                );
            }

            throw $e;
        }
    }

    public function desactivarInsumo(int $id): array
    {
        if ($id <= 0 || $this->insumoModel->obtenerPorId($id) === null) {
            return $this->respuestaError('El insumo que intenta desactivar no existe.', 404);
        }

        if ($this->insumoModel->tieneRecetas($id)) {
            return $this->respuestaError(
                'No puedes desactivar este insumo mientras forme parte de una receta. Primero actualiza las recetas relacionadas.',
                409
            );
        }

        $this->insumoModel->desactivar($id);

        return [
            'success' => true,
            'message' => 'Insumo enviado a la papelera correctamente.',
            'codigo' => 200
        ];
    }

    public function restaurarInsumo(int $id): array
    {
        if ($id <= 0) {
            return $this->respuestaError('Debe indicar un insumo válido.', 400);
        }

        try {
            if (!$this->insumoModel->restaurar($id)) {
                return $this->respuestaError('El insumo no existe en la papelera.', 404);
            }

            return [
                'success' => true,
                'message' => 'Insumo restaurado correctamente.',
                'codigo' => 200
            ];

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return $this->respuestaError(
                    'No se puede restaurar porque ya existe un insumo activo con el mismo nombre.',
                    409
                );
            }

            throw $e;
        }
    }

    /* ==========================================================
     * OPERACIONES DE INVENTARIO
     * ========================================================== */

    public function registrarCompra(array $datos): array
    {
        $validacion = $this->validarDatosCompra($datos);

        if (!$validacion['ok']) {
            return $this->respuestaError($validacion['mensaje'], 400);
        }

        $this->insumoModel->registrarCompra($validacion['datos']);

        return [
            'success' => true,
            'message' => 'Compra registrada. Se actualizaron las existencias y el costo promedio.',
            'codigo' => 201
        ];
    }

    /**
     * Registrar varias compras de insumos en una sola transacción.
     */
    public function registrarComprasMultiples(array $datos): array
    {
        $items = $datos['items'] ?? null;
        $observacion = isset($datos['observacion']) && is_string($datos['observacion'])
            ? trim($datos['observacion'])
            : '';

        if (!is_array($items) || count($items) < 2) {
            return $this->respuestaError('Agrega al menos dos insumos para registrar una compra múltiple.', 400);
        }

        if (mb_strlen($observacion) > 255) {
            return $this->respuestaError('La observación no puede superar los 255 caracteres.', 400);
        }

        $normalizados = [];
        $ids = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                return $this->respuestaError('Uno de los insumos de la compra no es válido.', 400);
            }

            $validacion = $this->validarDatosCompra($item);

            if (!$validacion['ok']) {
                return $this->respuestaError($validacion['mensaje'], 400);
            }

            $id = (int) $validacion['datos']['id_insumo'];

            if (isset($ids[$id])) {
                return $this->respuestaError('No repitas un mismo insumo en la compra múltiple.', 400);
            }

            $ids[$id] = true;
            $normalizados[] = $validacion['datos'];
        }

        $this->insumoModel->registrarComprasMultiples($normalizados, $observacion);

        return [
            'success' => true,
            'message' => 'Compra múltiple registrada correctamente. Se creó un solo gasto y se actualizaron todas las existencias.',
            'codigo' => 201
        ];
    }

    /**
     * Registrar varios consumos o ajustes en una sola transacción.
     */
    public function registrarMovimientosMultiples(array $datos): array
    {
        $tipo = $datos['tipo'] ?? '';
        $operacion = $datos['operacion'] ?? 'salida';
        $observacion = isset($datos['observacion']) && is_string($datos['observacion'])
            ? trim($datos['observacion'])
            : '';
        $items = $datos['items'] ?? null;

        if (!in_array($tipo, ['consumo', 'ajuste'], true)) {
            return $this->respuestaError('Selecciona un tipo de movimiento válido.', 400);
        }

        if (!in_array($operacion, ['entrada', 'salida'], true)) {
            return $this->respuestaError('Selecciona una operación válida.', 400);
        }

        if ($tipo === 'consumo') {
            $operacion = 'salida';
        }

        if (!is_array($items) || count($items) < 2) {
            return $this->respuestaError('Agrega al menos dos insumos para registrar un movimiento múltiple.', 400);
        }

        if ($observacion === '' || mb_strlen($observacion) > 255) {
            return $this->respuestaError('Indica un motivo de máximo 255 caracteres.', 400);
        }

        $normalizados = [];
        $ids = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                return $this->respuestaError('Uno de los movimientos enviados no es válido.', 400);
            }

            $id = filter_var($item['id_insumo'] ?? null, FILTER_VALIDATE_INT);
            $cantidad = $item['cantidad'] ?? null;

            if ($id === false || $id <= 0 || !is_numeric($cantidad) || (float) $cantidad <= 0) {
                return $this->respuestaError('Cada insumo debe tener una cantidad mayor que cero.', 400);
            }

            if (isset($ids[$id])) {
                return $this->respuestaError('No repitas un mismo insumo en el movimiento múltiple.', 400);
            }

            $insumo = $this->insumoModel->obtenerPorId((int) $id);

            if ($insumo === null) {
                return $this->respuestaError('Uno de los insumos seleccionados no existe o está inactivo.', 400);
            }

            $unidad = $item['unidad'] ?? '';
            $unidadesPermitidas = [
                'g' => ['g', 'kg'],
                'ml' => ['ml', 'l'],
                'unidades' => ['unidades']
            ];

            if (!in_array($unidad, $unidadesPermitidas[$insumo['unidad_medida']], true)) {
                return $this->respuestaError('La unidad seleccionada no corresponde al insumo.', 400);
            }

            if ($insumo['unidad_medida'] === 'unidades' && floor((float) $cantidad) !== (float) $cantidad) {
                return $this->respuestaError('La cantidad de unidades debe ser un número entero.', 400);
            }

            $factor = in_array($unidad, ['kg', 'l'], true) ? 1000 : 1;
            $cantidadBase = round((float) $cantidad * $factor, 3);

            $ids[$id] = true;
            $normalizados[] = [
                'id_insumo' => (int) $id,
                'cantidad' => $cantidadBase,
                'observacion' => $observacion
            ];
        }

        $this->insumoModel->registrarMovimientosMultiples(
            $normalizados,
            $tipo,
            $operacion,
            $observacion
        );

        return [
            'success' => true,
            'message' => 'Los movimientos se registraron correctamente en una sola transacción.',
            'codigo' => 201
        ];
    }

    public function registrarConsumo(array $datos): array
    {
        $validacion = $this->validarDatosMovimiento($datos);

        if (!$validacion['ok']) {
            return $this->respuestaError($validacion['mensaje'], 400);
        }

        $this->insumoModel->registrarConsumo($validacion['datos']);

        return [
            'success' => true,
            'message' => 'Consumo registrado correctamente.',
            'codigo' => 201
        ];
    }

    public function registrarAjuste(array $datos): array
    {
        $validacion = $this->validarDatosMovimiento($datos, true);

        if (!$validacion['ok']) {
            return $this->respuestaError($validacion['mensaje'], 400);
        }

        $this->insumoModel->registrarAjuste($validacion['datos']);

        return [
            'success' => true,
            'message' => 'Ajuste de inventario registrado correctamente.',
            'codigo' => 201
        ];
    }

    /* ==========================================================
     * RECETAS
     * ========================================================== */

    public function listarRecetas(): array
    {
        return [
            'success' => true,
            'data' => $this->insumoModel->obtenerRecetas()
        ];
    }

    public function eliminarReceta(int $idProducto): array
    {
        if ($idProducto <= 0) {
            return $this->respuestaError('Debe indicar un producto válido.', 400);
        }

        if (!$this->insumoModel->eliminarReceta($idProducto)) {
            return $this->respuestaError('El producto no tiene una receta para eliminar.', 404);
        }

        return [
            'success' => true,
            'message' => 'Receta eliminada correctamente.',
            'codigo' => 200
        ];
    }

    public function guardarReceta(array $datos): array
    {
        $idProducto = filter_var(
            $datos['id_producto'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($idProducto === false || $idProducto <= 0) {
            return $this->respuestaError('Debe seleccionar un producto válido.', 400);
        }

        $ingredientes = $datos['ingredientes'] ?? null;

        if (!is_array($ingredientes) || count($ingredientes) === 0) {
            return $this->respuestaError('La receta debe contener al menos un insumo.', 400);
        }

        $normalizados = [];
        $idsUtilizados = [];

        foreach ($ingredientes as $ingrediente) {
            if (!is_array($ingrediente)) {
                return $this->respuestaError('Uno de los ingredientes enviados no es válido.', 400);
            }

            $idInsumo = filter_var(
                $ingrediente['id_insumo'] ?? null,
                FILTER_VALIDATE_INT
            );

            $cantidad = $ingrediente['cantidad_necesaria'] ?? null;

            if ($idInsumo === false || $idInsumo <= 0 || !is_numeric($cantidad) || (float) $cantidad <= 0) {
                return $this->respuestaError('Cada ingrediente debe tener un insumo y una cantidad mayor que cero.', 400);
            }

            if (isset($idsUtilizados[$idInsumo])) {
                return $this->respuestaError('No repitas el mismo insumo dentro de una receta.', 400);
            }

            $idsUtilizados[$idInsumo] = true;

            $normalizados[] = [
                'id_insumo' => $idInsumo,
                'cantidad_necesaria' => (float) $cantidad
            ];
        }

        $permitirEdicion = filter_var($datos['editar'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->insumoModel->guardarReceta($idProducto, $normalizados, $permitirEdicion);

        return [
            'success' => true,
            'message' => 'Receta guardada correctamente.',
            'codigo' => 200
        ];
    }

    /* ==========================================================
     * VALIDACIONES
     * ========================================================== */

    private function validarDatosInsumo(array $datos): array
    {
        $nombre = isset($datos['nombre']) && is_string($datos['nombre'])
            ? trim($datos['nombre'])
            : '';

        if ($nombre === '') {
            return ['ok' => false, 'mensaje' => 'El nombre del insumo es obligatorio.'];
        }

        if (mb_strlen($nombre) > 100) {
            return ['ok' => false, 'mensaje' => 'El nombre no puede superar los 100 caracteres.'];
        }

        $unidad = $datos['unidad_medida'] ?? null;

        if (!in_array($unidad, ['g', 'ml', 'unidades'], true)) {
            return ['ok' => false, 'mensaje' => 'La unidad de medida seleccionada no es válida.'];
        }

        return [
            'ok' => true,
            'datos' => [
                'nombre' => $nombre,
                'unidad_medida' => $unidad
            ]
        ];
    }

    private function validarDatosCompra(array $datos): array
    {
        $idInsumo = filter_var($datos['id_insumo'] ?? null, FILTER_VALIDATE_INT);
        $cantidad = $datos['cantidad'] ?? null;
        $costoTotal = $datos['costo_total'] ?? null;
        $unidadCompra = $datos['unidad_compra'] ?? null;

        if ($idInsumo === false || $idInsumo <= 0) {
            return ['ok' => false, 'mensaje' => 'Selecciona un insumo válido.'];
        }

        if (!is_numeric($cantidad) || (float) $cantidad <= 0) {
            return ['ok' => false, 'mensaje' => 'La cantidad comprada debe ser mayor que cero.'];
        }

        if (!is_numeric($costoTotal) || (float) $costoTotal <= 0) {
            return ['ok' => false, 'mensaje' => 'El costo total de la compra debe ser mayor que cero.'];
        }

        if (!in_array($unidadCompra, ['g', 'kg', 'ml', 'l', 'unidades'], true)) {
            return ['ok' => false, 'mensaje' => 'La unidad de compra no es válida.'];
        }

        $insumo = $this->insumoModel->obtenerPorId($idInsumo);

        if ($insumo === null) {
            return ['ok' => false, 'mensaje' => 'El insumo seleccionado no existe o está inactivo.'];
        }

        $unidadesPermitidas = [
            'g' => ['g', 'kg'],
            'ml' => ['ml', 'l'],
            'unidades' => ['unidades']
        ];

        if (!in_array($unidadCompra, $unidadesPermitidas[$insumo['unidad_medida']], true)) {
            return [
                'ok' => false,
                'mensaje' => 'La unidad de compra no corresponde a la unidad base del insumo.'
            ];
        }

        if ($insumo['unidad_medida'] === 'unidades' && floor((float) $cantidad) !== (float) $cantidad) {
            return [
                'ok' => false,
                'mensaje' => 'La cantidad de unidades debe ser un número entero.'
            ];
        }

        $factor = [
            'g' => 1,
            'kg' => 1000,
            'ml' => 1,
            'l' => 1000,
            'unidades' => 1
        ][$unidadCompra];

        $cantidadBase = round((float) $cantidad * $factor, 3);

        $observacion = isset($datos['observacion']) && is_string($datos['observacion'])
            ? trim($datos['observacion'])
            : '';

        if (mb_strlen($observacion) > 255) {
            return ['ok' => false, 'mensaje' => 'La observación no puede superar los 255 caracteres.'];
        }

        return [
            'ok' => true,
            'datos' => [
                'id_insumo' => $idInsumo,
                'cantidad_base' => $cantidadBase,
                'costo_total' => (float) $costoTotal,
                'observacion' => $observacion
            ]
        ];
    }

    private function validarDatosMovimiento(array $datos, bool $esAjuste = false): array
    {
        $idInsumo = filter_var($datos['id_insumo'] ?? null, FILTER_VALIDATE_INT);
        $cantidad = $datos['cantidad'] ?? null;

        if ($idInsumo === false || $idInsumo <= 0) {
            return ['ok' => false, 'mensaje' => 'Selecciona un insumo válido.'];
        }

        if (!is_numeric($cantidad) || (float) $cantidad <= 0) {
            return ['ok' => false, 'mensaje' => 'La cantidad debe ser mayor que cero.'];
        }

        $observacion = isset($datos['observacion']) && is_string($datos['observacion'])
            ? trim($datos['observacion'])
            : '';

        if ($observacion === '') {
            return ['ok' => false, 'mensaje' => 'Debes indicar el motivo del movimiento.'];
        }

        if (mb_strlen($observacion) > 255) {
            return ['ok' => false, 'mensaje' => 'El motivo no puede superar los 255 caracteres.'];
        }

        $normalizados = [
            'id_insumo' => $idInsumo,
            'cantidad' => (float) $cantidad,
            'observacion' => $observacion
        ];

        if ($esAjuste) {
            $operacion = $datos['operacion'] ?? '';

            if (!in_array($operacion, ['entrada', 'salida'], true)) {
                return ['ok' => false, 'mensaje' => 'Selecciona si el ajuste aumenta o disminuye el stock.'];
            }

            $normalizados['operacion'] = $operacion;
        }

        return ['ok' => true, 'datos' => $normalizados];
    }

    /* ==========================================================
     * RESPUESTAS
     * ========================================================== */

    private function respuestaError(string $mensaje, int $codigo): array
    {
        return [
            'success' => false,
            'message' => $mensaje,
            'codigo' => $codigo
        ];
    }
}
