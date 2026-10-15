<?php
require_once __DIR__ . '/includes/auth.php';

const ESTADOS_VENTA = ['pagada' => 'Pagada', 'enviada' => 'Enviada', 'entregada' => 'Entregada', 'cancelada' => 'Cancelada'];

$hay_tablas = tablas_ventas_existen();

// ---------- Filtros (GET) ----------
$f_estado = $_GET['estado'] ?? '';
$f_desde  = $_GET['desde'] ?? '';
$f_hasta  = $_GET['hasta'] ?? '';
$f_q      = trim((string) ($_GET['q'] ?? ''));
if (!isset(ESTADOS_VENTA[$f_estado])) { $f_estado = ''; }
$fecha_ok = fn (string $f) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $f);
if (!$fecha_ok($f_desde)) { $f_desde = ''; }
if (!$fecha_ok($f_hasta)) { $f_hasta = ''; }

$where = []; $params = [];
if ($f_estado !== '') { $where[] = 'v.estado = :estado'; $params[':estado'] = $f_estado; }
if ($f_desde  !== '') { $where[] = 'v.creado_en >= :desde'; $params[':desde'] = $f_desde . ' 00:00:00'; }
if ($f_hasta  !== '') { $where[] = 'v.creado_en <= :hasta'; $params[':hasta'] = $f_hasta . ' 23:59:59'; }
if ($f_q !== '') {
    $where[] = '(v.cliente_nombre LIKE :q1 OR v.cliente_correo LIKE :q2 OR v.id = :qid)';
    $params[':q1'] = $params[':q2'] = '%' . $f_q . '%';
    $params[':qid'] = ctype_digit($f_q) ? (int) $f_q : 0;
}
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql_ventas = "SELECT v.*, COALESCE(SUM(i.cantidad), 0) AS unidades, COUNT(i.id) AS lineas
               FROM ventas v LEFT JOIN venta_items i ON i.venta_id = v.id
               $sql_where GROUP BY v.id ORDER BY v.creado_en DESC, v.id DESC";

// ---------- EXPORTAR CSV (antes de imprimir HTML) ----------
if ($hay_tablas && ($_GET['export'] ?? '') === 'csv') {
    $stmt = db()->prepare($sql_ventas);
    $stmt->execute($params);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="ventas_dominion.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Venta', 'Fecha', 'Cliente', 'Correo', 'Unidades', 'Subtotal', 'Envío', 'IVA', 'Total', 'Estado']);
    foreach ($stmt->fetchAll() as $v) {
        fputcsv($out, [$v['id'], $v['creado_en'], $v['cliente_nombre'], $v['cliente_correo'], $v['unidades'],
                       $v['subtotal'], $v['envio'], $v['iva'], $v['total'], ESTADOS_VENTA[$v['estado']] ?? $v['estado']]);
    }
    fclose($out);
    exit;
}

$admin_titulo    = 'Ventas';
$admin_subtitulo = 'Historial de pedidos, detalle por producto y estado de cada venta.';
$titulo_pagina   = 'Ventas | Panel Admin — Players of Dominion';

$mensaje = null; $error = null;

