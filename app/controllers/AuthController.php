<?php

declare(strict_types=1);

/**
 * ==========================================================
 * AuthController
 * ==========================================================
 * Controlador que recibe las operaciones de autenticación
 * y las delega al servicio correspondiente.
 * ==========================================================
 */

require_once ROOT_PATH . '/app/services/AuthService.php';

class AuthController
{
    /**
     * Servicio de autenticación.
     */
    private AuthService $service;

    /**
     * Inicializar el controlador.
     */
    public function __construct()
    {
        $this->service = new AuthService();
    }

    /* ======================================================
       INICIAR SESIÓN
       ====================================================== */

    /**
     * Procesar la solicitud de inicio de sesión.
     */
    public function iniciarSesion(array $datos): array
    {
        $email = (string) ($datos['email'] ?? '');
        $password = (string) ($datos['password'] ?? '');

        return $this->service->iniciarSesion($email, $password);
    }

    /* ======================================================
       REGISTRAR USUARIO
       ====================================================== */

    /**
     * Procesar la solicitud de registro.
     */
    public function registrarUsuario(array $datos): array
    {
        return $this->service->registrarUsuario($datos);
    }

    /* ======================================================
       CERRAR SESIÓN
       ====================================================== */

    /**
     * Procesar la solicitud de cierre de sesión.
     */
    public function cerrarSesion(): array
    {
        return $this->service->cerrarSesion();
    }
}