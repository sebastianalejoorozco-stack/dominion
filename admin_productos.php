<?php
/**
 * El panel administrativo se movió a /admin para quedar separado
 * de la interfaz pública (ver admin/index.php, admin/productos.php
 * y admin/reportes.php). Este archivo solo redirige por si alguien
 * tenía guardado el enlace anterior.
 */
header('Location: admin/productos.php' . (isset($_GET['editar']) ? '?editar=' . (int) $_GET['editar'] : ''));
exit;
