<?php
require_once __DIR__ . '/db.php';

$titulo_pagina = 'Carrito de Compras | Players of Dominion';
$descripcion_pagina = 'Revisa el hardware seleccionado, ajusta cantidades, calcula el envío y finaliza tu compra en Players of Dominion.';

// --- Acciones del carrito ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actualizar'])) {
        foreach ((array) ($_POST['cantidad'] ?? []) as $pid => $cant) {
            $pid  = (int) $pid;
            $cant = (int) $cant;
            if ($cant > 0) {
                $_SESSION['carrito'][$pid] = $cant;
            } else {
                unset($_SESSION['carrito'][$pid]);
            }
        }
    }
    if (isset($_POST['eliminar'])) {
        unset($_SESSION['carrito'][(int) $_POST['eliminar']]);
    }
    if (isset($_POST['vaciar'])) {
        $_SESSION['carrito'] = [];
    }
    if (isset($_POST['finalizar'])) {
        if (!usuario_actual()) {
            header('Location: login.php?checkout=1');
            exit;
        }
        if (!csrf_valido()) {
            $_SESSION['carrito_error'] = 'Sesión expirada, intenta de nuevo.';
            header('Location: carrito.php');
            exit;
        }
        try {
            $venta_id = registrar_venta(usuario_actual(), $_SESSION['carrito'] ?? []);
            $_SESSION['carrito'] = [];
            header('Location: carrito.php?compra=' . $venta_id);
        } catch (RuntimeException $ex) {
            $_SESSION['carrito_error'] = $ex->getMessage();
            header('Location: carrito.php');
        }
        exit;
    }
    header('Location: carrito.php');
    exit;
}

$error_carrito = $_SESSION['carrito_error'] ?? null;
unset($_SESSION['carrito_error']);
$compra_ok = isset($_GET['compra']) ? (int) $_GET['compra'] : 0;

$carrito = $_SESSION['carrito'] ?? [];
$lineas  = [];
$subtotal = 0.0;

if ($carrito) {
    $ids  = array_map('intval', array_keys($carrito));
    $in   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, nombre, precio, imagen_url FROM productos WHERE id IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $p) {
        $cant = (int) $carrito[$p['id']];
        $p['cantidad'] = $cant;
        $p['total']    = $p['precio'] * $cant;
        $subtotal     += $p['total'];
        $lineas[]      = $p;
    }
}

[$envio, $iva, $total] = calcular_totales($subtotal);

require __DIR__ . '/includes/header.php';
?>
      <section class="section">
        <div class="shell">
          <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="index.php">Inicio</a> <span>/</span> <strong>Carrito</strong>
          </nav>

          <div class="section-header">
            <h2>Tu Carrito de Compras</h2>
            <p><?= count($lineas) ?> tipo(s) de producto seleccionados.</p>
          </div>

          <?php if ($error_carrito): ?><div class="alert" style="border-color:#ff4d6d;background:rgba(255,77,109,.12)"><?= e($error_carrito) ?></div><?php endif; ?>
          <?php if ($compra_ok): ?><div class="alert">¡Gracias por tu compra! Tu pedido #<?= $compra_ok ?> fue registrado correctamente.</div><?php endif; ?>

          <?php if (!$lineas): ?>
            <div class="empty-state">
              <p>Tu carrito está vacío.</p>
              <a class="btn btn-primary" href="catalogo.php">Explorar la Tienda</a>
            </div>
          <?php else: ?>
            <div class="cart-layout">
              <form class="cart-lines" method="post">
                <?php foreach ($lineas as $l): ?>
                  <article class="cart-row">
                    <?php if ($l['imagen_url']): ?>
                      <img class="clearance-thumb" src="<?= e($l['imagen_url']) ?>" alt="<?= e($l['nombre']) ?>" loading="lazy" />
                    <?php else: ?>
                      <div class="media-placeholder clearance-thumb">[ IMAGEN ]</div>
                    <?php endif; ?>
                    <div>
                      <h3><a href="producto.php?id=<?= (int) $l['id'] ?>"><?= e($l['nombre']) ?></a></h3>
                      <button class="link-danger" type="submit" name="eliminar" value="<?= (int) $l['id'] ?>">Eliminar</button>
                    </div>
                    <div class="qty-control">
                      <input type="number" name="cantidad[<?= (int) $l['id'] ?>]" value="<?= (int) $l['cantidad'] ?>"
                             min="0" style="width:64px;text-align:center;background:transparent;border:0;color:#fff"
                             aria-label="Cantidad de <?= e($l['nombre']) ?>" />
                    </div>
                    <p class="product-price"><?= precio($l['total']) ?></p>
                  </article>
                <?php endforeach; ?>

                <div class="hero-actions">
                  <button class="btn btn-primary" type="submit" name="actualizar" value="1">Actualizar Cantidades</button>
                  <button class="btn btn-outline" type="submit" name="vaciar" value="1">Vaciar Carrito</button>
                </div>
              </form>

              <aside class="summary-card">
                <h3>Resumen del Pedido</h3>
                <div class="summary-row"><span>Subtotal</span><strong><?= precio($subtotal) ?></strong></div>
                <div class="summary-row"><span>Envío</span><strong><?= $envio === 0 ? 'Gratis' : precio($envio) ?></strong></div>
                <div class="summary-row"><span>IVA (19%)</span><strong><?= precio($iva) ?></strong></div>
                <div class="summary-row summary-total"><span>Total</span><strong><?= precio($total) ?></strong></div>
                <?php if (usuario_actual()): ?>
                  <form method="post">
                    <?= csrf_campo() ?>
                    <button class="btn btn-primary btn-block" type="submit" name="finalizar" value="1">Finalizar Compra</button>
                  </form>
                <?php else: ?>
                  <a class="btn btn-primary btn-block" href="login.php">Inicia sesión para comprar</a>
                <?php endif; ?>
                <a class="btn btn-outline btn-block" href="catalogo.php">Seguir Comprando</a>
              </aside>
            </div>
          <?php endif; ?>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php';
