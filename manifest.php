<?php
/**
 * manifest.php — Web App Manifest dinámico.
 * Lee el nombre del restaurante desde la BD para personalizarlo.
 */
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

require_once __DIR__ . '/conexion.php';

$nombre = 'Dony Software POS';
$logo   = 'img/LogoCardon.jpeg';

$res = $conexion->query("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre','logo_path')");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        if ($r['clave'] === 'nombre' && $r['valor'])    $nombre = $r['valor'];
        if ($r['clave'] === 'logo_path' && $r['valor']) $logo   = $r['valor'];
    }
}

$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

echo json_encode([
    'name'             => $nombre,
    'short_name'       => mb_substr($nombre, 0, 12),
    'description'      => 'Sistema de gestión de pedidos para restaurantes',
    'start_url'        => $base . 'index.php',
    'scope'            => $base,
    'display'          => 'standalone',
    'background_color' => '#1a1a2e',
    'theme_color'      => '#1a1a2e',
    'orientation'      => 'any',
    'lang'             => 'es',
    'icons'            => [
        ['src' => $base . $logo, 'sizes' => '192x192', 'type' => 'image/jpeg'],
        ['src' => $base . $logo, 'sizes' => '512x512', 'type' => 'image/jpeg'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
