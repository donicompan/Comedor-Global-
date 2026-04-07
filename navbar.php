<?php
/**
 * navbar.php — Barra de navegación principal.
 * Requiere que app.php ya haya sido incluido por la página que lo llama.
 */
if (!isset($conexion)) {
    require_once __DIR__ . '/app.php';
}

// Fallback por si $app no está cargado
$app = $app ?? ['nombre' => 'Restaurante', 'logo_path' => '', 'moneda' => '$', 'licencia' => ['estado' => 'unknown', 'dias_restantes' => null]];

$ventas_hoy = $conexion->query("
    SELECT COALESCE(SUM(total_pedido), 0)
    FROM pedido
    WHERE DATE(fecha_pedido) = CURDATE()
      AND estado_pedido = 'Completado'
")->fetch_row()[0];

$pedidos_activos = $conexion->query("
    SELECT COUNT(*) FROM pedido
    WHERE estado_pedido IN ('En Proceso')
")->fetch_row()[0];

$es_cajero  = ($_SESSION['rol'] ?? '') === 'cajero';
$nombre_app = htmlspecialchars($app['nombre']);
$logo_path  = $app['logo_path'] ?? '';
$moneda     = htmlspecialchars($app['moneda'] ?? '$');

$lic_estado  = $app['licencia']['estado'] ?? 'unknown';
$dias_rest   = $app['licencia']['dias_restantes'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-0">
    <div class="container-fluid px-3">

        <!-- Marca: logo o ícono + nombre -->
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="principal.php">
            <?php if ($logo_path && file_exists(__DIR__ . '/' . $logo_path)): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo"
                     style="height:32px; width:auto; border-radius:4px; object-fit:contain;">
            <?php else: ?>
                <i class="bi bi-house-door-fill"></i>
            <?php endif; ?>
            <span><?= $nombre_app ?></span>
        </a>

        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">

            <!-- Links principales -->
            <ul class="navbar-nav me-auto mt-2 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-3" href="principal.php">
                        <i class="bi bi-grid-3x3"></i>
                        <span class="ms-1">Mesas</span>
                    </a>
                </li>

                <?php if ($es_cajero): ?>
                <li class="nav-item">
                    <a class="nav-link px-3" href="ventas.php">
                        <i class="bi bi-bar-chart-line"></i>
                        <span class="ms-1">Ventas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="admin.php">
                        <i class="bi bi-gear"></i>
                        <span class="ms-1">Administración</span>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link px-3" href="cocina.php" target="_blank">
                        <i class="bi bi-fire"></i>
                        <span class="ms-1">Cocina</span>
                        <?php if ($pedidos_activos > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pedidos_activos ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <!-- Info usuario -->
            <ul class="navbar-nav align-items-lg-center gap-2 mt-2 mt-lg-0">
                <?php if ($es_cajero): ?>
                <li class="nav-item">
                    <span class="navbar-text text-success fw-bold">
                        <i class="bi bi-cash-stack"></i>
                        <?= $moneda ?><?= number_format($ventas_hoy, 0, ',', '.') ?> hoy
                    </span>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <span class="navbar-text text-white-50 small">
                        <i class="bi bi-person-circle"></i>
                        <?= htmlspecialchars($_SESSION['usuario'] ?? '') ?>
                        <span class="badge bg-secondary ms-1"><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></span>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </li>
            </ul>

        </div>
    </div>
</nav>

<?php
// ── Banners de licencia ────────────────────────────────────
if ($lic_estado === 'gracia'):
    // Licencia vencida en período de gracia
?>
<div class="alert alert-danger alert-dismissible fade show mb-0 py-2 px-3 rounded-0 text-center small" role="alert">
    <i class="bi bi-exclamation-octagon-fill"></i>
    <strong>¡Licencia vencida!</strong>
    Estás en período de gracia — renová ahora para no perder el acceso.
    <a href="expirado.php" class="alert-link ms-2 fw-bold">Renovar licencia →</a>
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php
elseif ($lic_estado === 'activa' && $dias_rest !== null && $dias_rest <= 30):
    // Licencia activa pero próxima a vencer
    $clase_banner = $dias_rest <= 7 ? 'danger' : ($dias_rest <= 15 ? 'warning' : 'info');
    $icon_banner  = $dias_rest <= 7 ? 'exclamation-triangle-fill' : 'clock-fill';
?>
<div class="alert alert-<?= $clase_banner ?> alert-dismissible fade show mb-0 py-2 px-3 rounded-0 text-center small" role="alert">
    <i class="bi bi-<?= $icon_banner ?>"></i>
    <strong>Tu licencia vence pronto:</strong>
    <?php if ($dias_rest <= 1): ?>
        Hoy es el último día.
    <?php else: ?>
        quedan <strong><?= $dias_rest ?></strong> días.
    <?php endif; ?>
    <a href="expirado.php" class="alert-link ms-2 fw-bold">Renovar →</a>
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php
elseif ($lic_estado === 'prueba' && $dias_rest !== null):
    // Trial en curso
    $clase_banner = $dias_rest <= 3 ? 'danger' : ($dias_rest <= 7 ? 'warning' : 'info');
    $icon_banner  = $dias_rest <= 3 ? 'exclamation-triangle-fill' : 'clock-fill';
?>
<div class="alert alert-<?= $clase_banner ?> alert-dismissible fade show mb-0 py-2 px-3 rounded-0 text-center small" role="alert">
    <i class="bi bi-<?= $icon_banner ?>"></i>
    <strong>Período de prueba:</strong>
    <?php if ($dias_rest <= 1): ?>
        Hoy es el último día.
    <?php else: ?>
        te quedan <strong><?= $dias_rest ?></strong> días gratuitos.
    <?php endif; ?>
    <a href="expirado.php" class="alert-link ms-2 fw-bold">Activar licencia →</a>
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php
endif;
?>
<div class="mb-4"></div>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(() => {});
}
</script>
