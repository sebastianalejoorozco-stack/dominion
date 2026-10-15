<?php
/**
 * Guard de autenticación del panel administrativo.
 * Se incluye al inicio de CADA página bajo /admin, antes de imprimir
 * cualquier HTML. Si no hay sesión, o la sesión no tiene rol admin,
 * corta la ejecución y redirige — así el panel queda completamente
 * separado de la interfaz pública: nadie llega a admin/*.php sin
 * haber iniciado sesión como administrador.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../db.php';

exigir_admin();
