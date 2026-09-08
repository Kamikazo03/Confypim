<!-- ==========================================================
Topbar
==========================================================
 * Barra superior principal del sistema.
 *
 * Muestra el título de la página actual, su descripción,
 * el acceso a notificaciones y la información del usuario
 * autenticado.
========================================================== -->

<?php
/** @var array $menu */
/** @var string $paginaActual */
/** @var string $nombreUsuario */
/** @var string $rolUsuario */

/*
|--------------------------------------------------------------------------
| Iniciales del usuario
|--------------------------------------------------------------------------
|
| Obtiene la primera letra del nombre y del apellido para
| mostrarlas dentro del avatar del usuario.
|
*/

$partesNombre = explode(' ', trim($nombreUsuario));

$iniciales = strtoupper(
    substr($partesNombre[0] ?? '', 0, 1) .
    substr($partesNombre[1] ?? '', 0, 1)
);
?>

<header class="topbar">

    <!-- ======================================================
         Información de la página
    ======================================================= -->

    <div class="topbar-left">

        <h1><?= $menu[$paginaActual]['titulo']; ?></h1>

        <p class="subtitle"> <?= $menu[$paginaActual]['descripcion']; ?> </p>

    </div>

    <!-- ======================================================
         Usuario
    ======================================================= -->

    <div class="topbar-right">

        <button class="notification-btn" title="Notificaciones" aria-label="Ver notificaciones">

            <i class="fa-regular fa-bell"></i>

        </button>

        <div class="user-card">

            <div class="user-avatar"> <?= $iniciales; ?></div>

            <div class="user-info">

                <span class="user-name">
                    <?= $nombreUsuario ?? 'Usuario'; ?>
                </span>

                <span class="user-role">
                    <?= $rolUsuario ?? ''; ?>
                </span>

            </div>
        
        </div>
    
    </div>

</header>