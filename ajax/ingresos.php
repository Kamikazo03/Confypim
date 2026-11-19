<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Endpoint del módulo Ingresos
 * ==========================================================
 * Define el contexto del módulo y reutiliza el endpoint
 * financiero compartido.
 * ==========================================================
 */

$_GET['tipo'] = 'ingresos';

require __DIR__ . '/finanzas.php';
