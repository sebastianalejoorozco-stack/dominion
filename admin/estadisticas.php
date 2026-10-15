<?php
require_once __DIR__ . '/includes/auth.php';

$admin_titulo    = 'Estadísticas de ventas';
$admin_subtitulo = 'Ingresos, productos más vendidos y rendimiento por categoría.';
$titulo_pagina   = 'Estadísticas | Panel Admin — Players of Dominion';

$hay_tablas = tablas_ventas_existen();
$dias = (int) ($_GET['dias'] ?? 30);
if (!in_array($dias, [7, 30, 90], true)) { $dias = 30; }

if ($hay_tablas) {
    $pdo = db();
    $desde      = date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'));
    $desde_prev = date('Y-m-d', strtotime('-' . (2 * $dias - 1) . ' days'));
    $q = fn (string $sql, array $p = []) => (function () use ($pdo, $sql, $p) { $s = $pdo->prepare($sql); $s->execute($p); return $s; })();

    // KPIs del periodo y del periodo anterior (para comparar). Las canceladas no cuentan.
    $kpi_sql = "SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS ingresos,
                       COALESCE(SUM((SELECT SUM(cantidad) FROM venta_items WHERE venta_id = v.id)),0) AS unidades
                FROM ventas v WHERE estado <> 'cancelada' AND creado_en >= :d1 AND creado_en < :d2";
    $act  = $q($kpi_sql, [':d1' => $desde . ' 00:00:00', ':d2' => date('Y-m-d', strtotime('+1 day')) . ' 00:00:00'])->fetch();
    $prev = $q($kpi_sql, [':d1' => $desde_prev . ' 00:00:00', ':d2' => $desde . ' 00:00:00'])->fetch();
    $ticket = $act['n'] ? $act['ingresos'] / $act['n'] : 0;
    $delta  = (float) $prev['ingresos'] > 0 ? ($act['ingresos'] - $prev['ingresos']) / $prev['ingresos'] * 100 : null;

    $canceladas = (int) $q("SELECT COUNT(*) FROM ventas WHERE estado = 'cancelada' AND creado_en >= :d",
                           [':d' => $desde . ' 00:00:00'])->fetchColumn();

    // Ingresos por día (rellena días sin ventas con 0)
    $por_dia = [];
    for ($i = $dias - 1; $i >= 0; $i--) { $por_dia[date('Y-m-d', strtotime("-$i days"))] = ['total' => 0.0, 'n' => 0]; }
    foreach ($q("SELECT DATE(creado_en) AS d, SUM(total) AS t, COUNT(*) AS n FROM ventas
                 WHERE estado <> 'cancelada' AND creado_en >= :d GROUP BY DATE(creado_en)", [':d' => $desde . ' 00:00:00'])->fetchAll() as $r) {
        if (isset($por_dia[$r['d']])) { $por_dia[$r['d']] = ['total' => (float) $r['t'], 'n' => (int) $r['n']]; }
    }

    $top = $q("SELECT i.nombre, SUM(i.cantidad) AS unidades, SUM(i.subtotal) AS ingresos
               FROM venta_items i JOIN ventas v ON v.id = i.venta_id
               WHERE v.estado <> 'cancelada' AND v.creado_en >= :d
               GROUP BY i.nombre ORDER BY unidades DESC, ingresos DESC LIMIT 5", [':d' => $desde . ' 00:00:00'])->fetchAll();

    $por_cat = $q("SELECT COALESCE(i.categoria, 'Sin categoría') AS categoria, SUM(i.cantidad) AS unidades, SUM(i.subtotal) AS ingresos
                   FROM venta_items i JOIN ventas v ON v.id = i.venta_id
                   WHERE v.estado <> 'cancelada' AND v.creado_en >= :d
                   GROUP BY categoria ORDER BY ingresos DESC", [':d' => $desde . ' 00:00:00'])->fetchAll();

    $por_estado = $q("SELECT estado, COUNT(*) AS n FROM ventas WHERE creado_en >= :d GROUP BY estado", [':d' => $desde . ' 00:00:00'])->fetchAll();

    $mejores_clientes = $q("SELECT cliente_nombre, COUNT(*) AS compras, SUM(total) AS total FROM ventas
                            WHERE estado <> 'cancelada' AND creado_en >= :d
                            GROUP BY cliente_correo, cliente_nombre ORDER BY total DESC LIMIT 5", [':d' => $desde . ' 00:00:00'])->fetchAll();

    // ---- Geometría del gráfico de barras (SVG) ----
    $W = 760; $H = 220; $pl = 8; $pb = 22; $pt = 10;
    $max = max(1.0, ...array_values(array_map(fn ($d) => $d["total"], $por_dia)));
    $n = count($por_dia); $bw = ($W - 2 * $pl) / $n;
}

$estado_nombre = ['pagada' => 'Pagada', 'enviada' => 'Enviada', 'entregada' => 'Entregada', 'cancelada' => 'Cancelada'];
$formato_corto = function (float $v): string {
    if ($v >= 1e6) { return '$' . rtrim(rtrim(number_format($v / 1e6, 1, ',', '.'), '0'), ',') . ' M'; }
    if ($v >= 1e3) { return '$' . number_format($v / 1e3, 0, ',', '.') . ' mil'; }
    return '$' . number_format($v, 0, ',', '.');
};

require __DIR__ . '/includes/admin_header.php';
?>
        <?php if (!$hay_tablas): ?>
          <div class="alert" style="border-color:#ffb84c;background:rgba(255,184,76,.1)">
            Aún no existen las tablas de ventas. Ejecuta <code>sql/ventas.sql</code> en tu MySQL y recarga esta página.
          </div>
        <?php else: ?>
        <div class="period-tabs" role="tablist" aria-label="Periodo">
          <?php foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $d => $label): ?>
            <a class="<?= $dias === $d ? 'is-active' : '' ?>" href="estadisticas.php?dias=<?= $d ?>"><?= $label ?></a>
          <?php endforeach; ?>
        </div>

        <div class="kpi-grid">
          <div class="kpi-card">
            <span>Ingresos</span><strong><?= precio($act['ingresos']) ?></strong>
            <?php if ($delta !== null): ?>
              <small class="delta <?= $delta >= 0 ? 'up' : 'down' ?>"><?= $delta >= 0 ? '▲' : '▼' ?> <?= number_format(abs($delta), 1, ',', '.') ?>% vs. periodo anterior</small>
            <?php else: ?><small class="muted">Sin datos del periodo anterior</small><?php endif; ?>
          </div>
          <div class="kpi-card"><span>Ventas</span><strong><?= (int) $act['n'] ?></strong>
            <small class="muted"><?= $canceladas ?> cancelada(s)</small></div>
          <div class="kpi-card"><span>Unidades vendidas</span><strong><?= (int) $act['unidades'] ?></strong></div>
          <div class="kpi-card"><span>Ticket promedio</span><strong><?= precio($ticket) ?></strong></div>
        </div>

        <div class="report-table-wrap">
          <h3>Ingresos por día</h3>
          <p class="hint">Últimos <?= $dias ?> días, sin contar ventas canceladas.</p>
          <div class="chart-card">
            <?php if ((int) $act['n'] === 0): ?>
              <p class="muted">No hay ventas en este periodo.</p>
            <?php else: ?>
            <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Ingresos por día, máximo <?= e($formato_corto($max)) ?>" preserveAspectRatio="none">
              <?php foreach ([0.25, 0.5, 0.75, 1] as $g): $y = $pt + ($H - $pt - $pb) * (1 - $g); ?>
                <line x1="<?= $pl ?>" x2="<?= $W - $pl ?>" y1="<?= $y ?>" y2="<?= $y ?>" class="grid" />
              <?php endforeach; ?>
              <?php $i = 0; foreach ($por_dia as $fecha => $d):
                $h = ($H - $pt - $pb) * $d['total'] / $max;
                $x = $pl + $i * $bw + $bw * 0.15;
                $paso = $dias <= 7 ? 1 : ($dias <= 30 ? 5 : 15); ?>
                <rect x="<?= round($x, 2) ?>" y="<?= round($H - $pb - $h, 2) ?>" width="<?= round($bw * 0.7, 2) ?>" height="<?= round($h, 2) ?>" rx="2" class="bar">
                  <title><?= e(date('d/m/Y', strtotime($fecha))) ?> — <?= e(precio($d['total'])) ?> (<?= $d['n'] ?> venta<?= $d['n'] === 1 ? '' : 's' ?>)</title>
                </rect>
                <?php if ($i % $paso === 0): ?>
                  <text x="<?= round($x + $bw * 0.35, 2) ?>" y="<?= $H - 6 ?>" text-anchor="middle" class="tick"><?= e(date('d/m', strtotime($fecha))) ?></text>
                <?php endif; $i++; endforeach; ?>
            </svg>
            <div class="chart-max">Máximo diario: <?= e($formato_corto($max)) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="stats-grid">
          <div class="report-table-wrap">
            <h3>Productos más vendidos</h3>
            <p class="hint">Top 5 por unidades.</p>
            <?php $mx = max(1, ...array_map(fn ($t) => (int) $t['unidades'], $top ?: [['unidades' => 1]])); ?>
            <ol class="hbars">
              <?php foreach ($top as $t): ?>
                <li>
                  <div class="hbar-head"><span><?= e($t['nombre']) ?></span><strong><?= (int) $t['unidades'] ?> uds · <?= e($formato_corto((float) $t['ingresos'])) ?></strong></div>
                  <div class="hbar"><i style="width:<?= round($t['unidades'] / $mx * 100) ?>%"></i></div>
                </li>
              <?php endforeach; ?>
              <?php if (!$top): ?><li class="muted">Sin ventas en el periodo.</li><?php endif; ?>
            </ol>
          </div>

          <div class="report-table-wrap">
            <h3>Ingresos por categoría</h3>
            <p class="hint">Participación sobre el total del periodo.</p>
            <?php $tc = max(1.0, array_sum(array_map(fn ($c) => (float) $c['ingresos'], $por_cat))); ?>
            <ol class="hbars">
              <?php foreach ($por_cat as $c): $pct = $c['ingresos'] / $tc * 100; ?>
                <li>
                  <div class="hbar-head"><span><?= e($c['categoria']) ?></span><strong><?= number_format($pct, 1, ',', '.') ?>% · <?= e($formato_corto((float) $c['ingresos'])) ?></strong></div>
                  <div class="hbar alt"><i style="width:<?= round($pct) ?>%"></i></div>
                </li>
              <?php endforeach; ?>
              <?php if (!$por_cat): ?><li class="muted">Sin ventas en el periodo.</li><?php endif; ?>
            </ol>
          </div>

          <div class="report-table-wrap">
            <h3>Ventas por estado</h3>
            <table class="admin-table">
              <thead><tr><th>Estado</th><th class="num">Ventas</th></tr></thead>
              <tbody>
                <?php foreach ($por_estado as $s): ?>
                  <tr><td><span class="estado-pill estado-<?= e($s['estado']) ?>"><?= e($estado_nombre[$s['estado']] ?? $s['estado']) ?></span></td><td class="num"><?= (int) $s['n'] ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$por_estado): ?><tr><td colspan="2">Sin ventas en el periodo.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="report-table-wrap">
            <h3>Mejores clientes</h3>
            <table class="admin-table">
              <thead><tr><th>Cliente</th><th class="num">Compras</th><th class="num">Total</th></tr></thead>
              <tbody>
                <?php foreach ($mejores_clientes as $c): ?>
                  <tr><td><?= e($c['cliente_nombre']) ?></td><td class="num"><?= (int) $c['compras'] ?></td><td class="num"><?= precio($c['total']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$mejores_clientes): ?><tr><td colspan="3">Sin ventas en el periodo.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>
<?php require __DIR__ . '/includes/admin_footer.php';
