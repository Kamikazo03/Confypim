<?php

declare(strict_types=1);

/**
 * ==========================================================
 * Endpoint del módulo Gastos
 * ==========================================================
 * Define el contexto del módulo y reutiliza el endpoint
 * financiero compartido.
 * ==========================================================
 */

$_GET['tipo'] = 'gastos';

require __DIR__ . '/finanzas.php';
