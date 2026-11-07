<?php

/**
 * ==========================================================
 * Perfil
 * ==========================================================
 *
 * Vista encargada de mostrar y actualizar la información
 * personal del administrador.
 *
 * ==========================================================
 */

require_once ROOT_PATH . '/app/controllers/PerfilController.php';

$perfilController = new PerfilController();
$perfil = $perfilController->obtenerPerfil();

$cssPerfil = CSS_URL . 'perfil.css';
$cssPerfilFile = ROOT_PATH . '/assets/css/perfil.css';

$jsPerfil = JS_URL . 'perfil.js';
$jsPerfilFile = ROOT_PATH . '/assets/js/perfil.js';

/*
|--------------------------------------------------------------------------
| Información del usuario
|--------------------------------------------------------------------------
*/

$nombre = trim($perfil['nombre'] ?? '');
$apellido = trim($perfil['apellido'] ?? '');

$primerNombre = explode(' ', $nombre)[0] ?? '';
$primerApellido = explode(' ', $apellido)[0] ?? '';

$nombreMostrar = trim($primerNombre . ' ' . $primerApellido);

$iniciales = mb_strtoupper(
    mb_substr($primerNombre, 0, 1, 'UTF-8') .
    mb_substr($primerApellido, 0, 1, 'UTF-8'),
    'UTF-8'
);

$fotoPerfil = $perfil['foto_perfil'] ?? null;
$avatarSeleccionado = $perfil['avatar'] ?? null;

/*
|--------------------------------------------------------------------------
| Posición de la fotografía
|--------------------------------------------------------------------------
|
| Los valores van de 0 a 100 y se utilizan para definir
| el encuadre inicial de la imagen dentro del cuadrado.
|
*/

$posicionX = isset($perfil['foto_pos_x'])
    ? max(0, min(100, (int) $perfil['foto_pos_x']))
    : 50;

$posicionY = isset($perfil['foto_pos_y'])
    ? max(0, min(100, (int) $perfil['foto_pos_y']))
    : 50;

/*
|--------------------------------------------------------------------------
| Avatares disponibles
|--------------------------------------------------------------------------
|
| Se detectan los SVG existentes para no mantener una lista fija.
| Los nombres deben seguir el formato avatar-*.svg.
|
*/

$archivosAvatares = glob(
    ROOT_PATH . '/assets/img/avatars/avatar-*.svg'
) ?: [];

$avataresDisponibles = [];

foreach ($archivosAvatares as $archivo) {
    $nombreAvatar = pathinfo($archivo, PATHINFO_FILENAME);

    if (preg_match('/^avatar-[a-zA-Z0-9_-]+$/', $nombreAvatar)) {
        $avataresDisponibles[] = $nombreAvatar;
    }
}

sort($avataresDisponibles);

$avatarActualExiste = $avatarSeleccionado !== null
    && in_array($avatarSeleccionado, $avataresDisponibles, true);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include ROOT_PATH . '/includes/head.php'; ?>

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($cssPerfil, ENT_QUOTES, 'UTF-8'); ?>?v=<?= file_exists($cssPerfilFile) ? filemtime($cssPerfilFile) : time(); ?>">
</head>

<body>

