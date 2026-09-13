package confypim.config;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

/**
 * ==========================================================
 * Conexion
 * ==========================================================
 *
 * Gestiona la conexión entre la aplicación Java y la base
 * de datos MySQL mediante JDBC.
 *
 * ==========================================================
 */
public class Conexion {

    private static final String URL =
            "jdbc:mysql://localhost:3306/confypim"
            + "?useSSL=false"
            + "&serverTimezone=America/Bogota"
            + "&allowPublicKeyRetrieval=true";

    private static final String USUARIO = "root";

    private static final String PASSWORD = "";

    private Conexion() {
    }

    public static Connection conectar() throws SQLException {

        return DriverManager.getConnection(
                URL,
                USUARIO,
                PASSWORD
        );
    }
}