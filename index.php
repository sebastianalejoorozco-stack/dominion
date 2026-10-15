<?php
require_once __DIR__ . '/db.php';

$titulo_pagina = 'Players of Dominion | Tienda de Hardware Gamer y Periféricos';
$descripcion_pagina = 'PC gamer, tarjetas gráficas, monitores 240Hz y periféricos de torneo. Arma tu setup ganador con Players of Dominion.';

$destacados = db()
    ->query('SELECT id, nombre, etiqueta, precio, precio_antes, imagen_url
             FROM productos WHERE destacado = 1 ORDER BY creado_en DESC LIMIT 4')
    ->fetchAll();

require __DIR__ . '/includes/header.php';
?>
      <!-- ============ HERO ============ -->
      <section class="hero">
        <div class="shell">
          <div class="hero-card">
            <div>
              <span class="eyebrow">Hardware Gamer Avanzado</span>
              <h1>Perfecciona tu Setup <span>Competitivo</span></h1>
              <p>
                Equipos armados a la medida, pantallas de 240Hz y periféricos de nivel torneo:
                ensamblados, probados en banco y enviados por especialistas. Cero cuellos de botella,
                puro rendimiento.
              </p>
              <div class="hero-actions">
                <a class="btn btn-primary" href="catalogo.php">Explorar la Tienda ⚡</a>
                <a class="btn btn-outline" href="catalogo.php?oferta=1">Ver Ofertas</a>
              </div>
            </div>
            <div class="hero-banner">
              <img src="assets/img/logo.png" alt="Players of Dominion" />
            </div>
          </div>
        </div>
      </section>

      <!-- ============ PRODUCTOS DESTACADOS ============ -->
      <section class="section" id="destacados">
        <div class="shell">
          <div class="section-header">
            <h2>Hardware y Periféricos Destacados</h2>
            <p>Componentes seleccionados con stock verificado y garantía.</p>
          </div>

          <div class="product-grid">
            <?php foreach ($destacados as $p): ?>
              <article class="product-card">
                <?php if ($p['etiqueta']): ?>
                  <span class="product-tag"><?= e($p['etiqueta']) ?></span>
                <?php endif; ?>
                <?php if ($p['imagen_url']): ?>
                  <img class="product-image" src="<?= e($p['imagen_url']) ?>" alt="<?= e($p['nombre']) ?>" loading="lazy" />
                <?php else: ?>
                  <div class="media-placeholder product-image">[ IMAGEN DEL PRODUCTO ]</div>
                <?php endif; ?>
                <h3><?= e($p['nombre']) ?></h3>
                <p class="product-price">
                  <?= precio($p['precio']) ?>
                  <?php if ($p['precio_antes']): ?>
                    <small style="opacity:.6;text-decoration:line-through"><?= precio($p['precio_antes']) ?></small>
                  <?php endif; ?>
                </p>
                <a class="btn btn-card" href="producto.php?id=<?= (int) $p['id'] ?>">Ver Detalles</a>
              </article>
            <?php endforeach; ?>

            <?php if (!$destacados): ?>
              <p class="empty-state">Aún no hay productos destacados.</p>
            <?php endif; ?>
          </div>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php';
