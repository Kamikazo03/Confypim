<?php

declare(strict_types=1);

require_once ROOT_PATH . '/app/models/PerfilModel.php';
require_once ROOT_PATH . '/app/helpers/AuthHelper.php';

/**
 * ==========================================================
 * Perfil Service
 * ==========================================================
 *
 * Valida y gestiona la información del perfil del usuario.
 *
 * ==========================================================
 */

class PerfilService
{
    private PerfilModel $model;

    public function __construct()
    {
        $this->model = new PerfilModel();
    }

    /**
     * Obtiene el perfil del administrador autenticado.
     */
    public function obtenerPerfil(): array
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException('No existe un usuario autenticado.');
        }

        $usuario = $this->model->obtenerPorId($idUsuario);

        if ($usuario === null || $usuario['rol'] !== 'administrador') {
            throw new RuntimeException('No se encontró el perfil solicitado.');
        }

        return $usuario;
    }

    /**
     * Actualiza nombre, apellido y descripción.
     */
    public function actualizarPerfil(array $datos): array
    {
        $idUsuario = $this->obtenerIdUsuario();

        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $apellido = trim((string) ($datos['apellido'] ?? ''));
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));

        if ($nombre === '' || $apellido === '') {
            throw new InvalidArgumentException(
                'El nombre y el apellido son obligatorios.'
            );
        }

        if (mb_strlen($nombre) > 100 || mb_strlen($apellido) > 100) {
            throw new InvalidArgumentException(
                'El nombre y el apellido no pueden superar los 100 caracteres.'
            );
        }

        if (mb_strlen($descripcion) > 2000) {
            throw new InvalidArgumentException(
                'La descripción no puede superar los 2000 caracteres.'
            );
        }

        $this->model->actualizar(
            $idUsuario,
            $nombre,
            $apellido,
            $descripcion !== '' ? $descripcion : null
        );

        $usuario = $this->obtenerUsuarioActualizado($idUsuario);

        return [
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'usuario' => $usuario
        ];
    }

    /**
     * Selecciona un avatar existente o restaura las iniciales.
     */
    public function seleccionarAvatar(?string $avatar): array
    {
        $idUsuario = $this->obtenerIdUsuario();

        // Un valor vacío significa que se quieren usar las iniciales.
        $avatar = trim((string) $avatar);

        if ($avatar === '') {
            $avatar = null;
        }

        if ($avatar !== null) {
            if (!preg_match('/^avatar-[a-zA-Z0-9_-]+$/', $avatar)) {
                throw new InvalidArgumentException(
                    'El nombre del avatar no es válido.'
                );
            }

            $rutaAvatar = ROOT_PATH
                . '/assets/img/avatars/'
                . $avatar
                . '.svg';

            if (!is_file($rutaAvatar)) {
                throw new InvalidArgumentException(
                    'El avatar seleccionado no existe.'
                );
            }
        }

        $this->model->actualizarAvatar($idUsuario, $avatar);

        $usuario = $this->obtenerUsuarioActualizado($idUsuario);

        return [
            'success' => true,
            'message' => $avatar === null
                ? 'Se restauraron las iniciales.'
                : 'Avatar actualizado correctamente.',
            'usuario' => $usuario
        ];
    }

    /**
     * Sube una fotografía de perfil.
     */
    public function subirFoto(array $archivo): array
    {
        $idUsuario = $this->obtenerIdUsuario();

        if (
            !isset($archivo['error']) ||
            $archivo['error'] !== UPLOAD_ERR_OK
        ) {
            throw new InvalidArgumentException(
                'No se pudo recibir la fotografía.'
            );
        }

        if (
            !isset($archivo['size']) ||
            $archivo['size'] <= 0 ||
            $archivo['size'] > 2 * 1024 * 1024
        ) {
            throw new InvalidArgumentException(
                'La fotografía debe pesar más de 0 bytes y no superar los 2 MB.'
            );
        }

        $rutaTemporal = $archivo['tmp_name'] ?? '';

        if ($rutaTemporal === '' || !is_uploaded_file($rutaTemporal)) {
            throw new InvalidArgumentException(
                'El archivo recibido no es válido.'
            );
        }

        $informacion = getimagesize($rutaTemporal);

        if ($informacion === false) {
            throw new InvalidArgumentException(
                'El archivo no contiene una imagen válida.'
            );
        }

        $tiposPermitidos = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp'
        ];

        $tipoImagen = $informacion[2];

        if (!isset($tiposPermitidos[$tipoImagen])) {
            throw new InvalidArgumentException(
                'Solo se permiten imágenes JPG, PNG o WebP.'
            );
        }

        $directorio = ROOT_PATH . '/assets/img/perfiles';

        if (
            !is_dir($directorio) &&
            !mkdir($directorio, 0755, true) &&
            !is_dir($directorio)
        ) {
            throw new RuntimeException(
                'No se pudo preparar el directorio de fotografías.'
            );
        }

        $extension = $tiposPermitidos[$tipoImagen];

        $nombreArchivo = 'perfil_' . $idUsuario . '_'
            . bin2hex(random_bytes(12)) . '.' . $extension;

        $destino = $directorio . '/' . $nombreArchivo;

        if (!move_uploaded_file($rutaTemporal, $destino)) {
            throw new RuntimeException(
                'No se pudo guardar la fotografía.'
            );
        }

        $usuarioAnterior = $this->model->obtenerPorId($idUsuario);
        $rutaAnterior = $usuarioAnterior['foto_perfil'] ?? null;

        $rutaRelativa = 'assets/img/perfiles/' . $nombreArchivo;

        try {
            $this->model->actualizarFoto($idUsuario, $rutaRelativa);
        } catch (Throwable $e) {
            if (is_file($destino)) {
                unlink($destino);
            }

            throw $e;
        }

        $usuario = $this->obtenerUsuarioActualizado($idUsuario);

        // Elimina la foto anterior solo si pertenece a la carpeta de perfiles.
        $this->eliminarArchivoFotoAnterior($rutaAnterior);

        return [
            'success' => true,
            'message' => 'Fotografía actualizada correctamente.',
            'usuario' => $usuario
        ];
    }

    /**
     * Elimina la fotografía de perfil.
     */
    public function eliminarFoto(): array
    {
        $idUsuario = $this->obtenerIdUsuario();

        $usuarioAnterior = $this->model->obtenerPorId($idUsuario);
        $rutaAnterior = $usuarioAnterior['foto_perfil'] ?? null;

        $this->model->actualizarFoto($idUsuario, null);

        $usuario = $this->obtenerUsuarioActualizado($idUsuario);

        $this->eliminarArchivoFotoAnterior($rutaAnterior);

        return [
            'success' => true,
            'message' => 'Fotografía eliminada correctamente.',
            'usuario' => $usuario
        ];
    }

    /**
     * Actualiza el encuadre de la fotografía del perfil.
     */
    public function actualizarEncuadre(array $datos): array
    {
        $idUsuario = $this->obtenerIdUsuario();

        $posicionX = filter_var(
            $datos['posicion_x'] ?? null,
            FILTER_VALIDATE_INT
        );

        $posicionY = filter_var(
            $datos['posicion_y'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $posicionX === false ||
            $posicionY === false ||
            $posicionX < 0 || $posicionX > 100 ||
            $posicionY < 0 || $posicionY > 100
        ) {
            throw new InvalidArgumentException(
                'La posición del encuadre no es válida.'
            );
        }

        if (!$this->model->actualizarEncuadre(
            $idUsuario,
            $posicionX,
            $posicionY
        )) {
            throw new RuntimeException(
                'No fue posible guardar el encuadre.'
            );
        }

        $usuario = $this->obtenerUsuarioActualizado($idUsuario);

        return [
            'success' => true,
            'message' => 'Encuadre guardado correctamente.',
            'usuario' => $usuario
        ];
    }

    /**
     * Obtiene el identificador del administrador autenticado.
     */
    private function obtenerIdUsuario(): int
    {
        $idUsuario = obtenerIdUsuarioSesion();

        if ($idUsuario === null) {
            throw new RuntimeException(
                'No existe un usuario autenticado.'
            );
        }

        return $idUsuario;
    }

    /**
     * Recupera el usuario actualizado y sincroniza su sesión.
     */
    private function obtenerUsuarioActualizado(int $idUsuario): array
    {
        $usuario = $this->model->obtenerPorId($idUsuario);

        if ($usuario === null || $usuario['rol'] !== 'administrador') {
            throw new RuntimeException(
                'No se pudo recuperar el perfil actualizado.'
            );
        }

        $this->actualizarSesion($usuario);

        return $usuario;
    }

    /**
     * Actualiza los datos del usuario almacenados en la sesión.
     */
    private function actualizarSesion(array $usuario): void
    {
        $_SESSION['usuario']['id_usuario'] = (int) $usuario['id_usuario'];
        $_SESSION['usuario']['nombre'] = $usuario['nombre'];
        $_SESSION['usuario']['apellido'] = $usuario['apellido'] ?? '';
        $_SESSION['usuario']['descripcion'] = $usuario['descripcion'] ?? null;
        $_SESSION['usuario']['avatar'] = $usuario['avatar'] ?? null;
        $_SESSION['usuario']['foto_perfil'] = $usuario['foto_perfil'] ?? null;
        $_SESSION['usuario']['foto_pos_x'] = (int) ($usuario['foto_pos_x'] ?? 50);
        $_SESSION['usuario']['foto_pos_y'] = (int) ($usuario['foto_pos_y'] ?? 50);
        $_SESSION['usuario']['email'] = $usuario['email'];
        $_SESSION['usuario']['rol'] = $usuario['rol'];
        $_SESSION['usuario']['fecha_registro'] = $usuario['fecha_registro'] ?? null;
    }

    /**
     * Elimina una fotografía anterior de la carpeta permitida.
     */
    private function eliminarArchivoFotoAnterior(?string $ruta): void
    {
        if (
            !is_string($ruta) ||
            !str_starts_with($ruta, 'assets/img/perfiles/')
        ) {
            return;
        }

        $directorioPermitido = realpath(
            ROOT_PATH . '/assets/img/perfiles'
        );

        $archivo = realpath(ROOT_PATH . '/' . $ruta);

        if (
            $directorioPermitido !== false &&
            $archivo !== false &&
            str_starts_with(
                $archivo,
                $directorioPermitido . DIRECTORY_SEPARATOR
            ) &&
            is_file($archivo)
        ) {
            unlink($archivo);
        }
    }
}