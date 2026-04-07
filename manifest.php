<?php
// Silenciar cualquier error/warning para no romper el JSON
error_reporting(0);
ob_start();

require_once __DIR__ . '/conexion.php';

$nombre = 'Dony Software POS';
$logo   = 'img/LogoCardon.jpeg';

try {
    $res = $conexion->query("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre','logo_path')");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            if ($r['clave'] === 'nombre'    && $r['valor']) $nombre = $r['valor'];
            if ($r['clave'] === 'logo_path' && $r['valor']) $logo   = $r['valor'];
        }
    }
} catch (Throwable $e) { /* sin BD usamos defaults */ }

ob_clean(); // descartar cualquier output previo (warnings, notices)

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Calcular base path dinámicamente
$script = $_SERVER['SCRIPT_NAME'] ?? '/manifest.php';
$base   = rtrim(dirname($script), '/') . '/';

echo json_encode([
    'name'             => $nombre,
    'short_name'       => mb_substr($nombre, 0, 14),
    'description'      => 'Sistema de gestión de pedidos',
    'start_url'        => $base . 'index.php',
    'scope'            => $base,
    'display'          => 'standalone',
    'background_color' => '#1a1a2e',
    'theme_color'      => '#1a1a2e',
    'orientation'      => 'any',
    'lang'             => 'es',
    'icons'            => [
        [
            'src'     => $base . 'img/icon-192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src'     => $base . 'img/icon-512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