// ---------- Cambiar estado de una venta ----------
if ($hay_tablas && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'estado') {
    try {
        if (!csrf_valido()) { throw new RuntimeException('Sesión expirada, recarga la página.'); }
        $id = (int) $_POST['id'];
        $nuevo = $_POST['estado'] ?? '';
        if (!isset(ESTADOS_VENTA[$nuevo])) { throw new RuntimeException('Estado no válido.'); }

        $pdo = db();
        $pdo->beginTransaction();
        $actual = $pdo->prepare('SELECT estado FROM ventas WHERE id = ? FOR UPDATE');
        $actual->execute([$id]);
        $antes = $actual->fetchColumn();
        if ($antes === false) { throw new RuntimeException('La venta no existe.'); }

        // Cancelar devuelve el stock; reactivar una cancelada lo vuelve a descontar.
        if ($antes !== 'cancelada' && $nuevo === 'cancelada') {
            $pdo->prepare('UPDATE productos p JOIN venta_items i ON i.producto_id = p.id
                           SET p.stock = p.stock + i.cantidad WHERE i.venta_id = ?')->execute([$id]);
        } elseif ($antes === 'cancelada' && $nuevo !== 'cancelada') {
            $pdo->prepare('UPDATE productos p JOIN venta_items i ON i.producto_id = p.id
                           SET p.stock = GREATEST(p.stock - i.cantidad, 0) WHERE i.venta_id = ?')->execute([$id]);
        }
        $pdo->prepare('UPDATE ventas SET estado = ? WHERE id = ?')->execute([$nuevo, $id]);
        $pdo->commit();
        $mensaje = "Venta #$id marcada como " . strtolower(ESTADOS_VENTA[$nuevo]) . '.';
    } catch (Throwable $ex) {
        if (db()->inTransaction()) { db()->rollBack(); }
        $error = $ex->getMessage();
    }
}

$ventas = []; $items = []; $kpi = ['n' => 0, 'ingresos' => 0, 'unidades' => 0, 'ticket' => 0];
if ($hay_tablas) {
    $stmt = db()->prepare($sql_ventas);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll();

    if ($ventas) {
        $ids = array_map(fn ($v) => (int) $v['id'], $ventas);
        $in  = implode(',', $ids); // ids enteros generados por nosotros
        foreach (db()->query("SELECT * FROM venta_items WHERE venta_id IN ($in) ORDER BY id")->fetchAll() as $it) {
            $items[(int) $it['venta_id']][] = $it;
        }
    }
    $validas = array_filter($ventas, fn ($v) => $v['estado'] !== 'cancelada');
    $kpi['n']        = count($validas);
    $kpi['ingresos'] = array_sum(array_map(fn ($v) => (float) $v['total'], $validas));
    $kpi['unidades'] = array_sum(array_map(fn ($v) => (int) $v['unidades'], $validas));
    $kpi['ticket']   = $kpi['n'] ? $kpi['ingresos'] / $kpi['n'] : 0;
}

$qs_export = http_build_query(array_filter(['estado' => $f_estado, 'desde' => $f_desde, 'hasta' => $f_hasta, 'q' => $f_q, 'export' => 'csv']));

require __DIR__ . '/includes/admin_header.php';
?>
        <?php if (!$hay_tablas): ?>
          <div class="alert" style="border-color:#ffb84c;background:rgba(255,184,76,.1)">
            Aún no existen las tablas de ventas. Ejecuta <code>sql/ventas.sql</code> en tu MySQL y recarga esta página.
          </div>
        <?php else: ?>
        <?php if ($mensaje): ?><div class="alert"><?= e($mensaje) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert" style="border-color:#ff4d6d;background:rgba(255,77,109,.12)"><?= e($error) ?></div><?php endif; ?>

        <div class="kpi-grid">
          <div class="kpi-card"><span>Ventas (sin canceladas)</span><strong><?= (int) $kpi['n'] ?></strong></div>
          <div class="kpi-card"><span>Ingresos</span><strong><?= precio($kpi['ingresos']) ?></strong></div>
          <div class="kpi-card"><span>Unidades vendidas</span><strong><?= (int) $kpi['unidades'] ?></strong></div>
          <div class="kpi-card"><span>Ticket promedio</span><strong><?= precio($kpi['ticket']) ?></strong></div>
        </div>

        <form class="filter-bar" method="get">
          <label>Buscar
            <input type="text" name="q" value="<?= e($f_q) ?>" placeholder="Cliente, correo o # venta" />
          </label>
          <label>Estado
            <select name="estado">
              <option value="">Todos</option>
              <?php foreach (ESTADOS_VENTA as $k => $n): ?>
                <option value="<?= e($k) ?>" <?= $f_estado === $k ? 'selected' : '' ?>><?= e($n) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Desde <input type="date" name="desde" value="<?= e($f_desde) ?>" /></label>
          <label>Hasta <input type="date" name="hasta" value="<?= e($f_hasta) ?>" /></label>
          <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-outline" href="ventas.php">Limpiar</a>
            <a class="btn btn-outline" href="ventas.php?<?= e($qs_export) ?>">⬇ CSV</a>
          </div>
        </form>

        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th><th>Fecha</th><th>Cliente</th><th class="num">Unid.</th>
              <th class="num">Total</th><th>Estado</th><th>Detalle</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ventas as $v): ?>
              <tr>
                <td><strong>#<?= (int) $v['id'] ?></strong></td>
                <td><?= e(date('d/m/Y H:i', strtotime($v['creado_en']))) ?></td>
                <td><?= e($v['cliente_nombre']) ?><br /><small class="muted"><?= e($v['cliente_correo']) ?></small></td>
                <td class="num"><?= (int) $v['unidades'] ?></td>
                <td class="num"><?= precio($v['total']) ?></td>
                <td>
                  <form method="post" class="estado-form">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="estado" />
                    <input type="hidden" name="id" value="<?= (int) $v['id'] ?>" />
                    <select name="estado" class="estado-select estado-<?= e($v['estado']) ?>" onchange="
                      if (this.value === 'cancelada' && !confirm('¿Cancelar la venta #<?= (int) $v['id'] ?>? El stock se devolverá al inventario.')) { this.value = '<?= e($v['estado']) ?>'; return; }
                      this.form.submit();">
                      <?php foreach (ESTADOS_VENTA as $k => $n): ?>
                        <option value="<?= e($k) ?>" <?= $v['estado'] === $k ? 'selected' : '' ?>><?= e($n) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                </td>
                <td>
                  <details class="venta-detalle">
                    <summary><?= (int) $v['lineas'] ?> producto(s)</summary>
                    <table class="mini-table">
                      <?php foreach ($items[(int) $v['id']] ?? [] as $it): ?>
                        <tr>
                          <td><?= e($it['nombre']) ?><?= $it['producto_id'] === null ? ' <small class="muted">(producto eliminado)</small>' : '' ?></td>
                          <td class="num">× <?= (int) $it['cantidad'] ?></td>
                          <td class="num"><?= precio($it['subtotal']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                      <tr class="mini-sep"><td colspan="2">Subtotal</td><td class="num"><?= precio($v['subtotal']) ?></td></tr>
                      <tr><td colspan="2">Envío</td><td class="num"><?= (float) $v['envio'] > 0 ? precio($v['envio']) : 'Gratis' ?></td></tr>
                      <tr><td colspan="2">IVA</td><td class="num"><?= precio($v['iva']) ?></td></tr>
                      <tr class="mini-total"><td colspan="2">Total</td><td class="num"><?= precio($v['total']) ?></td></tr>
                    </table>
                  </details>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$ventas): ?>
              <tr><td colspan="7">No hay ventas que coincidan con los filtros.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <?php endif; ?>
<?php require __DIR__ . '/includes/admin_footer.php';
