<?php
/**
 * Conexión segura a MySQL mediante PDO.
 * Players of Dominion — SENA ADSO
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const DB_HOST = '127.0.0.1';
const DB_NAME = 'players_of_dominion';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Error de conexión con la base de datos.');
    }

    return $pdo;
}

/** Escape seguro para imprimir en HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Formatea un precio en pesos colombianos. */
function precio($value): string
{
    return '$' . number_format((float) $value, 0, ',', '.');
}

/**
 * Las imágenes de producto (imagen_url) se guardan como rutas relativas
 * a la raíz del sitio (ej: "assets/img/productos/pc-core.svg"), tal como
 * las usan index.php, catalogo.php, producto.php y carrito.php.
 * Las páginas de /admin viven un nivel más abajo, así que esta función
 * antepone "../" cuando la ruta es relativa (las URL absolutas o que
 * empiezan con "/" se devuelven sin cambios).
 */
function imagen_admin(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#^([a-z]+:)?//#i', $url) || str_starts_with($url, '/')) {
        return $url;
    }
    return '../' . $url;
}

/** Usuario en sesión o null. */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/** ¿El usuario en sesión tiene rol de administrador? */
function es_admin(): bool
{
    $usuario = usuario_actual();
    return $usuario !== null && ($usuario['rol'] ?? '') === 'admin';
}

/**
 * Exige que la sesión activa sea de un administrador.
 * Si no lo es, redirige al login (o a la tienda) y detiene la ejecución.
 * Debe llamarse ANTES de imprimir cualquier HTML.
 */
function exigir_admin(string $ruta_login = '../login.php', string $ruta_tienda = '../index.php'): void
{
    $usuario = usuario_actual();

    if ($usuario === null) {
        header('Location: ' . $ruta_login . '?admin_requerido=1');
        exit;
    }

    if (($usuario['rol'] ?? '') !== 'admin') {
        header('Location: ' . $ruta_tienda . '?acceso_denegado=1');
        exit;
    }
}

/** Cantidad total de artículos en el carrito. */
function carrito_cantidad(): int
{
    $total = 0;
    foreach ($_SESSION['carrito'] ?? [] as $cantidad) {
        $total += (int) $cantidad;
    }
    return $total;
}


/** Token CSRF de la sesión (se crea la primera vez que se pide). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo oculto listo para pegar dentro de un <form method="post">. */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '" />';
}

/** ¿El POST actual trae un token CSRF válido? */
function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals((string) $_SESSION['csrf'], (string) $_POST['csrf']);
}

/** Envío, IVA y total a partir del subtotal (mismas reglas que el carrito). */
function calcular_totales(float $subtotal): array
{
    $envio = $subtotal > 0 && $subtotal < 3000000 ? 45000.0 : 0.0;
    $iva   = round($subtotal * 0.19, 2);
    return [$envio, $iva, $subtotal + $envio + $iva];
}

/**
 * Registra una venta a partir del carrito ([producto_id => cantidad]).
 * Todo ocurre en una transacción: valida stock, guarda la venta con sus
 * líneas (con foto de nombre/precio/categoría) y descuenta el inventario.
 * Devuelve el id de la venta o lanza RuntimeException con un mensaje claro.
 */
function registrar_venta(array $usuario, array $carrito): int
{
    $carrito = array_filter(array_map('intval', $carrito), fn ($c) => $c > 0);
    if (!$carrito) {
        throw new RuntimeException('Tu carrito está vacío.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $ids = array_map('intval', array_keys($carrito));
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT p.id, p.nombre, p.precio, p.stock, c.nombre AS categoria
                               FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id
                               WHERE p.id IN ($in) FOR UPDATE");
        $stmt->execute($ids);
        $productos = [];
        foreach ($stmt->fetchAll() as $p) {
            $productos[(int) $p['id']] = $p;
        }

        $lineas   = [];
        $subtotal = 0.0;
        foreach ($carrito as $pid => $cant) {
            $p = $productos[$pid] ?? null;
            if ($p === null) {
                throw new RuntimeException('Un producto de tu carrito ya no está disponible. Revisa el carrito.');
            }
            if ((int) $p['stock'] < $cant) {
                throw new RuntimeException('Stock insuficiente para "' . $p['nombre'] . '" (disponibles: ' . (int) $p['stock'] . ').');
            }
            $linea = (float) $p['precio'] * $cant;
            $subtotal += $linea;
            $lineas[] = [$p, $cant, $linea];
        }

        [$envio, $iva, $total] = calcular_totales($subtotal);

        $pdo->prepare('INSERT INTO ventas (usuario_id, cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado)
                       VALUES (?, ?, ?, ?, ?, ?, ?, "pagada")')
            ->execute([(int) $usuario['id'], $usuario['nombre'], $usuario['correo'], $subtotal, $envio, $iva, $total]);
        $venta_id = (int) $pdo->lastInsertId();

        $ins = $pdo->prepare('INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal)
                              VALUES (?, ?, ?, ?, ?, ?, ?)');
        $baja = $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?');
        foreach ($lineas as [$p, $cant, $linea]) {
            $ins->execute([$venta_id, $p['id'], $p['nombre'], $p['categoria'], $p['precio'], $cant, $linea]);
            $baja->execute([$cant, $p['id']]);
        }

        $pdo->commit();
        return $venta_id;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        if ($ex instanceof RuntimeException) {
            throw $ex;
        }
        throw new RuntimeException('No se pudo registrar la compra. Intenta de nuevo.');
    }
}

/** ¿Ya existen las tablas de ventas? (por si falta correr sql/ventas.sql) */
function tablas_ventas_existen(): bool
{
    try {
        db()->query('SELECT 1 FROM ventas LIMIT 1');
        db()->query('SELECT 1 FROM venta_items LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
