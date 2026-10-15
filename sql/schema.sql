-- =====================================================
-- Players of Dominion — Esquema de base de datos MySQL
-- =====================================================
CREATE DATABASE IF NOT EXISTS players_of_dominion
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE players_of_dominion;

-- ---------- USUARIOS ----------
CREATE TABLE IF NOT EXISTS usuarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(120)  NOT NULL,
  correo        VARCHAR(160)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  avatar_url    VARCHAR(255)  DEFAULT NULL,
  rol           ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
  creado_en     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- CATEGORIAS ----------
CREATE TABLE IF NOT EXISTS categorias (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(90)  NOT NULL,
  slug        VARCHAR(90)  NOT NULL UNIQUE,
  descripcion VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- PRODUCTOS ----------
CREATE TABLE IF NOT EXISTS productos (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(160)   NOT NULL,
  marca         VARCHAR(80)    NOT NULL DEFAULT 'Dominion',
  etiqueta      VARCHAR(60)    DEFAULT NULL,
  descripcion   TEXT           DEFAULT NULL,
  precio        DECIMAL(12,2)  NOT NULL,
  precio_antes  DECIMAL(12,2)  DEFAULT NULL,
  stock         INT            NOT NULL DEFAULT 0,
  imagen_url    VARCHAR(255)   DEFAULT NULL,
  destacado     TINYINT(1)     NOT NULL DEFAULT 0,
  categoria_id  INT            DEFAULT NULL,
  creado_en     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_producto_categoria FOREIGN KEY (categoria_id)
    REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- DATOS DE EJEMPLO ----------
INSERT INTO categorias (nombre, slug, descripcion) VALUES
  ('PC Gamer',     'pcs',          'Equipos ensamblados y probados en banco.'),
  ('Monitores',    'monitores',    'Pantallas esports de alta tasa de refresco.'),
  ('Periféricos',  'perifericos',  'Teclados, mouse, diademas y superficies.'),
  ('Componentes',  'componentes',  'Tarjetas gráficas, procesadores y memorias.')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO productos (nombre, marca, etiqueta, descripcion, precio, precio_antes, stock, imagen_url, destacado, categoria_id) VALUES
  ('PC Dominion Core i7 RTX 4060', 'Dominion', 'Oferta especial', 'Equipo equilibrado para 1440p, ensamblado y probado en casa.', 5199000, 5999000, 8, 'assets/img/productos/pc-core.svg', 1, 1),
  ('Monitor Vortex 27" QHD 240Hz', 'ASUS', 'Nuevo 240Hz', 'Panel Fast IPS ajustado para shooters competitivos, 1ms GtG.', 1719000, NULL, 14, 'assets/img/productos/monitor-vortex.svg', 1, 2),
  ('Mouse Aether Pro Inalámbrico 8K', 'Dominion', 'Más vendido', 'Chasis de magnesio de 54g con dongle de 8000Hz y sensor de 32K DPI.', 359000, NULL, 42, 'assets/img/productos/mouse-aether.svg', 1, 3),
  ('Teclado Titan Óptico Mecánico RGB', 'MSI', 'Serie Pro', 'Teclado óptico hot-swap con RGB por tecla y actuación de 0.2ms.', 559000, NULL, 23, 'assets/img/productos/keyboard-titan.svg', 1, 3),
  ('PC Dominion Apex RTX 4080 Super', 'Dominion', 'Insignia', 'Build 4K con refrigeración líquida y cableado impecable.', 10799000, NULL, 4, 'assets/img/productos/pc-apex.svg', 0, 1),
  ('Tarjeta Gráfica MSI RTX 4070 Ventus 3X', 'MSI', 'Liquidación', 'Triple ventilador con overclock de fábrica para 1440p silencioso.', 2239000, 2599000, 11, 'assets/img/productos/gpu-msi.svg', 0, 4);

-- Usuario administrador de ejemplo (contraseña: Dominion2026)
INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES
  ('Administrador', 'admin@dominion.co', '$2b$10$kLB1P1PATyTcKPj3SfG3Kedm0D2RKq66EshCoaUQwMhR3GbF6tcfO', 'admin')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ---------- VENTAS ----------
CREATE TABLE IF NOT EXISTS ventas (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id     INT            DEFAULT NULL,
  cliente_nombre VARCHAR(120)   NOT NULL,
  cliente_correo VARCHAR(160)   NOT NULL,
  subtotal       DECIMAL(12,2)  NOT NULL,
  envio          DECIMAL(12,2)  NOT NULL DEFAULT 0,
  iva            DECIMAL(12,2)  NOT NULL DEFAULT 0,
  total          DECIMAL(12,2)  NOT NULL,
  estado         ENUM('pagada','enviada','entregada','cancelada') NOT NULL DEFAULT 'pagada',
  creado_en      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ventas_fecha (creado_en),
  CONSTRAINT fk_venta_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cada línea guarda una "foto" del nombre, categoría y precio al momento de la
-- compra. Así, si luego se ELIMINA el producto del catálogo, el historial de
-- ventas se conserva intacto (producto_id queda en NULL).
CREATE TABLE IF NOT EXISTS venta_items (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  venta_id        INT            NOT NULL,
  producto_id     INT            DEFAULT NULL,
  nombre          VARCHAR(160)   NOT NULL,
  categoria       VARCHAR(90)    DEFAULT NULL,
  precio_unitario DECIMAL(12,2)  NOT NULL,
  cantidad        INT            NOT NULL,
  subtotal        DECIMAL(12,2)  NOT NULL,
  CONSTRAINT fk_item_venta    FOREIGN KEY (venta_id)    REFERENCES ventas(id)    ON DELETE CASCADE,
  CONSTRAINT fk_item_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- VENTAS DE EJEMPLO (opcional) ----------
-- Ventas de ejemplo (solo para ver el panel con datos; borra con: DELETE FROM ventas WHERE cliente_correo LIKE '%@correo.co';)
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Camilo Rojas','camilo.rojas@correo.co',559000,45000,106210,710210,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '11:25' HOUR_MINUTE), INTERVAL 20 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Camilo Rojas','camilo.rojas@correo.co',359000,45000,68210,472210,'enviada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '17:13' HOUR_MINUTE), INTERVAL 2 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Laura Gómez','laura.gomez@correo.co',1677000,45000,318630,2040630,'cancelada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '9:52' HOUR_MINUTE), INTERVAL 41 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,3,1677000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Valentina Ruiz','valen.ruiz@correo.co',359000,45000,68210,472210,'enviada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '15:03' HOUR_MINUTE), INTERVAL 43 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Santiago Mora','santi.mora@correo.co',6834000,0,1298460,8132460,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '10:36' HOUR_MINUTE), INTERVAL 7 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,3,1677000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,3,5157000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Laura Gómez','laura.gomez@correo.co',10356000,0,1967640,12323640,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '17:45' HOUR_MINUTE), INTERVAL 24 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,3,5157000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Mariana Torres','mariana.torres@correo.co',1719000,45000,326610,2090610,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '18:59' HOUR_MINUTE), INTERVAL 19 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,1,1719000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Mariana Torres','mariana.torres@correo.co',559000,45000,106210,710210,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '18:19' HOUR_MINUTE), INTERVAL 14 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Andrés Pardo','andres.pardo@correo.co',3438000,0,653220,4091220,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '17:26' HOUR_MINUTE), INTERVAL 2 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,2,3438000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Daniela Castro','dani.castro@correo.co',1677000,45000,318630,2040630,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '10:48' HOUR_MINUTE), INTERVAL 84 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,3,1677000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Laura Gómez','laura.gomez@correo.co',359000,45000,68210,472210,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '10:53' HOUR_MINUTE), INTERVAL 66 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Mariana Torres','mariana.torres@correo.co',559000,45000,106210,710210,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '19:36' HOUR_MINUTE), INTERVAL 56 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Laura Gómez','laura.gomez@correo.co',5758000,0,1094020,6852020,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '11:39' HOUR_MINUTE), INTERVAL 32 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Andrés Pardo','andres.pardo@correo.co',3797000,0,721430,4518430,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '16:05' HOUR_MINUTE), INTERVAL 26 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,2,3438000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Juan Esteban Díaz','juan.diaz@correo.co',1719000,45000,326610,2090610,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '15:22' HOUR_MINUTE), INTERVAL 16 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,1,1719000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Andrés Pardo','andres.pardo@correo.co',1719000,45000,326610,2090610,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '9:31' HOUR_MINUTE), INTERVAL 51 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,1,1719000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Andrés Pardo','andres.pardo@correo.co',13597000,0,2583430,16180430,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '18:20' HOUR_MINUTE), INTERVAL 24 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Apex RTX 4080 Super' LIMIT 1),'PC Dominion Apex RTX 4080 Super','PC Gamer',10799000,1,10799000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Tarjeta Gráfica MSI RTX 4070 Ventus 3X' LIMIT 1),'Tarjeta Gráfica MSI RTX 4070 Ventus 3X','Componentes',2239000,1,2239000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Juan Esteban Díaz','juan.diaz@correo.co',5917000,0,1124230,7041230,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '19:51' HOUR_MINUTE), INTERVAL 74 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,2,718000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Andrés Pardo','andres.pardo@correo.co',359000,45000,68210,472210,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '12:28' HOUR_MINUTE), INTERVAL 2 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Daniela Castro','dani.castro@correo.co',7438000,0,1413220,8851220,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '17:06' HOUR_MINUTE), INTERVAL 42 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Tarjeta Gráfica MSI RTX 4070 Ventus 3X' LIMIT 1),'Tarjeta Gráfica MSI RTX 4070 Ventus 3X','Componentes',2239000,1,2239000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Daniela Castro','dani.castro@correo.co',15998000,0,3039620,19037620,'cancelada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '13:22' HOUR_MINUTE), INTERVAL 7 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Apex RTX 4080 Super' LIMIT 1),'PC Dominion Apex RTX 4080 Super','PC Gamer',10799000,1,10799000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Laura Gómez','laura.gomez@correo.co',5199000,0,987810,6186810,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '16:19' HOUR_MINUTE), INTERVAL 32 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Camilo Rojas','camilo.rojas@correo.co',2239000,45000,425410,2709410,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '20:10' HOUR_MINUTE), INTERVAL 15 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Tarjeta Gráfica MSI RTX 4070 Ventus 3X' LIMIT 1),'Tarjeta Gráfica MSI RTX 4070 Ventus 3X','Componentes',2239000,1,2239000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Camilo Rojas','camilo.rojas@correo.co',2278000,45000,432820,2755820,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '17:58' HOUR_MINUTE), INTERVAL 7 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,1,1719000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Valentina Ruiz','valen.ruiz@correo.co',359000,45000,68210,472210,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '14:49' HOUR_MINUTE), INTERVAL 78 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Valentina Ruiz','valen.ruiz@correo.co',559000,45000,106210,710210,'enviada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '21:54' HOUR_MINUTE), INTERVAL 65 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Camilo Rojas','camilo.rojas@correo.co',359000,45000,68210,472210,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '20:01' HOUR_MINUTE), INTERVAL 35 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Daniela Castro','dani.castro@correo.co',5199000,0,987810,6186810,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '16:51' HOUR_MINUTE), INTERVAL 84 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Mariana Torres','mariana.torres@correo.co',2798000,45000,531620,3374620,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '14:13' HOUR_MINUTE), INTERVAL 33 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Tarjeta Gráfica MSI RTX 4070 Ventus 3X' LIMIT 1),'Tarjeta Gráfica MSI RTX 4070 Ventus 3X','Componentes',2239000,1,2239000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Laura Gómez','laura.gomez@correo.co',6676000,0,1268440,7944440,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '21:41' HOUR_MINUTE), INTERVAL 51 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,2,1118000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Laura Gómez','laura.gomez@correo.co',2598000,45000,493620,3136620,'enviada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '19:21' HOUR_MINUTE), INTERVAL 30 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Tarjeta Gráfica MSI RTX 4070 Ventus 3X' LIMIT 1),'Tarjeta Gráfica MSI RTX 4070 Ventus 3X','Componentes',2239000,1,2239000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Mariana Torres','mariana.torres@correo.co',4517000,0,858230,5375230,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '11:37' HOUR_MINUTE), INTERVAL 6 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,1,1719000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Tarjeta Gráfica MSI RTX 4070 Ventus 3X' LIMIT 1),'Tarjeta Gráfica MSI RTX 4070 Ventus 3X','Componentes',2239000,1,2239000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Laura Gómez','laura.gomez@correo.co',2278000,45000,432820,2755820,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '9:51' HOUR_MINUTE), INTERVAL 6 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,1,559000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,1,1719000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Daniela Castro','dani.castro@correo.co',359000,45000,68210,472210,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '17:15' HOUR_MINUTE), INTERVAL 11 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Juan Esteban Díaz','juan.diaz@correo.co',10356000,0,1967640,12323640,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '16:42' HOUR_MINUTE), INTERVAL 60 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,3,5157000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Camilo Rojas','camilo.rojas@correo.co',3438000,0,653220,4091220,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '11:38' HOUR_MINUTE), INTERVAL 30 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,2,3438000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Mariana Torres','mariana.torres@correo.co',2239000,45000,425410,2709410,'pagada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '9:20' HOUR_MINUTE), INTERVAL 5 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Tarjeta Gráfica MSI RTX 4070 Ventus 3X' LIMIT 1),'Tarjeta Gráfica MSI RTX 4070 Ventus 3X','Componentes',2239000,1,2239000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Laura Gómez','laura.gomez@correo.co',5558000,0,1056020,6614020,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '9:49' HOUR_MINUTE), INTERVAL 10 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Valentina Ruiz','valen.ruiz@correo.co',7193000,0,1366670,8559670,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '17:38' HOUR_MINUTE), INTERVAL 47 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,3,5157000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Mouse Aether Pro Inalámbrico 8K' LIMIT 1),'Mouse Aether Pro Inalámbrico 8K','Periféricos',359000,1,359000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Teclado Titan Óptico Mecánico RGB' LIMIT 1),'Teclado Titan Óptico Mecánico RGB','Periféricos',559000,3,1677000);
INSERT INTO ventas (cliente_nombre, cliente_correo, subtotal, envio, iva, total, estado, creado_en) VALUES ('Valentina Ruiz','valen.ruiz@correo.co',8637000,0,1641030,10278030,'entregada',DATE_SUB(DATE_ADD(CURDATE(), INTERVAL '13:59' HOUR_MINUTE), INTERVAL 82 DAY));
SET @v = LAST_INSERT_ID();
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='Monitor Vortex 27" QHD 240Hz' LIMIT 1),'Monitor Vortex 27" QHD 240Hz','Monitores',1719000,2,3438000);
INSERT INTO venta_items (venta_id, producto_id, nombre, categoria, precio_unitario, cantidad, subtotal) VALUES (@v,(SELECT id FROM productos WHERE nombre='PC Dominion Core i7 RTX 4060' LIMIT 1),'PC Dominion Core i7 RTX 4060','PC Gamer',5199000,1,5199000);
