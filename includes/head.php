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

<title><?= isset($menu[$paginaActual]) ? $menu[$paginaActual]['tituloBrowser'] . ' | ' . APP_NAME : APP_NAME; ?></title>

<!-- =======================================================
    Hojas de estilo
======================================================== -->

<link rel="stylesheet" href="<?= CSS_URL ?>styles.css?v=<?= filemtime(ROOT_PATH . '/assets/css/styles.css'); ?>">

<link rel="stylesheet" href="<?= CSS_URL ?>forms.css?v=<?= filemtime(ROOT_PATH . '/assets/css/forms.css'); ?>">

<link rel="stylesheet" href="<?= CSS_URL ?>notifications.css?v=<?= filemtime(ROOT_PATH . '/assets/css/notifications.css'); ?>">

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