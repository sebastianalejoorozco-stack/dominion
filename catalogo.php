<?php
require_once __DIR__ . '/db.php';

$titulo_pagina = 'Tienda | Catálogo de Hardware Gamer — Players of Dominion';
$descripcion_pagina = 'Explora todo el catálogo de Players of Dominion: PC gamer, monitores, tarjetas gráficas y periféricos con filtros de precio, categoría y marca.';

$q         = trim((string) ($_GET['q'] ?? ''));
$categoria = (int) ($_GET['categoria'] ?? 0);
$marca     = trim((string) ($_GET['marca'] ?? ''));
$maximo    = (float) ($_GET['maximo'] ?? 15000000);
$oferta    = isset($_GET['oferta']);

$sql = 'SELECT p.*, c.nombre AS categoria_nombre
        FROM productos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        WHERE p.precio <= :maximo';
$params = [':maximo' => $maximo];

if ($q !== '') {
    $sql .= ' AND (p.nombre LIKE :q OR p.descripcion LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($categoria > 0) {
    $sql .= ' AND p.categoria_id = :categoria';
    $params[':categoria'] = $categoria;
}
if ($marca !== '') {
    $sql .= ' AND p.marca = :marca';
    $params[':marca'] = $marca;
}
if ($oferta) {
    $sql .= ' AND p.precio_antes IS NOT NULL';
}
$sql .= ' ORDER BY p.creado_en DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

$categorias = db()->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();
$marcas     = db()->query('SELECT DISTINCT marca FROM productos ORDER BY marca')->fetchAll();

require __DIR__ . '/includes/header.php';
?>
      <section class="section">
        <div class="shell">
          <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="index.php">Inicio</a> <span>/</span> <strong>Tienda</strong>
          </nav>

          <div class="section-header">
            <h2><?= $oferta ? 'Ofertas y Liquidación' : 'Catálogo Completo de Hardware' ?></h2>
            <p><?= count($productos) ?> productos con stock verificado y garantía del fabricante.</p>
          </div>

          <div class="catalog-layout">
            <aside class="filter-panel" aria-label="Filtros de productos">
              <form method="get" action="catalogo.php">
                <div class="filter-group">
                  <h4>Búsqueda</h4>
                  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Ej: monitor 240Hz" />
                </div>

                <div class="filter-group" id="categorias">
                  <h4>Categoría</h4>
                  <label class="filter-check">
                    <input type="radio" name="categoria" value="0" <?= $categoria === 0 ? 'checked' : '' ?> /> Todas
                  </label>
                  <?php foreach ($categorias as $c): ?>
                    <label class="filter-check">
                      <input type="radio" name="categoria" value="<?= (int) $c['id'] ?>"
                             <?= $categoria === (int) $c['id'] ? 'checked' : '' ?> />
                      <?= e($c['nombre']) ?>
                    </label>
                  <?php endforeach; ?>
                </div>

                <div class="filter-group" id="marcas">
                  <h4>Marca</h4>
                  <label class="filter-check">
                    <input type="radio" name="marca" value="" <?= $marca === '' ? 'checked' : '' ?> /> Todas
                  </label>
                  <?php foreach ($marcas as $m): ?>
                    <label class="filter-check">
                      <input type="radio" name="marca" value="<?= e($m['marca']) ?>"
                             <?= $marca === $m['marca'] ? 'checked' : '' ?> />
                      <?= e($m['marca']) ?>
                    </label>
                  <?php endforeach; ?>
                </div>

                <div class="filter-group">
                  <h4>Precio máximo</h4>
                  <input type="range" name="maximo" min="100000" max="15000000" step="100000" value="<?= (int) $maximo ?>" />
                  <p class="filter-value">Hasta <?= precio($maximo) ?></p>
                </div>

                <?php if ($oferta): ?><input type="hidden" name="oferta" value="1" /><?php endif; ?>

                <button class="btn btn-primary btn-block" type="submit">Aplicar Filtros</button>
                <a class="btn btn-outline btn-block" href="catalogo.php">Limpiar Filtros</a>
              </form>
            </aside>

            <div>
              <?php if ($productos): ?>
                <div class="product-grid grid-3">
                  <?php foreach ($productos as $p): ?>
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
                      <p class="product-price"><?= precio($p['precio']) ?></p>
                      <a class="btn btn-card" href="producto.php?id=<?= (int) $p['id'] ?>">Ver Detalles</a>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="empty-state">Ningún producto coincide con los filtros seleccionados.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php';
