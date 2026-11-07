<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Register
 * ==========================================================
 * Vista pública para registrar una cuenta nueva en Confypim.
 *
 * El formulario envía los datos al endpoint de autenticación.
 * La validación y el almacenamiento se realizan en el servidor.
 * ==========================================================
 */

$paginaActual = 'register';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php require_once ROOT_PATH . '/includes/head.php'; ?>
</head>

<body class="login-page">

    <main class="login-card">

        <!-- ==================================================
             Logo
             ================================================== -->

        <div class="login-logo">
            <img
                src="<?= IMG_URL ?>logo.png"
                alt="Logo de <?= APP_NAME ?>"
            >
        </div>

        <!-- ==================================================
             Encabezado
             ================================================== -->

        <header class="login-header">
            <h1>Crear cuenta</h1>
            <p>Regístrate para comenzar a utilizar <?= APP_NAME ?>.</p>
        </header>

        <!-- ==================================================
             Mensajes de respuesta
             ================================================== -->

        <div
            id="registerMessage"
            class="login-message"
            role="alert"
            aria-live="polite"
            hidden
        >
            <span id="registerAlertText"></span>
        </div>

        <!-- ==================================================
             Formulario de registro
             ================================================== -->

        <form id="registerForm" novalidate>

            <!-- Nombre -->
            <div class="login-field">
                <label for="nombre">Nombre *</label>

                <div class="login-input">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        autocomplete="given-name"
                        placeholder="Tu nombre"
                        required
                    >
                </div>
            </div>

            <!-- Apellido -->
            <div class="login-field">
                <label for="apellido">Apellido</label>

                <div class="login-input">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>

                    <input
                        type="text"
                        id="apellido"
                        name="apellido"
                        maxlength="100"
                        autocomplete="family-name"
                        placeholder="Tu apellido"
                    >
                </div>
            </div>

            <!-- Correo electrónico -->
            <div class="login-field">
                <label for="email">Correo electrónico *</label>

                <div class="login-input">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="100"
                        autocomplete="email"
                        placeholder="correo@ejemplo.com"
                        required
                    >
                </div>
            </div>

            <!-- Contraseña -->
            <div class="login-field">
                <label for="password">Contraseña *</label>

                <div class="login-input">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                        required
                    >
                </div>
            </div>

            <!-- Confirmar contraseña -->
            <div class="login-field">
                <label for="confirmar_password">Confirmar contraseña *</label>

                <div class="login-input">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>

                    <input
                        type="password"
                        id="confirmar_password"
                        name="confirmar_password"
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="Repite tu contraseña"
                        required
                    >
                </div>
            </div>

            <!-- Enviar formulario -->
            <button
                type="submit"
                id="registerButton"
                class="login-button"
            >
                Crear cuenta
            </button>

        </form>

        <!-- ==================================================
             Enlace al Login
             ================================================== -->

        <p class="login-footer">
            ¿Ya tienes una cuenta?
            <a href="<?= BASE_URL ?>index.php?page=login">
                Inicia sesión
            </a>
        </p>

    </main>

    <!-- ======================================================
         Procesamiento del registro
         ====================================================== -->

    <script>
        /**
         * ==================================================
         * Registro de usuario
         * ==================================================
         * Envía los datos al servidor mediante Fetch API
         * y presenta el mensaje devuelto por el endpoint.
         * ==================================================
         */

        const registerForm = document.getElementById('registerForm');
        const registerButton = document.getElementById('registerButton');
        const registerMessage = document.getElementById('registerMessage');
        const registerAlertText = document.getElementById('registerAlertText');

        /**
         * Mostrar un mensaje de respuesta.
         */
        function mostrarMensajeRegistro(mensaje, exito = false) {
            registerMessage.hidden = false;
            registerMessage.classList.toggle('login-message-error', !exito);
            registerMessage.classList.toggle('register-message-success', exito);
            registerAlertText.textContent = mensaje;
        }

        /**
         * Procesar el envío del formulario.
         */
        registerForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            registerMessage.hidden = true;

            const nombre = document.getElementById('nombre').value.trim();
            const apellido = document.getElementById('apellido').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmarPassword = document.getElementById('confirmar_password').value;

            /**
             * Validaciones iniciales en el navegador.
             * El servidor vuelve a validar todos los datos.
             */
            if (!nombre || !email || !password || !confirmarPassword) {
                mostrarMensajeRegistro('Completa todos los campos obligatorios.');
                return;
            }

            if (password.length < 8) {
                mostrarMensajeRegistro('La contraseña debe tener al menos 8 caracteres.');
                return;
            }

            if (password !== confirmarPassword) {
                mostrarMensajeRegistro('Las contraseñas no coinciden.');
                return;
            }

            registerButton.disabled = true;
            registerButton.textContent = 'Creando cuenta...';

            try {
                const response = await fetch(
                    '<?= BASE_URL ?>ajax/auth.php?accion=register',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            nombre: nombre,
                            apellido: apellido,
                            email: email,
                            password: password,
                            confirmar_password: confirmarPassword
                        })
                    }
                );

                const resultado = await response.json();

                if (response.ok && resultado.success) {
                    mostrarMensajeRegistro(resultado.message, true);
                    registerForm.reset();

                    /**
                     * Permitir que el usuario lea el mensaje
                     * antes de ir a la pantalla de Login.
                     */
                    window.setTimeout(function () {
                        window.location.href =
                            '<?= BASE_URL ?>index.php?page=login';
                    }, 1200);

                    return;
                }

                mostrarMensajeRegistro(
                    resultado.message || 'No fue posible crear la cuenta.'
                );

            } catch (error) {
                mostrarMensajeRegistro(
                    'No se pudo conectar con el servidor. Inténtalo nuevamente.'
                );

            } finally {
                registerButton.disabled = false;
                registerButton.textContent = 'Crear cuenta';
            }
        });
    </script>

</body>
</html>