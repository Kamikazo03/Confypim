<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/services/PerfilService.php';
require_once ROOT_PATH . '/app/helpers/AuthHelper.php';

/**
 * ==========================================================
 * Perfil Controller
 * ==========================================================
 *
 * Controlador encargado de gestionar las operaciones
 * de la vista Perfil.
 *
 * ==========================================================
 */

class PerfilController
{
    private PerfilService $service;

    public function __construct()
    {
        exigirAdministrador();

        $this->service = new PerfilService();
    }

    /**
     * Obtiene los datos del perfil.
     */
    public function obtenerPerfil(): array
    {
        return $this->service->obtenerPerfil();
    }
}
