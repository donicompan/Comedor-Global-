<?php
/**
 * updater.php — Motor de actualización automática.
 * Endpoint AJAX: solo accesible por cajeros (rol administrador).
 *
 * Acciones (GET ?accion=):
 *   check  → verifica si hay una versión nueva disponible
 *   apply  → descarga y aplica la actualización (POST)
 */
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'cajero') {
    http_response_code(403);
    die(json_encode(['ok' => false, 'msg' => 'Sin acceso.']));
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/csrf.php';

header('Content-Type: application/json; charset=utf-8');

$accion = $_GET['accion'] ?? '';

// ══════════════════════════════════════════════════════════════════════
// CHECK — Consulta si hay actualización disponible
// ══════════════════════════════════════════════════════════════════════
if ($accion === 'check') {

    if (!defined('UPDATE_MANIFEST_URL') || !UPDATE_MANIFEST_URL) {
        echo json_encode(['ok' => false, 'msg' => 'URL de actualizaciones no configurada en config.php.']);
        exit;
    }

    $ctx = stream_context_create(['http' => [
        'timeout'       => 8,
        'ignore_errors' => true,
        'user_agent'    => 'CardonPOS/' . APP_VERSION,
    ]]);

    $body = @file_get_contents(UPDATE_MANIFEST_URL, false, $ctx);
    if ($body === false) {
        echo json_encode(['ok' => false, 'msg' => 'No se pudo contactar el servidor de actualizaciones.']);
        exit;
    }

    $m = json_decode($body, true);
    if (!$m || !isset($m['version_num'], $m['version'], $m['download_url'])) {
        echo json_encode(['ok' => false, 'msg' => 'Respuesta inválida del servidor.']);
        exit;
    }

    echo json_encode([
        'ok'          => true,
        'current'     => APP_VERSION,
        'latest'      => $m['version'],
        'has_update'  => (int)$m['version_num'] > APP_VERSION_NUM,
        'changelog'   => $m['changelog'] ?? [],
        'download_url'=> $m['download_url'],
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════
// APPLY — Descarga y aplica la actualización
// ══════════════════════════════════════════════════════════════════════
if ($accion === 'apply') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'msg' => 'Método no permitido.']);
        exit;
    }

    // Validar CSRF
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Token de seguridad inválido. Recargá la página.']);
        exit;
    }

    $download_url = trim($_POST['download_url'] ?? '');

    // Validar URL
    if (!$download_url || !filter_var($download_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['ok' => false, 'msg' => 'URL de descarga inválida.']);
        exit;
    }

    // Validar host autorizado
    $host = parse_url($download_url, PHP_URL_HOST) ?? '';
    $host_ok = false;
    foreach (UPDATE_ALLOWED_HOSTS as $allowed) {
        if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
            $host_ok = true;
            break;
        }
    }
    if (!$host_ok) {
        echo json_encode(['ok' => false, 'msg' => 'Origen de descarga no autorizado.']);
        exit;
    }

    // ── 1. Descargar ZIP ──────────────────────────────────────────────
    $tmp = sys_get_temp_dir() . '/cardon_update_' . time() . '.zip';
    $ctx = stream_context_create(['http' => [
        'timeout'    => 120,
        'user_agent' => 'CardonPOS/' . APP_VERSION,
    ]]);

    $data = @file_get_contents($download_url, false, $ctx);
    if ($data === false) {
        echo json_encode(['ok' => false, 'msg' => 'Error al descargar la actualización. Verificá tu conexión.']);
        exit;
    }
    file_put_contents($tmp, $data);

    // ── 2. Validar ZIP ────────────────────────────────────────────────
    $zip = new ZipArchive();
    if ($zip->open($tmp) !== true) {
        @unlink($tmp);
        echo json_encode(['ok' => false, 'msg' => 'El archivo descargado está corrupto.']);
        exit;
    }

    // ── 3. Backup de la versión actual ────────────────────────────────
    $backup_dir = __DIR__ . '/backups';
    if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

    $backup_path = $backup_dir . '/v' . APP_VERSION . '_' . date('Ymd_His') . '.zip';
    $bzip = new ZipArchive();
    if ($bzip->open($backup_path, ZipArchive::CREATE) === true) {
        foreach (glob(__DIR__ . '/*.php') as $f) {
            $bzip->addFile($f, basename($f));
        }
        foreach (['css', 'img', 'migrations'] as $sub) {
            foreach (glob(__DIR__ . "/$sub/*") ?: [] as $f) {
                if (is_file($f)) $bzip->addFile($f, "$sub/" . basename($f));
            }
        }
        $bzip->close();
    }

    // ── 4. Extraer actualización ──────────────────────────────────────
    // Archivos que NUNCA se sobreescriben
    $protegidos      = ['config.php'];
    $prefijos_skip   = ['uploads/', 'backups/', 'BD Cardon/', '.claude/', '.git/'];
    $root_real       = realpath(__DIR__) . DIRECTORY_SEPARATOR;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $nombre = $zip->getNameIndex($i);

        // Saltar directorios
        if (str_ends_with($nombre, '/')) continue;

        // Saltar archivos protegidos
        if (in_array($nombre, $protegidos)) continue;
        $skip = false;
        foreach ($prefijos_skip as $pref) {
            if (str_starts_with($nombre, $pref)) { $skip = true; break; }
        }
        if ($skip) continue;

        // Prevenir path traversal
        $destino = $root_real . str_replace('/', DIRECTORY_SEPARATOR, $nombre);
        $dir_destino = dirname($destino);
        if (!str_starts_with(realpath($dir_destino) ?: $dir_destino, rtrim($root_real, DIRECTORY_SEPARATOR))) continue;

        if (!is_dir($dir_destino)) mkdir($dir_destino, 0755, true);
        file_put_contents($destino, $zip->getFromIndex($i));
    }

    $zip->close();
    @unlink($tmp);

    // Limpiar backups viejos — conservar solo los últimos 5
    $backups_existentes = glob($backup_dir . '/*.zip') ?: [];
    usort($backups_existentes, fn($a, $b) => filemtime($a) - filemtime($b)); // más viejos primero
    $sobrantes = array_slice($backups_existentes, 0, max(0, count($backups_existentes) - 5));
    foreach ($sobrantes as $viejo) { @unlink($viejo); }

    // Las migraciones de BD se aplican automáticamente en el próximo request (app.php).
    echo json_encode(['ok' => true, 'msg' => '¡Actualización aplicada correctamente!']);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Acción desconocida.']);
