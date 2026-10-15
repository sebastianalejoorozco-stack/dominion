<?php
require_once __DIR__ . '/db.php';

if (!usuario_actual()) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['salir'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$mensaje = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = db()->prepare('UPDATE usuarios SET nombre = :n, avatar_url = :a WHERE id = :id');
    $stmt->execute([
        ':n'  => trim((string) $_POST['nombre']),
        ':a'  => trim((string) $_POST['avatar_url']) ?: null,
        ':id' => (int) $_SESSION['usuario']['id'],
    ]);
    $_SESSION['usuario']['nombre']     = trim((string) $_POST['nombre']);
    $_SESSION['usuario']['avatar_url'] = trim((string) $_POST['avatar_url']) ?: null;
    $mensaje = 'Perfil actualizado correctamente.';
}

$usuario = usuario_actual();
$titulo_pagina = 'Mi Cuenta | Players of Dominion';
$descripcion_pagina = 'Administra tu perfil, tu foto y tus datos de sesión en Players of Dominion.';

require __DIR__ . '/includes/header.php';
?>
      <section class="section">
        <div class="shell">
          <nav class="breadcrumbs" aria-label="Ruta de navegación">
            <a href="index.php">Inicio</a> <span>/</span> <strong>Mi Cuenta</strong>
          </nav>

          <div class="section-header">
            <h2>Hola, <?= e($usuario['nombre']) ?></h2>
            <p>Sesión activa como <?= e($usuario['correo']) ?>.</p>
          </div>

          <?php if ($mensaje): ?><div class="alert"><?= e($mensaje) ?></div><?php endif; ?>

          <div class="summary-card">
            <h3>Datos del Perfil</h3>
            <form class="admin-form" method="post">
              <label class="full">Nombre completo
                <input type="text" name="nombre" value="<?= e($usuario['nombre']) ?>" required />
              </label>
              <label class="full">URL de tu foto de perfil
                <input type="url" name="avatar_url" value="<?= e($usuario['avatar_url'] ?? '') ?>" placeholder="https://..." />
              </label>
              <div class="full hero-actions">
                <button class="btn btn-primary" type="submit">Guardar Cambios</button>
                <a class="btn btn-outline" href="cuenta.php?salir=1">Cerrar Sesión</a>
              </div>
            </form>
          </div>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php';
