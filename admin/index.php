<?php
require_once __DIR__ . '/includes/auth.php';

$admin_titulo    = 'Dashboard';
$admin_subtitulo = 'Resumen general de la tienda Players of Dominion.';
$titulo_pagina   = 'Dashboard | Panel Admin — Players of Dominion';

$kpi = db()->query('SELECT
        COUNT(*)                               AS total_productos,
        COALESCE(SUM(stock), 0)                AS total_unidades,
        COALESCE(SUM(precio * stock), 0)       AS valor_inventario,
        SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) AS stock_bajo
    FROM productos')->fetch();

$total_usuarios = (int) db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

$ultimos = db()->query('SELECT id, nombre, precio, stock, imagen_url
        FROM productos ORDER BY creado_en DESC LIMIT 5')->fetchAll();

require __DIR__ . '/includes/admin_header.php';
?>
        <div class="kpi-grid">
          <div class="kpi-card">
            <span>Productos activos</span>
            <strong><?= (int) $kpi['total_productos'] ?></strong>
          </div>
          <div class="kpi-card">
            <span>Valor del inventario</span>
            <strong><?= precio($kpi['valor_inventario']) ?></strong>
          </div>
          <div class="kpi-card <?= (int) $kpi['stock_bajo'] > 0 ? 'is-warning' : '' ?>">
            <span>Con stock bajo (≤5)</span>
            <strong><?= (int) $kpi['stock_bajo'] ?></strong>
          </div>
          <div class="kpi-card">
            <span>Usuarios registrados</span>
            <strong><?= $total_usuarios ?></strong>
          </div>
        </div>

        <div class="admin-quick-grid">
          <a class="admin-quick-card" href="productos.php">
            <h3>📦 Gestionar Productos</h3>
            <p>Crea, edita, elimina y carga fotos de los productos del catálogo.</p>
            <span>Ir al CRUD de productos →</span>
          </a>
          <a class="admin-quick-card" href="ventas.php">
            <h3>🧾 Tabla de Ventas</h3>
            <p>Historial de pedidos con detalle, filtros, estados y exportación a CSV.</p>
            <span>Ir a ventas →</span>
          </a>
          <a class="admin-quick-card" href="estadisticas.php">
            <h3>📊 Estadísticas de Ventas</h3>
            <p>Ingresos por día, productos más vendidos y rendimiento por categoría.</p>
            <span>Ir a estadísticas →</span>
          </a>
          <a class="admin-quick-card" href="reportes.php">
            <h3>📈 Ver Reportes</h3>
            <p>Inventario por categoría, alertas de stock bajo y exportación a CSV.</p>
            <span>Ir a reportes →</span>
          </a>
        </div>

        <div class="report-table-wrap" style="margin-top:36px">
          <h3>Últimos productos agregados</h3>
          <table class="admin-table">
            <thead>
              <tr><th></th><th>Producto</th><th class="num">Precio</th><th class="num">Stock</th></tr>
            </thead>
            <tbody>
              <?php foreach ($ultimos as $p): ?>
                <tr class="<?= (int) $p['stock'] <= 5 ? 'stock-low' : '' ?>">
                  <td>
                    <?php if ($p['imagen_url']): ?>
                      <img class="admin-thumb" src="<?= e(imagen_admin($p['imagen_url'])) ?>" alt="" />
                    <?php endif; ?>
                  </td>
                  <td><a href="productos.php?editar=<?= (int) $p['id'] ?>"><?= e($p['nombre']) ?></a></td>
                  <td class="num"><?= precio($p['precio']) ?></td>
                  <td class="num"><?= (int) $p['stock'] ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$ultimos): ?>
                <tr><td colspan="4">Aún no hay productos registrados.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
<?php require __DIR__ . '/includes/admin_footer.php';
