<!-- ==========================================================
Sidebar
==========================================================
 * Menú lateral principal del sistema.
 * Construye automáticamente las opciones de navegación
 * utilizando la configuración definida en config/menu.php.
========================================================== -->

<?php
/** @var array $menu */
/** @var string $paginaActual */
?>

<aside class="sidebar" id="sidebar">

    <!-- ======================================================
         Logo
    ======================================================= -->

    <h2 class="logo"><?= APP_NAME ?></h2>

    <img class="imagen" src="<?= IMG_URL ?>logo.png?v=<?= filemtime(ROOT_PATH . '/assets/img/logo.png'); ?> "alt="Logo <?= APP_NAME ?>">

    <!-- ======================================================
         Botón colapsar menú
    ======================================================= -->

    <button class="toggle-btn" id="toggleSidebar" aria-label="Mostrar u ocultar menú"><i class="fa-solid fa-bars"></i></button>

    <!-- ======================================================
        Navegación
    ======================================================= -->

    <nav class="menu">

        <?php foreach ($menu as $clave => $item): ?>
            <?php if (!$item['activo']) continue; ?>
            <?php if ($clave === 'mantenimiento') continue; ?>

            <a href="<?= $item['url']; ?>" class="<?= ($paginaActual === $clave)? 'active': ''; ?>">
                <span><?= $item['icono']; ?></span>
                <span class="text"><?= $item['titulo']; ?></span>
            </a>

        <?php endforeach; ?>

    </nav>

    <!-- ======================================================
         Cerrar sesión
    ======================================================= -->

    <div class="sidebar-footer">

        <a href="<?= BASE_URL; ?>logout.php" class="logout-link" onclick="return confirmarCierreSesion();">
            <span> <i class="fa-solid fa-right-from-bracket"></i> </span>
            <span class="logout-text"> Cerrar sesión </span>
        </a>

    </div>

</aside>

<!-- ==========================================================
     Confirmación de cierre de sesión
========================================================== -->

<script>

    /**
     * ======================================================
     * Confirmar cierre de sesión
     * ======================================================
     *
     * Solicita confirmación antes de finalizar
     * la sesión actual.
     */

    function confirmarCierreSesion() {

        return confirm('¿Está seguro de que desea cerrar sesión?');

    }

</script>