# Players of Dominion

Tienda de hardware gamer y periféricos — proyecto PHP + MySQL (SENA ADSO).

## Requisitos

- PHP 8.1 o superior con la extensión **PDO MySQL** habilitada.
- MySQL o MariaDB.
- Un servidor local tipo XAMPP, Laragon, MAMP o `php -S localhost:8000`.

## Instalación

1. Copia la carpeta `dominion/` a tu servidor (ej. `htdocs/` en XAMPP).
2. Crea la base de datos ejecutando **uno** de estos scripts en tu cliente MySQL:
   - `sql/schema.sql` → crea la base `players_of_dominion`, las tablas y los
     datos de ejemplo **con fotos de producto incluidas**. Úsalo si vas a
     instalar el proyecto desde cero.
   - Si ya tenías la base de datos creada de una instalación anterior (sin
     fotos), no vuelvas a correr `schema.sql` para no duplicar productos:
     corre solo `sql/actualizar_imagenes.sql`, que agrega las rutas de
     imagen a los productos existentes por nombre.
3. Revisa las credenciales de conexión en `db.php` (`DB_HOST`, `DB_USER`,
   `DB_PASS`) y ajústalas a tu entorno si no usas `root` sin contraseña.
4. Abre `index.php` en el navegador.

### Cuenta de administrador de prueba

```
Correo:      admin@dominion.co
Contraseña:  Dominion2026
```

Al iniciar sesión con esta cuenta entras automáticamente al **panel
administrativo** (`/admin`). Cualquier otra cuenta nueva que te registres
queda con rol `cliente` y nunca puede entrar a `/admin` aunque conozca la URL.

## Estructura del proyecto

```
dominion/
├── index.php                     Página de inicio (hero + destacados)
├── catalogo.php                  Catálogo con filtros
├── producto.php                  Ficha de producto
├── carrito.php                   Carrito de compras
├── login.php                     Ingreso / registro
├── cuenta.php                    Perfil del usuario (y cierre de sesión)
├── db.php                        Conexión PDO + helpers (e, precio, es_admin...)
├── admin_productos.php           Redirect de compatibilidad -> admin/productos.php
├── includes/
│   ├── header.php                Encabezado y nav de la tienda (pública)
│   └── footer.php
├── admin/                        Panel administrativo (SEPARADO de la tienda)
│   ├── index.php                 Dashboard con KPIs
│   ├── productos.php             CRUD de productos (antes admin_productos.php)
│   ├── reportes.php              Reportes de inventario + export CSV
│   └── includes/
│       ├── auth.php              Exige sesión con rol admin (o redirige)
│       ├── admin_header.php      Layout propio: sidebar + topbar
│       └── admin_footer.php
├── assets/
│   ├── css/
│   │   ├── stilos.css            Estilos de la tienda pública
│   │   └── admin.css             Estilos exclusivos del panel admin
│   └── img/
│       ├── logo.png              Logo (fondo transparente)
│       └── productos/            Ilustraciones de cada producto (SVG)
└── sql/
    ├── schema.sql                 Esquema completo + datos de ejemplo
    └── actualizar_imagenes.sql    Agrega fotos a una BD ya existente
```

## Qué cambió en esta entrega

- **Ventas reales + tabla de ventas + estadísticas.** "Finalizar Compra" ahora
  registra el pedido (tablas `ventas` y `venta_items`), valida y descuenta el
  stock en una transacción. En el panel: `admin/ventas.php` (tabla con filtros,
  detalle por producto, cambio de estado —cancelar devuelve el stock— y CSV) y
  `admin/estadisticas.php` (ingresos, gráfico por día, más vendidos, categorías,
  estados y mejores clientes en 7/30/90 días). Si ya tenías la base de datos,
  corre `sql/ventas.sql` (crea las tablas y 40 ventas de ejemplo).
- **Eliminar productos.** Botón rojo "🗑 Eliminar" en `admin/productos.php` con
  confirmación por nombre y token CSRF. El historial de ventas se conserva.

- **Administrador separado de la interfaz de usuario.** El panel vive en
  `/admin`, con su propio layout (sidebar, `admin.css`) y queda protegido
  por `exigir_admin()` en `admin/includes/auth.php`: si no hay sesión te
  manda a `login.php`, y si la sesión no es de administrador te devuelve a
  la tienda con un aviso. El enlace "ADMINISTRAR" ya no aparece en el menú
  público; solo se ve un botón "Panel Admin" cuando la sesión activa es de
  un administrador.
- **Reportes.** `admin/reportes.php` agrega al panel: KPIs (productos,
  unidades, valor de inventario, stock bajo), inventario agrupado por
  categoría, alerta de productos con 5 unidades o menos, un top 5 por valor
  en inventario, y descarga en CSV de todo el catálogo.
- **Fotos de producto.** Los 6 productos de ejemplo ya tienen imagen
  (ilustraciones propias en `assets/img/productos/`, con el mismo estilo
  neón del sitio, para que el proyecto funcione sin depender de enlaces
  externos que puedan romperse). Se corrigieron además dos vistas que
  traían el campo `imagen_url` de la base de datos pero no lo mostraban:
  los "Productos Relacionados" en la ficha de producto y las filas del
  carrito. Para subir tus propias fotos, el campo "URL de imagen" en
  `admin/productos.php` acepta tanto una URL externa (`https://...`) como
  una ruta local dentro de `assets/img/...`.
- **Logo y banner.** El logo se cargó en el encabezado y en el banner
  principal de la página de inicio. Se ajustaron las dimensiones de las
  imágenes de producto (tarjetas, ficha de producto, carrito) para que se
  vean consistentes sin importar el tamaño original de cada foto, y el
  ancho máximo del sitio crece un poco en monitores muy grandes (≥1680px).
- **Corrección de base de datos.** `db.php` apuntaba a una base llamada
  `dominion`, pero `sql/schema.sql` creaba `players_of_dominion`. Se
  unificó todo en `players_of_dominion`.

## Notas

- El checkout es una simulación (no hay pasarela de pago real todavía).
- Las contraseñas se guardan con `password_hash()` de PHP (bcrypt).
