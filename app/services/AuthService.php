<?php

declare(strict_types=1);

/**
 * ==========================================================
 * AuthService
 * ==========================================================
 * Servicio encargado de gestionar las operaciones de
 * autenticación y registro de usuarios.
 * ==========================================================
 */

require_once ROOT_PATH . '/app/models/AuthModel.php';
require_once ROOT_PATH . '/app/helpers/AuthHelper.php';

class AuthService
{
    /**
     * Modelo de acceso a los datos.
     */
    private AuthModel $model;

    /**
     * Inicializar el servicio.
     */
    public function __construct()
    {
        $this->model = new AuthModel();
    }

    /* ======================================================
       INICIAR SESIÓN
       ====================================================== */

    /**
     * Intentar autenticar un administrador.
     */
    public function iniciarSesion(string $email, string $password): array
    {
        $email = trim($email);

        if ($email === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Correo y contraseña son obligatorios.'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'El correo electrónico no es válido.'
            ];
        }

        $usuario = $this->model->obtenerAdministradorPorEmail($email);

        if (
            $usuario === null ||
            !password_verify($password, $usuario['password_hash'])
        ) {
            return [
                'success' => false,
                'message' => 'Las credenciales no son correctas.'
            ];
        }

        /**
         * Regenerar el identificador de sesión después
         * de una autenticación satisfactoria.
         */
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'] ?? '',
            'email' => $usuario['email'],
            'rol' => $usuario['rol'],
            'descripcion' => $usuario['descripcion'] ?? null,
            'avatar' => $usuario['avatar'] ?? null,
            'foto_perfil' => $usuario['foto_perfil'] ?? null,
            'foto_pos_x' => (int) ($usuario['foto_pos_x'] ?? 50),
            'foto_pos_y' => (int) ($usuario['foto_pos_y'] ?? 50),
            'fecha_registro' => $usuario['fecha_registro']
        ];

        $_SESSION['ultima_actividad'] = time();

        return [
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'usuario' => $_SESSION['usuario']
        ];
    }

    /* ======================================================
       REGISTRAR USUARIO
       ====================================================== */

    /**
     * Validar y registrar un usuario nuevo.
     *
     * El rol se obtiene de DEFAULT_REGISTER_ROLE y nunca
     * de los datos enviados por el formulario.
     */
    public function registrarUsuario(array $datos): array
    {
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $apellido = trim((string) ($datos['apellido'] ?? ''));
        $email = strtolower(trim((string) ($datos['email'] ?? '')));
        $password = (string) ($datos['password'] ?? '');
        $confirmarPassword = (string) ($datos['confirmar_password'] ?? '');

        /* Validar campos obligatorios. */
        if (
            $nombre === '' ||
            $email === '' ||
            $password === '' ||
            $confirmarPassword === ''
        ) {
            return [
                'success' => false,
                'message' => 'Completa todos los campos obligatorios.'
            ];
        }

        /* Validar longitud del nombre. */
        if (mb_strlen($nombre) > 100 || mb_strlen($apellido) > 100) {
            return [
                'success' => false,
                'message' => 'El nombre y el apellido no pueden superar los 100 caracteres.'
            ];
        }

        /* Validar el correo electrónico. */
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Introduce un correo electrónico válido.'
            ];
        }

        if (mb_strlen($email) > 100) {
            return [
                'success' => false,
                'message' => 'El correo electrónico no puede superar los 100 caracteres.'
            ];
        }

        /* Exigir una contraseña de al menos 8 caracteres. */
        if (strlen($password) < 8) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres.'
            ];
        }

        /* Verificar que las contraseñas coincidan. */
        if ($password !== $confirmarPassword) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden.'
            ];
        }

        /* Evitar registrar correos existentes. */
        if ($this->model->existeEmail($email)) {
            return [
                'success' => false,
                'message' => 'Ya existe una cuenta registrada con ese correo.'
            ];
        }

        /**
         * Generar el hash seguro de la contraseña.
         *
         * Nunca se almacena la contraseña original
         * en la base de datos.
         */
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            return [
                'success' => false,
                'message' => 'No fue posible procesar la contraseña.'
            ];
        }

        /**
         * Asignar el rol configurado en el servidor.
         */
        $rol = DEFAULT_REGISTER_ROLE;

        if (!in_array($rol, ['administrador', 'cliente'], true)) {
            return [
                'success' => false,
                'message' => 'La configuración del rol de registro no es válida.'
            ];
        }

        try {
            $creado = $this->model->crearUsuario(
                $nombre,
                $apellido,
                $email,
                $passwordHash,
                $rol
            );

            if (!$creado) {
                return [
                    'success' => false,
                    'message' => 'No fue posible crear la cuenta.'
                ];
            }
        } catch (PDOException $e) {
            /**
             * La restricción UNIQUE de la base de datos
             * puede detectar un correo duplicado aunque
             * otra solicitud lo haya registrado antes.
             */
            if ($e->getCode() === '23000') {
                return [
                    'success' => false,
                    'message' => 'Ya existe una cuenta registrada con ese correo.'
                ];
            }

            throw $e;
        }

        return [
            'success' => true,
            'message' => 'Cuenta creada correctamente. Ya puedes iniciar sesión.'
        ];
    }

    /* ======================================================
       CERRAR SESIÓN
       ====================================================== */

    /**
     * Finalizar la sesión actual.
     */
    public function cerrarSesion(): array
    {
        cerrarSesion();

        return [
            'success' => true,
            'message' => 'Sesión cerrada correctamente.'
        ];
    }
}