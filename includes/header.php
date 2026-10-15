<?php
require_once __DIR__ . '/../db.php';
$usuario = usuario_actual();
$titulo_pagina = $titulo_pagina ?? 'Players of Dominion | Hardware Gamer y Periféricos';
$descripcion_pagina = $descripcion_pagina ?? 'Players of Dominion — PC gamer, tarjetas gráficas, monitores y periféricos premium para jugadores competitivos.';
$actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($titulo_pagina) ?></title>
    <meta name="description" content="<?= e($descripcion_pagina) ?>" />
    <link rel="stylesheet" href="assets/css/stilos.css" />
  </head>
  <body>
    <!-- ============ ENCABEZADO ============ -->
    <header class="site-header">
      <div class="shell header-inner">
        <a class="site-logo" href="index.php">
          <img src="assets/img/logo.png" alt="Players of Dominion" />
        </a>

        <nav class="main-nav" aria-label="Navegación principal">
          <ul>
            <li><a class="<?= $actual === 'index.php' ? 'is-active' : '' ?>" href="index.php">INICIO</a></li>
            <li><a class="<?= $actual === 'catalogo.php' ? 'is-active' : '' ?>" href="catalogo.php">TIENDA</a></li>
            <li><a href="catalogo.php#categorias">CATEGORÍAS</a></li>
            <li><a href="catalogo.php#marcas">MARCAS</a></li>
            <li><a href="catalogo.php?oferta=1">OFERTAS</a></li>
          </ul>
        </nav>

        <div class="header-actions">
          <form class="search-field" role="search" action="catalogo.php" method="get">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" aria-hidden="true">
              <circle cx="11" cy="11" r="7" />
              <line x1="16.5" y1="16.5" x2="21" y2="21" />
            </svg>
            <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>"
                   placeholder="Buscar hardware, GPU, monitores..." aria-label="Buscar productos" />
          </form>

          <a class="cart-button" href="carrito.php">
            🛒 Carrito <span class="cart-badge"><?= carrito_cantidad() ?></span>
          </a>

          <?php if (es_admin()): ?>
            <a class="btn btn-outline admin-link" href="admin/index.php">Panel Admin</a>
          <?php endif; ?>

          <?php if ($usuario): ?>
            <!-- Sesión activa: avatar circular con borde neón -->
            <a class="user-avatar" href="cuenta.php" title="<?= e($usuario['nombre']) ?>">
              <?php if (!empty($usuario['avatar_url'])): ?>
                <img src="<?= e($usuario['avatar_url']) ?>" alt="Perfil de <?= e($usuario['nombre']) ?>" />
              <?php else: ?>
                <?= e(mb_strtoupper(mb_substr($usuario['nombre'], 0, 1))) ?>
              <?php endif; ?>
            </a>
          <?php else: ?>
            <a class="btn btn-outline" href="login.php">Ingresar</a>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <main>
      <?php if (isset($_GET['acceso_denegado'])): ?>
        <div class="shell" style="padding-top:18px">
          <div class="alert" style="border-color:#ff4d6d;background:rgba(255,77,109,.12)">
            No tienes permisos de administrador para ver esa sección.
          </div>
        </div>
      <?php endif; ?>
