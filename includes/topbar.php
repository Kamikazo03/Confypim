<!-- ==================================================================
Topbar
==================================================================
 * Barra superior principal del sistema.
 * Muestra el título de la página y los datos del usuario autenticado.
====================================================================== -->

<?php

$usuarioSesion = $_SESSION['usuario'] ?? [];

// Obtener el nombre y apellido guardados en la sesión.
$nombreBase = trim((string) ($usuarioSesion['nombre'] ?? $nombreUsuario ?? 'Usuario'));
$apellidoBase = trim((string) ($usuarioSesion['apellido'] ?? ''));

// Obtener solamente el primer nombre.
$partesNombre = preg_split('/\s+/', $nombreBase) ?: [];
$primerNombre = $partesNombre[0] ?? 'Usuario';

// Obtener solamente el primer apellido.
$partesApellido = preg_split('/\s+/', $apellidoBase) ?: [];
$primerApellido = $partesApellido[0] ?? '';

// Preparar el nombre que se mostrará en el topbar.
$nombreMostrar = trim($primerNombre . ' ' . $primerApellido);

if ($nombreMostrar === '') {
    $nombreMostrar = 'Usuario';
}

// Crear iniciales para cuando no exista una fotografía ni un avatar seleccionado.
$iniciales = strtoupper(
    mb_substr($primerNombre, 0, 1, 'UTF-8') .
    ($primerApellido !== '' ? mb_substr($primerApellido, 0, 1, 'UTF-8') : '')
);

// Obtener la fotografía de perfil guardada en la sesión.
$fotoPerfil = (string) ($usuarioSesion['foto_perfil'] ?? '');

// Obtener y validar la posición del encuadre guardada en la sesión.
$posicionX = filter_var($usuarioSesion['foto_pos_x'] ?? 50,FILTER_VALIDATE_INT);
$posicionY = filter_var($usuarioSesion['foto_pos_y'] ?? 50,FILTER_VALIDATE_INT);

if ($posicionX === false || $posicionX < 0 || $posicionX > 100) {$posicionX = 50;}
if ($posicionY === false || $posicionY < 0 || $posicionY > 100) {$posicionY = 50;}

// Validar el nombre del archivo SVG antes de utilizarlo.
$avatarSeleccionado = (string) ($usuarioSesion['avatar'] ?? '');

if (!preg_match('/^avatar-[A-Za-z0-9_-]+$/', $avatarSeleccionado)) {
    $avatarSeleccionado = '';
}

$rolMostrar = (string) ($usuarioSesion['rol'] ?? $rolUsuario ?? '');
?>

<header class="topbar">
    <div class="topbar-left">
        <h1>
            <?= htmlspecialchars(
                $menu[$paginaActual]['titulo'] ?? APP_NAME,
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h1>

        <p class="subtitle">
            <?= htmlspecialchars(
                $menu[$paginaActual]['descripcion'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </p>
    </div>

    <div class="topbar-right">
        <?php if ($mostrarNotificaciones ?? true): ?>
            <button
                class="notification-btn"
                type="button"
                title="Notificaciones"
                aria-label="Ver notificaciones"
            >
                <i class="fa-regular fa-bell"></i>
            </button>
        <?php endif; ?>

        <a
            href="<?= BASE_URL; ?>index.php?page=perfil"
            class="user-card"
            title="Ver perfil"
        >
            <div class="user-avatar">
                <?php if ($fotoPerfil !== ''): ?>
                    <img
                        src="<?= BASE_URL; ?>ajax/perfil.php?accion=foto"
                        alt="Fotografía de perfil"
                        style="object-position: <?= $posicionX; ?>% <?= $posicionY; ?>%;"
                    >
                <?php elseif ($avatarSeleccionado !== ''): ?>
                    <img
                        src="<?= BASE_URL; ?>assets/img/avatars/<?= htmlspecialchars(
                            $avatarSeleccionado,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>.svg"
                        alt="Avatar del usuario"
                    >
                <?php else: ?>
                    <span>
                        <?= htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="user-info">
                <span class="user-name">
                    <?= htmlspecialchars($nombreMostrar, ENT_QUOTES, 'UTF-8'); ?>
                </span>

                <span class="user-role">
                    <?= htmlspecialchars(
                        ucfirst($rolMostrar),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </span>
            </div>
        </a>
    </div>
</header>