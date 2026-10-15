<?php
require_once __DIR__ . '/db.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT p.*, c.nombre AS categoria_nombre
                       FROM productos p
                       LEFT JOIN categorias c ON c.id = p.categoria_id
                       WHERE p.id = :id');
$stmt->execute([':id' => $id]);
$producto = $stmt->fetch();

if (!$producto) {
    http_response_code(404);
    $titulo_pagina = 'Producto no encontrado | Players of Dominion';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="shell"><p class="empty-state">El producto solicitado no existe.</p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Agregar al carrito (sesión)
$mensaje = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));
    $_SESSION['carrito'][$id] = ($_SESSION['carrito'][$id] ?? 0) + $cantidad;
    $mensaje = 'Producto agregado al carrito.';
}

$relacionados = db()->prepare('SELECT id, nombre, precio, imagen_url FROM productos
                               WHERE categoria_id <=> :cat AND id <> :id LIMIT 3');
$relacionados->execute([':cat' => $producto['categoria_id'], ':id' => $id]);
$relacionados = $relacionados->fetchAll();

$titulo_pagina = e($producto['nombre']) . ' | Players of Dominion';
$descripcion_pagina = mb_substr((string) $producto['descripcion'], 0, 155);

require __DIR__ . '/includes/header.php';
?>
      <section class="section">
        <div class="shell">
          <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="index.php">Inicio</a> <span>/</span>
            <a href="catalogo.php">Tienda</a> <span>/</span>
            <strong><?= e($producto['nombre']) ?></strong>
          </nav>

          <?php if ($mensaje): ?><div class="alert"><?= e($mensaje) ?></div><?php endif; ?>

          <div class="product-detail">
            <?php if ($producto['imagen_url']): ?>
              <img class="product-gallery" src="<?= e($producto['imagen_url']) ?>" alt="<?= e($producto['nombre']) ?>" />
            <?php else: ?>
              <div class="media-placeholder product-gallery">[ IMAGEN DEL PRODUCTO ]</div>
            <?php endif; ?>

            <div class="product-info">
              <?php if ($producto['etiqueta']): ?>
                <span class="product-tag"><?= e($producto['etiqueta']) ?></span>
              <?php endif; ?>
              <h1><?= e($producto['nombre']) ?></h1>
              <p class="eyebrow">
                <?= e($producto['marca']) ?> · <?= e($producto['categoria_nombre'] ?? 'Sin categoría') ?>
              </p>
              <p><?= e($producto['descripcion']) ?></p>

              <p class="product-price">
                <?= precio($producto['precio']) ?>
                <?php if ($producto['precio_antes']): ?>
                  <small style="opacity:.6;text-decoration:line-through"><?= precio($producto['precio_antes']) ?></small>
                <?php endif; ?>
              </p>

              <p class="filter-value">
                <?= (int) $producto['stock'] > 0
                      ? 'Disponibles: ' . (int) $producto['stock'] . ' unidades'
                      : 'Agotado temporalmente' ?>
              </p>

              <form method="post" class="hero-actions">
                <div class="qty-control">
                  <button type="button" onclick="const c=this.nextElementSibling;c.value=Math.max(1,+c.value-1)" aria-label="Disminuir">−</button>
                  <input type="number" name="cantidad" value="1" min="1" max="<?= max(1, (int) $producto['stock']) ?>" style="width:56px;text-align:center;background:transparent;border:0;color:#fff" />
                  <button type="button" onclick="const c=this.previousElementSibling;c.value=+c.value+1" aria-label="Aumentar">+</button>
                </div>
                <button class="btn btn-primary" type="submit" name="agregar" value="1"
                        <?= (int) $producto['stock'] === 0 ? 'disabled' : '' ?>>
                  Agregar al Carrito 🛒
                </button>
                <a class="btn btn-outline" href="carrito.php">Ir al Carrito</a>
              </form>
            </div>
          </div>

          <?php if ($relacionados): ?>
            <div class="section-header" style="margin-top:3rem">
              <h2>Productos Relacionados</h2>
              <p>Otras opciones dentro de la misma categoría.</p>
            </div>
            <div class="product-grid grid-3">
              <?php foreach ($relacionados as $r): ?>
                <article class="product-card">
                  <?php if ($r['imagen_url']): ?>
                    <img class="product-image" src="<?= e($r['imagen_url']) ?>" alt="<?= e($r['nombre']) ?>" loading="lazy" />
                  <?php else: ?>
                    <div class="media-placeholder product-image">[ IMAGEN ]</div>
                  <?php endif; ?>
                  <h3><?= e($r['nombre']) ?></h3>
                  <p class="product-price"><?= precio($r['precio']) ?></p>
                  <a class="btn btn-card" href="producto.php?id=<?= (int) $r['id'] ?>">Ver Detalles</a>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php';
