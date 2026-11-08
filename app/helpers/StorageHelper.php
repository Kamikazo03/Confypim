<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Storage Helper
 * ==========================================================
 *
 * Gestiona las rutas físicas y los directorios de
 * almacenamiento de archivos de Confypim.
 *
 * Permite utilizar almacenamiento local durante el desarrollo
 * y volúmenes persistentes en entornos de producción.
 *
 * ==========================================================
 */

class StorageHelper
{
    /**
     * Directorios permitidos para almacenar archivos.
     */
    private const DIRECTORIOS_PERMITIDOS = [
        'perfiles',
        'productos',
        'insumos',
        'analiticas',
        'temporales'
    ];

    /**
     * Obtiene la ruta raíz del almacenamiento.
     */
    public static function obtenerRutaBase(): string
    {
        $rutaConfigurada = getenv('STORAGE_PATH');

        if (
            is_string($rutaConfigurada) &&
            trim($rutaConfigurada) !== ''
        ) {
            return rtrim(trim($rutaConfigurada), '/\\');
        }

        $entorno = getenv('APP_ENV') ?: 'development';

        if ($entorno === 'production') {
            $rutaRailway = getenv('RAILWAY_VOLUME_MOUNT_PATH');

            if (
                is_string($rutaRailway) &&
                trim($rutaRailway) !== ''
            ) {
                return rtrim(trim($rutaRailway), '/\\');
            }

            throw new RuntimeException(
                'No se ha configurado la ruta del almacenamiento persistente.'
            );
        }

        // En XAMPP se conserva la ubicación actual de las imágenes.
        return ROOT_PATH . '/assets/img';
    }

    /**
     * Obtiene la ruta física de un directorio permitido.
     */
    public static function obtenerDirectorio(string $directorio): string
    {
        if (!in_array($directorio, self::DIRECTORIOS_PERMITIDOS, true)) {
            throw new InvalidArgumentException(
                'El directorio de almacenamiento no está permitido.'
            );
        }

        $ruta = self::obtenerRutaBase()
            . DIRECTORY_SEPARATOR
            . $directorio;

        if (
            !is_dir($ruta) &&
            !mkdir($ruta, 0755, true) &&
            !is_dir($ruta)
        ) {
            throw new RuntimeException(
                'No se pudo preparar el directorio de almacenamiento.'
            );
        }

        if (!is_writable($ruta)) {
            throw new RuntimeException(
                'El directorio de almacenamiento no tiene permisos de escritura.'
            );
        }

        return $ruta;
    }

    /**
     * Obtiene la ruta física de un archivo almacenado.
     */
    public static function obtenerRutaArchivo(
        string $directorio,
        string $nombreArchivo
    ): string {
        if (
            $nombreArchivo === '' ||
            basename($nombreArchivo) !== $nombreArchivo ||
            str_contains($nombreArchivo, "\0")
        ) {
            throw new InvalidArgumentException(
                'El nombre del archivo no es válido.'
            );
        }

        return self::obtenerDirectorio($directorio)
            . DIRECTORY_SEPARATOR
            . $nombreArchivo;
    }
}