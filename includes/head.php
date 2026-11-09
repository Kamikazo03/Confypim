<!-- ==========================================================
Head común del sistema
==========================================================
* Contiene la configuración general del documento HTML,
* hojas de estilo, fuentes e iconografía utilizadas por
* todas las vistas de Confipym.
========================================================== -->

<!-- =======================================================
    Metadatos
======================================================== -->
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- =======================================================
    Estado inicial del Sidebar
    Se ejecuta antes de cargar los estilos para evitar que
    el menú aparezca expandido antes de restaurar su estado.
======================================================== -->
<script>
    (function () {
        const raiz = document.documentElement;
        const esMovil = window.matchMedia('(max-width: 768px)').matches;
        let estadoGuardado = 'expanded';

        try {
            estadoGuardado = localStorage.getItem('sidebarState') || 'expanded';
        } catch (error) {
            // Si LocalStorage no está disponible, se utiliza el estado predeterminado.
        }

        if (esMovil || estadoGuardado === 'collapsed') {
            raiz.classList.add('sidebar-initial-collapsed');
        }
    })();
</script>

<!-- =======================================================
    Identidad visual y favicon de Confypim
    Los archivos se almacenan en assets/img/favicon/.
    Se utiliza APP_VERSION para actualizar la caché cuando
    se publique una nueva versión del icono.
======================================================== -->

<link rel="icon" type="image/x-icon" href="<?= IMG_URL ?>favicon/favicon.ico?v=<?= APP_VERSION; ?>">

<link rel="icon" type="image/png" sizes="16x16" href="<?= IMG_URL ?>favicon/favicon-16x16.png?v=<?= APP_VERSION; ?>">

<link rel="icon" type="image/png" sizes="32x32" href="<?= IMG_URL ?>favicon/favicon-32x32.png?v=<?= APP_VERSION; ?>">

<link rel="apple-touch-icon" sizes="180x180" href="<?= IMG_URL ?>favicon/apple-touch-icon.png?v=<?= APP_VERSION; ?>">

<link rel="manifest" href="<?= BASE_URL ?>site.webmanifest?v=<?= APP_VERSION; ?>">

<meta name="theme-color" content="#F8F5F2">

<?php $titulosEspeciales = ['login' => 'Iniciar sesión', 'register' => 'Crear cuenta', ];

$tituloPagina = $titulosEspeciales[$paginaActual] ?? ($menu[$paginaActual]['tituloBrowser'] ?? null);

$tituloNavegador = $tituloPagina !== null ? $tituloPagina . ' | ' . APP_NAME : APP_NAME; ?>

<title><?= htmlspecialchars($tituloNavegador, ENT_QUOTES, 'UTF-8'); ?></title>

<!-- =======================================================
    Hojas de estilo
======================================================== -->

<link rel="stylesheet" href="<?= CSS_URL ?>styles.css?v=<?= filemtime(ROOT_PATH . '/assets/css/styles.css'); ?>">

<link rel="stylesheet" href="<?= CSS_URL ?>forms.css?v=<?= filemtime(ROOT_PATH . '/assets/css/forms.css'); ?>">

<link rel="stylesheet" href="<?= CSS_URL ?>notifications.css?v=<?= filemtime(ROOT_PATH . '/assets/css/notifications.css'); ?>">

<link rel="stylesheet" href="<?= CSS_URL ?>auth.css?v=<?= filemtime(ROOT_PATH . '/assets/css/auth.css'); ?>">

<link rel="stylesheet" href="<?= CSS_URL ?>perfil.css?v=<?= filemtime(ROOT_PATH . '/assets/css/perfil.css'); ?>">

<!-- =======================================================
    Google Fonts
======================================================== -->

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- =======================================================
    Font Awesome
======================================================== -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">