<?php
require_once __DIR__ . '/includes/auth.php';

// ---------- EXPORTAR CSV (antes de imprimir cualquier HTML) ----------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filas = db()->query('SELECT p.id, p.nombre, p.marca, c.nombre AS categoria, p.precio, p.stock,
                                  (p.precio * p.stock) AS valor_inventario, p.destacado
                           FROM productos p
                           LEFT JOIN categorias c ON c.id = p.categoria_id
                           ORDER BY p.id ASC')->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte_productos_dominion.csv"');

    $salida = fopen('php://output', 'w');
    fputs($salida, "\xEF\xBB\xBF"); // BOM para que Excel detecte UTF-8
    fputcsv($salida, ['ID', 'Nombre', 'Marca', 'Categoría', 'Precio', 'Stock', 'Valor en inventario', 'Destacado']);
    foreach ($filas as $f) {
        fputcsv($salida, [
            $f['id'], $f['nombre'], $f['marca'], $f['categoria'] ?? 'Sin categoría',
            $f['precio'], $f['stock'], $f['valor_inventario'], $f['destacado'] ? 'Sí' : 'No',
        ]);
    }
    fclose($salida);
    exit;
}

$admin_titulo    = 'Reportes';
$admin_subtitulo = 'Inventario, valor en bodega y alertas de stock bajo.';
$titulo_pagina   = 'Reportes | Panel Admin — Players of Dominion';

// ---------- KPIs ----------
$kpi = db()->query('SELECT
        COUNT(*)                               AS total_productos,
        COALESCE(SUM(stock), 0)                AS total_unidades,
        COALESCE(SUM(precio * stock), 0)       AS valor_inventario,
        SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) AS stock_bajo,
        COALESCE(SUM(destacado), 0)            AS destacados
    FROM productos')->fetch();

// ---------- Inventario por categoría ----------
$por_categoria = db()->query('SELECT COALESCE(c.nombre, "Sin categoría") AS categoria,
            COUNT(p.id) AS num_productos,
            COALESCE(SUM(p.stock), 0) AS unidades,
            COALESCE(SUM(p.precio * p.stock), 0) AS valor
        FROM productos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        GROUP BY c.id, c.nombre
        ORDER BY valor DESC')->fetchAll();

// ---------- Alerta de stock bajo ----------
$stock_bajo = db()->query('SELECT id, nombre, marca, stock
        FROM productos
        WHERE stock <= 5
        ORDER BY stock ASC')->fetchAll();

// ---------- Top 5 por valor en inventario ----------
$top_valor = db()->query('SELECT id, nombre, marca, precio, stock, (precio * stock) AS valor
        FROM productos
        ORDER BY valor DESC
        LIMIT 5')->fetchAll();

require __DIR__ . '/includes/admin_header.php';
?>
        <div class="kpi-grid">
          <div class="kpi-card">
            <span>Productos activos</span>
            <strong><?= (int) $kpi['total_productos'] ?></strong>
          </div>
          <div class="kpi-card">
            <span>Unidades en stock</span>
            <strong><?= (int) $kpi['total_unidades'] ?></strong>
          </div>
          <div class="kpi-card">
            <span>Valor del inventario</span>
            <strong><?= precio($kpi['valor_inventario']) ?></strong>
          </div>
          <div class="kpi-card <?= (int) $kpi['stock_bajo'] > 0 ? 'is-warning' : '' ?>">
            <span>Con stock bajo (≤5)</span>
            <strong><?= (int) $kpi['stock_bajo'] ?></strong>
          </div>
        </div>

        <div class="hero-actions" style="margin-bottom:30px">
          <a class="btn btn-primary" href="reportes.php?export=csv">⬇ Descargar CSV completo</a>
        </div>

        <div class="report-table-wrap">
          <h3>Inventario por categoría</h3>
          <p class="hint">Unidades disponibles y valor total (precio × stock) agrupados por categoría.</p>
          <table class="admin-table">
            <thead>
              <tr><th>Categoría</th><th class="num">Productos</th><th class="num">Unidades</th><th class="num">Valor</th></tr>
            </thead>
            <tbody>
              <?php foreach ($por_categoria as $c): ?>
                <tr>
                  <td><?= e($c['categoria']) ?></td>
                  <td class="num"><?= (int) $c['num_productos'] ?></td>
                  <td class="num"><?= (int) $c['unidades'] ?></td>
                  <td class="num"><?= precio($c['valor']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$por_categoria): ?>
                <tr><td colspan="4">No hay datos todavía.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="report-table-wrap">
          <h3>Alerta de stock bajo</h3>
          <p class="hint">Productos con 5 unidades o menos — candidatos a reabastecer pronto.</p>
          <table class="admin-table">
            <thead>
              <tr><th>Producto</th><th>Marca</th><th class="num">Stock</th></tr>
            </thead>
            <tbody>
              <?php foreach ($stock_bajo as $s): ?>
                <tr class="stock-low">
                  <td><a href="productos.php?editar=<?= (int) $s['id'] ?>"><?= e($s['nombre']) ?></a></td>
                  <td><?= e($s['marca']) ?></td>
                  <td class="num stock-value"><?= (int) $s['stock'] ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$stock_bajo): ?>
                <tr><td colspan="3">Ningún producto con stock bajo por ahora. 👍</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="report-table-wrap">
          <h3>Top 5 por valor en inventario</h3>
          <p class="hint">Productos que más capital representan (precio × stock disponible).</p>
          <table class="admin-table">
            <thead>
              <tr><th>Producto</th><th>Marca</th><th class="num">Precio</th><th class="num">Stock</th><th class="num">Valor</th></tr>
            </thead>
            <tbody>
              <?php foreach ($top_valor as $t): ?>
                <tr>
                  <td><a href="productos.php?editar=<?= (int) $t['id'] ?>"><?= e($t['nombre']) ?></a></td>
                  <td><?= e($t['marca']) ?></td>
                  <td class="num"><?= precio($t['precio']) ?></td>
                  <td class="num"><?= (int) $t['stock'] ?></td>
                  <td class="num"><?= precio($t['valor']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$top_valor): ?>
                <tr><td colspan="5">No hay datos todavía.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
<?php require __DIR__ . '/includes/admin_footer.php';