<div class="app">

    <?php include ROOT_PATH . '/includes/sidebar.php'; ?>

    <div class="content">

        <?php include ROOT_PATH . '/includes/topbar.php'; ?>

        <main class="main">

            <div
                class="perfil-container"
                id="perfilApp"
                data-endpoint="<?= htmlspecialchars(BASE_URL . 'ajax/perfil.php', ENT_QUOTES, 'UTF-8'); ?>"
                data-base-url="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>"
                data-iniciales="<?= htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8'); ?>"
                data-avatar="<?= htmlspecialchars($avatarActualExiste ? $avatarSeleccionado : '', ENT_QUOTES, 'UTF-8'); ?>"
                data-avatares="<?= htmlspecialchars(json_encode($avataresDisponibles), ENT_QUOTES, 'UTF-8'); ?>">

                <section class="perfil-card">

                    <div
                        id="perfilMensaje"
                        class="perfil-mensaje"
                        role="status"
                        aria-live="polite"></div>

                    <!-- Foto de perfil -->
                    <section class="perfil-section">

                        <h2 class="perfil-section-title">Foto de perfil</h2>

                        <div class="perfil-foto-layout">

                            <!--
                                Contenedor de la fotografía.
                                El JavaScript utiliza este ID para permitir
                                arrastrar la imagen y modificar su encuadre.
                            -->
                            <div
                                id="perfilFotoContenedor"
                                class="perfil-foto-preview"
                                data-posicion-x="<?= $posicionX; ?>"
                                data-posicion-y="<?= $posicionY; ?>"
                                title="<?= !empty($fotoPerfil) ? 'Arrastra la fotografía para ajustar su encuadre' : 'Sube una fotografía para ajustar su encuadre'; ?>">

                                <img
                                    id="perfilFotoPreview"
                                    src="<?= !empty($fotoPerfil) ? htmlspecialchars(BASE_URL . $fotoPerfil, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                    alt="Fotografía de perfil"
                                    data-posicion-x="<?= $posicionX; ?>"
                                    data-posicion-y="<?= $posicionY; ?>"
                                    style="object-position: <?= $posicionX; ?>% <?= $posicionY; ?>%;"
                                    <?= empty($fotoPerfil) ? 'hidden' : ''; ?>>

                                <i
                                    id="perfilFotoPlaceholder"
                                    class="fa-regular fa-image perfil-foto-placeholder"
                                    aria-hidden="true"
                                    <?= !empty($fotoPerfil) ? 'hidden' : ''; ?>></i>

                            </div>

                            <div class="perfil-foto-info">

                                <p class="perfil-help-text">
                                    Esta fotografía solo se mostrará en tu perfil.
                                    No reemplaza el avatar de la barra superior.
                                </p>

                                <p class="perfil-help-text">
                                    Formatos permitidos: JPG, PNG y WebP.
                                    Tamaño máximo: 2 MB.
                                </p>

                                <p class="perfil-help-text">
                                    Después de subir la fotografía, haz clic sobre ella
                                    y arrástrala para ajustar el encuadre.
                                </p>

                                <div class="perfil-foto-actions">

                                    <label
                                        for="perfilFotoArchivo"
                                        class="perfil-btn perfil-btn-primary">
                                        <i class="fa-solid fa-upload"></i>
                                        Subir foto
                                    </label>

                                    <input
                                        type="file"
                                        id="perfilFotoArchivo"
                                        accept="image/jpeg,image/png,image/webp"
                                        hidden>

                                    <button
                                        type="button"
                                        id="btnEliminarFoto"
                                        class="perfil-btn perfil-btn-danger"
                                        <?= empty($fotoPerfil) ? 'disabled' : ''; ?>>
                                        <i class="fa-regular fa-trash-can"></i>
                                        Eliminar foto
                                    </button>

                                </div>

                            </div>

                        </div>

                    </section>

                    <div class="perfil-divider"></div>

                    <!-- Avatar del sistema -->
                    <section class="perfil-section">

                        <h2 class="perfil-section-title">Avatar del sistema</h2>

                        <div class="perfil-avatar-layout">

                            <div
                                id="perfilAvatarPreview"
                                class="perfil-avatar-preview">

                                <?php if ($avatarActualExiste): ?>

                                    <img
                                        src="<?= BASE_URL . 'assets/img/avatars/' . rawurlencode($avatarSeleccionado) . '.svg'; ?>"
                                        alt="Avatar seleccionado">

                                <?php else: ?>

                                    <?= htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8'); ?>

                                <?php endif; ?>

                            </div>

                            <div class="perfil-avatar-info">

                                <p id="perfilAvatarTexto">
                                    <?= $avatarActualExiste
                                        ? 'Tienes un avatar seleccionado.'
                                        : 'Estás utilizando tus iniciales.'; ?>
                                </p>

                                <p class="perfil-help-text">
                                    El avatar seleccionado aparecerá en la barra superior
                                    en lugar de tus iniciales.
                                </p>

                                <div class="perfil-foto-actions">

                                    <button
                                        type="button"
                                        id="btnElegirAvatar"
                                        class="perfil-btn perfil-btn-primary">
                                        <i class="fa-regular fa-face-smile"></i>
                                        Elegir avatar
                                    </button>

                                    <button
                                        type="button"
                                        id="btnRestaurarIniciales"
                                        class="perfil-btn"
                                        <?= !$avatarActualExiste ? 'disabled' : ''; ?>>
                                        Usar iniciales
                                    </button>

                                </div>

                            </div>

                        </div>

                    </section>

                    <div class="perfil-divider"></div>

                    <!-- Información personal -->
                    <section class="perfil-section">

                        <h2 class="perfil-section-title">Información personal</h2>

                        <form id="formPerfil" class="perfil-form">

                            <div class="perfil-form-grid">

                                <div class="perfil-field">
                                    <label for="perfilNombre">Nombre</label>

                                    <input
                                        type="text"
                                        id="perfilNombre"
                                        name="nombre"
                                        maxlength="100"
                                        value="<?= htmlspecialchars($perfil['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        autocomplete="given-name"
                                        required>
                                </div>

                                <div class="perfil-field">
                                    <label for="perfilApellido">Apellido</label>

                                    <input
                                        type="text"
                                        id="perfilApellido"
                                        name="apellido"
                                        maxlength="100"
                                        value="<?= htmlspecialchars($perfil['apellido'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        autocomplete="family-name"
                                        required>
                                </div>

                            </div>

                            <div class="perfil-field">
                                <label for="perfilDescripcion">Descripción</label>

                                <textarea
                                    id="perfilDescripcion"
                                    name="descripcion"
                                    maxlength="2000"
                                    placeholder="Cuéntanos algo sobre ti..."><?= htmlspecialchars($perfil['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>

                            <div class="perfil-field">
                                <label for="perfilEmail">Correo electrónico</label>

                                <input
                                    type="email"
                                    id="perfilEmail"
                                    value="<?= htmlspecialchars($perfil['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    autocomplete="email"
                                    readonly>

                                <p class="perfil-help-text">
                                    El cambio de correo estará disponible cuando
                                    implementemos la validación de la nueva dirección.
                                </p>
                            </div>

                            <div class="perfil-field">
                                <label for="perfilRol">Rol</label>

                                <input
                                    type="text"
                                    id="perfilRol"
                                    value="<?= htmlspecialchars(ucfirst($perfil['rol'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    readonly>
                            </div>

                            <div class="perfil-actions">

                                <button type="reset" class="perfil-btn">
                                    Descartar cambios
                                </button>

                                <button
                                    type="submit"
                                    class="perfil-btn perfil-btn-primary"
                                    id="btnGuardarPerfil">
                                    <i class="fa-regular fa-floppy-disk"></i>
                                    Guardar cambios
                                </button>

                            </div>

                        </form>

                    </section>

                </section>

            </div>

        </main>

        <?php include ROOT_PATH . '/includes/footer.php'; ?>

    </div>

</div>

<script
    src="<?= htmlspecialchars($jsPerfil, ENT_QUOTES, 'UTF-8'); ?>?v=<?= file_exists($jsPerfilFile) ? filemtime($jsPerfilFile) : time(); ?>"
    defer></script>

</body>
</html>