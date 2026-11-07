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
 *
 * La configuración permite trabajar en dos entornos:
 * - Desarrollo local mediante XAMPP.
 * - Producción mediante las variables de entorno de Railway.
 * ==========================================================
 */

class Database
{
    /**
     * Servidor de base de datos.
     *
     * En Railway se obtiene mediante MYSQLHOST.
     * En XAMPP se utiliza localhost.
     */
    private static string $host = "localhost";

    /**
     * Puerto del servidor de base de datos.
     *
     * En Railway se obtiene mediante MYSQLPORT.
     * En XAMPP se utiliza el puerto 3306.
     */
    private static string $port = "3306";

    /**
     * Nombre de la base de datos.
     *
     * En Railway se obtiene mediante MYSQLDATABASE.
     * En XAMPP se utiliza confypim.
     */
    private static string $database = "confypim";

    /**
     * Usuario de la base de datos.
     *
     * En Railway se obtiene mediante MYSQLUSER.
     * En XAMPP se utiliza root.
     */
    private static string $username = "root";

    /**
     * Contraseña de la base de datos.
     *
     * En Railway se obtiene mediante MYSQLPASSWORD.
     * En XAMPP se utiliza una contraseña vacía.
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
     * La configuración se obtiene de las variables de entorno
     * cuando están disponibles; de lo contrario, se utilizan
     * los valores predeterminados para XAMPP.
     *
     * @return PDO
     */
    public static function connect(): PDO
    {
        if (self::$connection === null) {

            try {

                /**
                 * Obtener la configuración del entorno.
                 *
                 * Railway proporciona estas variables
                 * desde la configuración del servicio.
                 *
                 * Si no existen, se conservan los valores
                 * predeterminados del entorno local.
                 */
                self::$host = getenv('MYSQLHOST') ?: 'localhost';

                self::$port = getenv('MYSQLPORT') ?: '3306';

                self::$database = getenv('MYSQLDATABASE') ?: 'confypim';

                self::$username = getenv('MYSQLUSER') ?: 'root';

                $passwordEntorno = getenv('MYSQLPASSWORD');

                self::$password = $passwordEntorno !== false
                    ? $passwordEntorno
                    : '';

                /**
                 * Construir la cadena de conexión.
                 */
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    self::$host,
                    self::$port,
                    self::$database
                );

                /**
                 * Crear la conexión PDO.
                 */
                self::$connection = new PDO(
                    $dsn,
                    self::$username,
                    self::$password
                );

                /**
                 * Configurar los atributos de PDO.
                 *
                 * ERRMODE_EXCEPTION:
                 * Genera excepciones cuando ocurre un error.
                 *
                 * DEFAULT_FETCH_MODE:
                 * Devuelve los resultados como arreglos asociativos.
                 *
                 * EMULATE_PREPARES:
                 * Desactiva la emulación de consultas preparadas.
                 */
                self::$connection->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                self::$connection->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );

                self::$connection->setAttribute(
                    PDO::ATTR_EMULATE_PREPARES,
                    false
                );

            } catch (PDOException $exception) {

                /**
                 * Registrar el error técnico en el servidor.
                 *
                 * No se muestran las credenciales ni los detalles
                 * internos de la conexión al usuario.
                 */
                error_log(
                    'Error al conectar con la base de datos: ' .
                    $exception->getMessage()
                );

                throw new RuntimeException(
                    'No fue posible conectar con la base de datos.'
                );
            }
        }

        return self::$connection;
    }
}