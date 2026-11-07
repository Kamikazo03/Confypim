<!-- ==========================================================
Footer
==========================================================
 * Pie de página común del sistema.
 *
 * Muestra la información general de la aplicación y carga
 * todos los archivos JavaScript compartidos por el sistema.
 *
 * Si una vista requiere un archivo JavaScript adicional,
 * este puede indicarse mediante las variables:
 *
 *  $jsPagina
 *  $jsPaginaFile
========================================================== -->

<footer class="footer">
    <span>
        © <?= date('Y'); ?> <?= APP_NAME; ?> · Sistema de Gestión Financiera
    </span>
    <span>
        Versión <?= APP_VERSION; ?>
    </span>
</footer>

<!-- ======================================================
     JavaScript global
======================================================= -->

<script src="<?= JS_URL ?>api.js?v=<?= filemtime(ROOT_PATH . '/assets/js/api.js'); ?>"></script>

<script src="<?= JS_URL ?>notifications.js?v=<?= filemtime(ROOT_PATH . '/assets/js/notifications.js'); ?>"></script>

<script src="<?= JS_URL ?>main.js?v=<?= filemtime(ROOT_PATH . '/assets/js/main.js'); ?>"></script>

<script src="<?= JS_URL ?>preferences.js?v=<?= filemtime(ROOT_PATH . '/assets/js/preferences.js'); ?>"></script>

<!-- ======================================================
     JavaScript específico de la vista
======================================================= -->

<?php if (isset($jsPagina, $jsPaginaFile)): ?>

<script src="<?= $jsPagina; ?>?v=<?= filemtime($jsPaginaFile); ?>"></script>

<?php endif; ?>