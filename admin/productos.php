<?php
require_once __DIR__ . '/includes/auth.php';

$admin_titulo    = 'Gestión de Productos';
$admin_subtitulo = 'Crea, consulta, actualiza y elimina productos del catálogo.';
$titulo_pagina   = 'Productos | Panel Admin — Players of Dominion';

$mensaje = null;
$error   = null;

// ---------- CREATE / UPDATE / DELETE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if (!csrf_valido()) {
            throw new RuntimeException('Sesión expirada o formulario inválido. Recarga la página e intenta de nuevo.');
        }

        if ($accion === 'eliminar') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('SELECT nombre FROM productos WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $nombre = $stmt->fetchColumn();
            if ($nombre === false) {
                throw new RuntimeException('El producto ya no existe.');
            }
            // El historial de ventas se conserva: venta_items guarda el nombre y precio
            // al momento de la compra y solo suelta la referencia (producto_id = NULL).
            db()->prepare('DELETE FROM productos WHERE id = :id')->execute([':id' => $id]);
            unset($_SESSION['carrito'][$id]);
            $mensaje = 'Producto «' . $nombre . '» eliminado correctamente.';
        } elseif ($accion === 'guardar') {
            $datos = [
                ':nombre'       => trim((string) $_POST['nombre']),
                ':marca'        => trim((string) $_POST['marca']),
                ':etiqueta'     => trim((string) $_POST['etiqueta']) ?: null,
                ':descripcion'  => trim((string) $_POST['descripcion']),
                ':precio'       => (float) $_POST['precio'],
                ':precio_antes' => $_POST['precio_antes'] !== '' ? (float) $_POST['precio_antes'] : null,
                ':stock'        => (int) $_POST['stock'],
                ':imagen_url'   => trim((string) $_POST['imagen_url']) ?: null,
                ':destacado'    => isset($_POST['destacado']) ? 1 : 0,
                ':categoria_id' => (int) $_POST['categoria_id'] ?: null,
            ];

            if ($datos[':nombre'] === '' || $datos[':precio'] <= 0) {
                throw new RuntimeException('El nombre y un precio mayor a cero son obligatorios.');
            }

            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $datos[':id'] = $id;
                $sql = 'UPDATE productos SET nombre=:nombre, marca=:marca, etiqueta=:etiqueta,
                          descripcion=:descripcion, precio=:precio, precio_antes=:precio_antes,
                          stock=:stock, imagen_url=:imagen_url, destacado=:destacado,
                          categoria_id=:categoria_id
                        WHERE id=:id';
                $mensaje = 'Producto actualizado correctamente.';
            } else {
                $sql = 'INSERT INTO productos
                          (nombre, marca, etiqueta, descripcion, precio, precio_antes, stock, imagen_url, destacado, categoria_id)
                        VALUES
                          (:nombre, :marca, :etiqueta, :descripcion, :precio, :precio_antes, :stock, :imagen_url, :destacado, :categoria_id)';
                $mensaje = 'Producto creado correctamente.';
            }

            db()->prepare($sql)->execute($datos);
        }
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

