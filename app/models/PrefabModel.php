<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

/**==========================================================
 * NombreModel
 * ==========================================================
 *
 * Modelo encargado de gestionar todas las operaciones sobre
 * la tabla "nombre_tabla".
 *
 * Responsabilidades:
 * - Consultas (SELECT)
 * - Inserciones (INSERT)
 * - Actualizaciones (UPDATE)
 * - Eliminaciones lógicas o físicas (DELETE)
 *
 * Este modelo NO contiene lógica de negocio.
 * Toda la lógica debe permanecer en el Controller.
 *
 * Proyecto: Confipym
 * Autor: Kevin Alexander Medina Patiño
 * ==========================================================
 */
class NombreModel
{
    /**
     * Conexión a la base de datos.
     */
    private PDO $db;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->db = Database::connect();
    }

    /* ==========================================================
     * CONSULTAS (SELECT)
     * ========================================================== */

    /**
     * Obtener todos los registros.
     *
     * @return array
     */
    public function obtenerTodos(): array
    {

    }

    /**
     * Obtener un registro por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {

    }

    /**
     * Contar registros.
     *
     * @return int
     */
    public function contar(): int
    {

    }

    /* ==========================================================
     * REGISTRO (INSERT)
     * ========================================================== */

    /**
     * Crear un nuevo registro.
     *
     * @param array $datos
     * @return bool
     */
    public function crear(array $datos): bool
    {

    }

    /* ==========================================================
     * ACTUALIZACIÓN (UPDATE)
     * ========================================================== */

    /**
     * Actualizar un registro.
     *
     * @param array $datos
     * @return bool
     */
    public function actualizar(array $datos): bool
    {

    }

    /* ==========================================================
     * ELIMINACIÓN (DELETE)
     * ========================================================== */

    /**
     * Eliminación lógica.
     *
     * @param int $id
     * @return bool
     */
    public function desactivar(int $id): bool
    {

    }

}