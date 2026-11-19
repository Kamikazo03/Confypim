<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Endpoint del módulo Reportes
 * ==========================================================
 * Define el contexto del módulo y reutiliza el endpoint
 * financiero compartido.
 * ==========================================================
 */

$_GET['tipo'] = 'reportes';

require __DIR__ . '/finanzas.php';
