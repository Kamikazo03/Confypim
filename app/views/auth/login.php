<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Login
 * ==========================================================
 * Vista encargada de mostrar el formulario de autenticación
 * de Confypim.
 *
 * El acceso está destinado únicamente a administradores.
 * ==========================================================
 */

$paginaActual = 'login';
?>

<!DOCTYPE html>
<html lang="es">
    
<head>
    <?php require_once ROOT_PATH . '/includes/head.php'; ?>
</head>

<body class="login-page">

    <!-- ======================================================
         Tarjeta principal
    ======================================================= -->

    <main class="login-card">

        <!-- ==================================================
             Logo
        =================================================== -->

        <div class="login-logo">
            <img
                src="<?= IMG_URL; ?>logo.png"
                alt="Logo <?= APP_NAME; ?>"
            >
        </div>

        <!-- ==================================================
             Encabezado
        =================================================== -->

        <div class="login-header">

            <h1>
                <?= APP_NAME; ?>
            </h1>

            <p>
                Inicie sesión para acceder al sistema
            </p>

        </div>

        <!-- ==================================================
             Formulario
        =================================================== -->

        <form
            id="loginForm"
            autocomplete="on"
        >

            <!-- ==================================================
                 Correo electrónico
            =================================================== -->

            <div class="login-field">

                <label for="email">
                    Correo electrónico
                </label>

                <div class="login-input">

                    <i class="fa-regular fa-envelope"></i>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="correo@ejemplo.com"
                        autocomplete="email"
                        required
                    >

                </div>

            </div>

            <!-- ==================================================
                 Contraseña
            =================================================== -->

            <div class="login-field">

                <label for="password">
                    Contraseña
                </label>

                <div class="login-input">

                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ingrese su contraseña"
                        autocomplete="current-password"
                        required
                    >

                </div>

            </div>

            <!-- ==================================================
                 Mensaje de formulario
            =================================================== -->

            <div
                id="loginMessage"
                class="login-message login-message-error"
                hidden
            >
                <i class="fa-solid fa-circle-exclamation"></i>

                <span id="loginAlertText"></span>
            </div>

            <!-- ==================================================
                 Botón
            =================================================== -->

            <button
                type="submit"
                id="loginButton"
                class="login-button"
            >
                <span>
                    Iniciar sesión
                </span>
            </button>

        </form>

        <!-- ==================================================
             Pie
        =================================================== -->

        <p class="login-footer">
            Acceso exclusivo para administradores.
        </p>

        <p class="login-footer">
            ¿No tienes una cuenta?
            <a href="<?= BASE_URL ?>index.php?page=register">
                Crear cuenta
            </a>
        </p>

    </main>

    <!-- ======================================================
         JavaScript
    ======================================================= -->

    <script>

        /**
         * ======================================================
         * Elementos del formulario
         * ======================================================
         */

        const loginForm =
            document.getElementById('loginForm');

        const loginButton =
            document.getElementById('loginButton');

        const loginMessage =
            document.getElementById('loginMessage');

        const loginAlertText =
            document.getElementById('loginAlertText');

        /**
         * ======================================================
         * Mostrar mensaje
         * ======================================================
         */

        function mostrarMensaje(mensaje) {

            loginAlertText.textContent = mensaje;

            loginMessage.hidden = false;

        }

        /**
         * ======================================================
         * Ocultar mensaje
         * ======================================================
         */

        function ocultarMensaje() {

            loginAlertText.textContent = '';

            loginMessage.hidden = true;

        }

        /**
         * ======================================================
         * Envío del formulario
         * ======================================================
         */

        loginForm.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();

                /**
                 * Ocultar mensajes anteriores.
                 */

                ocultarMensaje();

                /**
                 * Obtener datos.
                 */

                const email =
                    document.getElementById('email').value.trim();

                const password =
                    document.getElementById('password').value;

                /**
                 * Desactivar botón.
                 */

                loginButton.disabled = true;

                loginButton.textContent = 'Verificando...';

                try {

                    /**
                     * ==================================================
                     * Petición de autenticación
                     * ==================================================
                     */

                    const response = await fetch(
                        '<?= BASE_URL; ?>ajax/auth.php?accion=login',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type': 'application/json'
                            },

                            body: JSON.stringify({
                                email: email,
                                password: password
                            })
                        }
                    );

                    /**
                     * Obtener respuesta.
                     */

                    const resultado =
                        await response.json();

                    /**
                     * ==================================================
                     * Login correcto
                     * ==================================================
                     */

                    if (resultado.success) {

                        window.location.href =
                            '<?= BASE_URL; ?>index.php';

                        return;
                    }

                    /**
                     * ==================================================
                     * Login incorrecto
                     * ==================================================
                     */

                    mostrarMensaje(
                        resultado.message ||
                        'Las credenciales no son correctas.'
                    );

                } catch (error) {

                    console.error(error);

                    mostrarMensaje(
                        'No fue posible comunicarse con el servidor.'
                    );

                } finally {

                    /**
                     * Reactivar botón.
                     */

                    loginButton.disabled = false;

                    loginButton.textContent =
                        'Iniciar sesión';

                }

            }
        );

    </script>

</body>

</html>