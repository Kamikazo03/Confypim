<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Database
 * ==========================================================
 *
 * Clase encargada de administrar la conexión a la base
 * de datos mediante el patrón Singleton.
 *
 * Crea una única conexión PDO y configura los atributos de PDO.
 * Reutilizar la misma conexión durante toda la ejecución.
 * ==========================================================
 */

class Database
{
    /**
     * Servidor de base de datos.
     */
    private static string $host = "localhost";

    /**
     * Nombre de la base de datos.
     */
    private static string $database = "confipym";

    /**
     * Usuario de la base de datos.
     */
    private static string $username = "root";

    /**
     * Contraseña de la base de datos.
     */
    private static string $password = "";

    /**
     * Instancia única de la conexión.
     */
    private static ?PDO $connection = null;

    /**
     * Evita crear instancias de la clase.
     */
    private function __construct()
    {
    }

    /**
     * Obtener la conexión a la base de datos.
     *
     * Si la conexión ya existe, se reutiliza.
     * En caso contrario se crea una nueva.
     *
     * @return PDO
     */
    public static function connect(): PDO
    {
        if (self::$connection === null) {

            try {

                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=utf8mb4',
                    self::$host,
                    self::$database
                );

                self::$connection = new PDO(
                    $dsn,
                    self::$username,
                    self::$password
                );

                self::$connection->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                self::$connection->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );

            } catch (PDOException $exception) {

                die(
                    'Error al conectar con la base de datos: ' .
                    $exception->getMessage()
                );
            }
        }

        return self::$connection;
    }
}