// ---------- READ ----------
$editar = null;
if (isset($_GET['editar'])) {
    $stmt = db()->prepare('SELECT * FROM productos WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['editar']]);
    $editar = $stmt->fetch() ?: null;
}

$categorias = db()->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();
$productos  = db()->query('SELECT p.*, c.nombre AS categoria_nombre
                           FROM productos p
                           LEFT JOIN categorias c ON c.id = p.categoria_id
                           ORDER BY p.id DESC')->fetchAll();

require __DIR__ . '/includes/admin_header.php';
?>
        <?php if ($mensaje): ?><div class="alert"><?= e($mensaje) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert" style="border-color:#ff4d6d;background:rgba(255,77,109,.12)"><?= e($error) ?></div><?php endif; ?>

        <div class="summary-card">
          <h3><?= $editar ? 'Editar Producto #' . (int) $editar['id'] : 'Nuevo Producto' ?></h3>
          <form class="admin-form" method="post">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="guardar" />
            <input type="hidden" name="id" value="<?= (int) ($editar['id'] ?? 0) ?>" />

            <label class="full">Nombre
              <input type="text" name="nombre" required value="<?= e($editar['nombre'] ?? '') ?>" />
            </label>

            <label>Marca
              <input type="text" name="marca" value="<?= e($editar['marca'] ?? 'Dominion') ?>" />
            </label>

            <label>Etiqueta
              <input type="text" name="etiqueta" value="<?= e($editar['etiqueta'] ?? '') ?>" placeholder="Ej: Más vendido" />
            </label>

            <label>Precio (COP)
              <input type="number" step="1" min="0" name="precio" required value="<?= e((string) ($editar['precio'] ?? '')) ?>" />
            </label>

            <label>Precio anterior (opcional)
              <input type="number" step="1" min="0" name="precio_antes" value="<?= e((string) ($editar['precio_antes'] ?? '')) ?>" />
            </label>

            <label>Stock
              <input type="number" min="0" name="stock" value="<?= (int) ($editar['stock'] ?? 0) ?>" />
            </label>

            <label>Categoría
              <select name="categoria_id">
                <option value="0">Sin categoría</option>
                <?php foreach ($categorias as $c): ?>
                  <option value="<?= (int) $c['id'] ?>" <?= (int) ($editar['categoria_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="full">URL de imagen
              <input type="text" name="imagen_url" value="<?= e($editar['imagen_url'] ?? '') ?>" placeholder="https://... o assets/img/productos/archivo.svg" />
            </label>
            <?php if (!empty($editar['imagen_url'])): ?>
              <div class="full">
                <img class="admin-thumb" style="width:84px;height:84px" src="<?= e(imagen_admin($editar['imagen_url'])) ?>" alt="Vista previa" />
              </div>
            <?php endif; ?>

            <label class="full">Descripción
              <textarea name="descripcion" rows="3"><?= e($editar['descripcion'] ?? '') ?></textarea>
            </label>

            <label class="filter-check full">
              <input type="checkbox" name="destacado" value="1" <?= !empty($editar['destacado']) ? 'checked' : '' ?> />
              Mostrar en productos destacados de la página de inicio
            </label>

            <div class="full hero-actions">
              <button class="btn btn-primary" type="submit"><?= $editar ? 'Guardar Cambios' : 'Crear Producto' ?></button>
              <?php if ($editar): ?>
                <a class="btn btn-outline" href="productos.php">Cancelar</a>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <table class="admin-table">
          <thead>
            <tr>
              <th></th><th>ID</th><th>Nombre</th><th>Categoría</th><th>Marca</th>
              <th class="num">Precio</th><th class="num">Stock</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($productos as $p): ?>
              <tr class="<?= (int) $p['stock'] <= 5 ? 'stock-low' : '' ?>">
                <td>
                  <?php if ($p['imagen_url']): ?>
                    <img class="admin-thumb" src="<?= e(imagen_admin($p['imagen_url'])) ?>" alt="" />
                  <?php endif; ?>
                </td>
                <td><?= (int) $p['id'] ?></td>
                <td><a href="../producto.php?id=<?= (int) $p['id'] ?>"><?= e($p['nombre']) ?></a></td>
                <td><?= e($p['categoria_nombre'] ?? '—') ?></td>
                <td><?= e($p['marca']) ?></td>
                <td class="num"><?= precio($p['precio']) ?></td>
                <td class="num <?= (int) $p['stock'] <= 5 ? 'stock-value' : '' ?>"><?= (int) $p['stock'] ?></td>
                <td class="acciones-cell">
                  <a class="btn btn-card" href="productos.php?editar=<?= (int) $p['id'] ?>">Editar</a>
                  <form method="post" style="display:inline"
                        onsubmit="return confirm('¿Eliminar «<?= e(addslashes($p['nombre'])) ?>»? Esta acción no se puede deshacer. Las ventas ya registradas se conservan.')">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="eliminar" />
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>" />
                    <button class="btn-danger" type="submit">🗑 Eliminar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$productos): ?>
              <tr><td colspan="8">Aún no hay productos registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
<?php require __DIR__ . '/includes/admin_footer.php';
