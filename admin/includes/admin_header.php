<?php
require_once __DIR__ . '/auth.php';

$usuario = usuario_actual();
$titulo_pagina = $titulo_pagina ?? 'Panel Administrativo | Players of Dominion';
$actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($titulo_pagina) ?></title>
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="../assets/css/stilos.css" />
    <link rel="stylesheet" href="../assets/css/admin.css" />
  </head>
  <body class="admin-body">
    <div class="admin-shell">
      <!-- ============ BARRA LATERAL — exclusiva del panel ============ -->
      <aside class="admin-sidebar">
        <div class="admin-brand">
          <img src="../assets/img/logo.png" alt="Players of Dominion" />
          <small>Panel&nbsp;Admin</small>
        </div>

        <nav class="admin-nav" aria-label="Navegación del panel">
          <a class="<?= $actual === 'index.php' ? 'is-active' : '' ?>" href="index.php">🏠 Dashboard</a>
          <a class="<?= $actual === 'productos.php' ? 'is-active' : '' ?>" href="productos.php">📦 Productos</a>
          <a class="<?= $actual === 'ventas.php' ? 'is-active' : '' ?>" href="ventas.php">🧾 Ventas</a>
          <a class="<?= $actual === 'estadisticas.php' ? 'is-active' : '' ?>" href="estadisticas.php">📊 Estadísticas</a>
          <a class="<?= $actual === 'reportes.php' ? 'is-active' : '' ?>" href="reportes.php">📈 Reportes</a>
          <div class="admin-nav-sep"></div>
          <a href="../index.php">🏠 Ver tienda</a>
          <a href="../cuenta.php?salir=1">🚪 Cerrar sesión</a>
        </nav>

        <div class="admin-sidebar-foot">
          <p style="font-size:12px;color:var(--text-muted)">
            Sesión activa<br />
            <strong style="color:var(--text)"><?= e($usuario['nombre']) ?></strong>
          </p>
        </div>
      </aside>

      <!-- ============ CONTENIDO ============ -->
      <main class="admin-main">
        <div class="admin-topbar">
          <div>
            <span class="admin-badge">Administración</span>
            <h1><?= e($admin_titulo ?? 'Panel Administrativo') ?></h1>
            <?php if (!empty($admin_subtitulo)): ?><p><?= e($admin_subtitulo) ?></p><?php endif; ?>
          </div>
        </div>
