<?php
require_once __DIR__ . '/db.php';

$titulo_pagina = 'Ingresar o Registrarse | Players of Dominion';
$descripcion_pagina = 'Inicia sesión o crea tu cuenta en Players of Dominion para gestionar tus pedidos y tu perfil gamer.';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion  = $_POST['accion'] ?? 'login';
    $correo  = trim((string) ($_POST['correo'] ?? ''));
    $clave   = (string) ($_POST['password'] ?? '');

    try {
        if ($accion === 'registro') {
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            if ($nombre === '' || $correo === '' || strlen($clave) < 6) {
                throw new RuntimeException('Completa todos los campos (contraseña mínimo 6 caracteres).');
            }
            $stmt = db()->prepare('INSERT INTO usuarios (nombre, correo, password_hash) VALUES (:n, :c, :p)');
            $stmt->execute([
                ':n' => $nombre,
                ':c' => $correo,
                ':p' => password_hash($clave, PASSWORD_DEFAULT),
            ]);
        }

        $stmt = db()->prepare('SELECT id, nombre, correo, avatar_url, rol, password_hash FROM usuarios WHERE correo = :c');
        $stmt->execute([':c' => $correo]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($clave, $usuario['password_hash'])) {
            throw new RuntimeException('Correo o contraseña incorrectos.');
        }

        unset($usuario['password_hash']);
        $_SESSION['usuario'] = $usuario;
        header('Location: ' . ($usuario['rol'] === 'admin' ? 'admin/index.php' : 'cuenta.php'));
        exit;
    } catch (PDOException $ex) {
        $error = 'Ese correo ya está registrado.';
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

require __DIR__ . '/includes/header.php';
?>
      <section class="section">
        <div class="shell">
          <div class="section-header">
            <h2>Acceso a tu Cuenta</h2>
            <p>Inicia sesión o crea una cuenta nueva para comprar más rápido.</p>
          </div>

          <?php if ($error): ?>
            <div class="alert" style="border-color:#ff4d6d;background:rgba(255,77,109,.12)"><?= e($error) ?></div>
          <?php elseif (isset($_GET['admin_requerido'])): ?>
            <div class="alert">Inicia sesión con una cuenta de administrador para entrar al panel.</div>
          <?php endif; ?>

          <div class="cart-layout">
            <div class="summary-card">
              <h3>Iniciar Sesión</h3>
              <form class="admin-form" method="post">
                <input type="hidden" name="accion" value="login" />
                <label class="full">Correo electrónico
                  <input type="email" name="correo" required />
                </label>
                <label class="full">Contraseña
                  <input type="password" name="password" required />
                </label>
                <div class="full"><button class="btn btn-primary btn-block" type="submit">Ingresar</button></div>
              </form>
            </div>

            <div class="summary-card">
              <h3>Crear Cuenta</h3>
              <form class="admin-form" method="post">
                <input type="hidden" name="accion" value="registro" />
                <label class="full">Nombre completo
                  <input type="text" name="nombre" required />
                </label>
                <label class="full">Correo electrónico
                  <input type="email" name="correo" required />
                </label>
                <label class="full">Contraseña
                  <input type="password" name="password" minlength="6" required />
                </label>
                <div class="full"><button class="btn btn-primary btn-block" type="submit">Registrarme</button></div>
              </form>
            </div>
          </div>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php';
