-- =====================================================
-- Players of Dominion — Carga de imágenes de producto
-- =====================================================
-- Ejecuta este script UNA sola vez si tu base de datos ya existía
-- antes de que se agregaran las fotos (es decir, si ya corriste
-- schema.sql en el pasado y no quieres perder tus datos).
-- Si vas a crear la base de datos desde cero, no necesitas este
-- archivo: schema.sql ya inserta las imágenes.

USE players_of_dominion;

UPDATE productos SET imagen_url = 'assets/img/productos/pc-core.svg'
  WHERE nombre = 'PC Dominion Core i7 RTX 4060';

UPDATE productos SET imagen_url = 'assets/img/productos/monitor-vortex.svg'
  WHERE nombre = 'Monitor Vortex 27" QHD 240Hz';

UPDATE productos SET imagen_url = 'assets/img/productos/mouse-aether.svg'
  WHERE nombre = 'Mouse Aether Pro Inalámbrico 8K';

UPDATE productos SET imagen_url = 'assets/img/productos/keyboard-titan.svg'
  WHERE nombre = 'Teclado Titan Óptico Mecánico RGB';

UPDATE productos SET imagen_url = 'assets/img/productos/pc-apex.svg'
  WHERE nombre = 'PC Dominion Apex RTX 4080 Super';

UPDATE productos SET imagen_url = 'assets/img/productos/gpu-msi.svg'
  WHERE nombre = 'Tarjeta Gráfica MSI RTX 4070 Ventus 3X';
