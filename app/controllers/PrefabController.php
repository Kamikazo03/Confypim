<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/NombreModel.php';

/**
 * ==========================================================
 * NombreController
 * ==========================================================
 *
 * Controlador encargado de gestionar la lógica de negocio
 * relacionada con la entidad correspondiente.
 *
 * Responsabilidades:
 * - Validar datos
 * - Coordinar la lógica de negocio
 * - Invocar el modelo
 * - Preparar respuestas para las vistas o AJAX
 *
 * Proyecto: Confipym
 * Autor: Kevin Alexander Medina Patiño
 * ==========================================================
 */
class NombreController
{
    /**
     * Modelo asociado.
     */
    private NombreModel $modelo;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->modelo = new NombreModel();
    }

    /* ==========================================================
     * CONSULTAS
     * ========================================================== */

    /**
     * Obtener listado completo.
     *
     * @return array
     */
    public function listar(): array
    {

    }

    /**
     * Obtener un registro.
     *
     * @param int $id
     * @return array
     */
    public function obtener(int $id): array
    {

    }

    /* ==========================================================
     * CRUD
     * ========================================================== */

    /**
     * Registrar un nuevo elemento.
     *
     * @param array $datos
     * @return array
     */
    public function crear(array $datos): array
    {

    }

    /**
     * Actualizar un elemento.
     *
     * @param array $datos
     * @return array
     */
    public function actualizar(array $datos): array
    {

    }

    /**
     * Desactivar un elemento.
     *
     * @param int $id
     * @return array
     */
    public function desactivar(int $id): array
    {

    }

}