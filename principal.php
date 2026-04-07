<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

include('app.php');

// ============================================================
// ESTADÍSTICAS
// ============================================================

$total_mesas       = $conexion->query("SELECT COUNT(*) FROM mesa")->fetch_row()[0];
$mesas_disponibles = $conexion->query("SELECT COUNT(*) FROM mesa WHERE dispo_mesa = 'Disponible'")->fetch_row()[0];
$mesas_ocupadas    = $total_mesas - $mesas_disponibles;

$ventas_hoy = $conexion->query("
    SELECT COALESCE(SUM(total_pedido), 0)
    FROM pedido
    WHERE DATE(fecha_pedido) = CURDATE()
      AND estado_pedido = 'Completado'
")->fetch_row()[0];

// ============================================================
// MESAS CON SU PEDIDO ACTIVO
// GROUP BY garantiza una fila por mesa.
// Mostramos el cajero (quien tomó el pedido) en el panel.
// ============================================================

$resultado = $conexion->query("
    SELECT
        m.id_mesa,
        m.dispo_mesa,
        m.zona,
        MAX(p.id_pedido)                                  AS id_pedido,
        MAX(p.fecha_pedido)                               AS fecha_pedido,
        MAX(c.nom_cajero)                                 AS nom_cajero,
        MAX(c.ape_cajero)                                 AS ape_cajero,
        MAX(p.estado_pedido)                              AS estado_pedido,
        TIMESTAMPDIFF(MINUTE, MAX(p.fecha_pedido), NOW()) AS minutos_ocupada
    FROM mesa m
    LEFT JOIN pedido p
        ON m.id_mesa = p.id_mesa AND p.estado_pedido IN ('En Proceso', 'Listo')
    LEFT JOIN cajero c
        ON p.id_cajero = c.id_cajero
    GROUP BY m.id_mesa, m.dispo_mesa, m.zona
    ORDER BY m.zona, m.id_mesa
");

if (!$resultado) {
    die("Error en la consulta de mesas: " . $conexion->error);
}

// Agrupar mesas por zona dinámicamente
$zonas = [];
while ($mesa = $resultado->fetch_assoc()) {
    $zonas[$mesa['zona']][] = $mesa;
}

// Compatibilidad con código que espera $salon y $patio
$salon = $zonas[$app['zona1_nombre']] ?? [];
$patio = $zonas[$app['zona2_nombre']] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Mesas — <?= htmlspecialchars($app['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/estiloPrincipal.css" rel="stylesheet">
    <?php include 'pwa_head.php'; ?>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container-fluid px-4">

    <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3">
        <i class="bi bi-check-circle"></i> Mesa liberada correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3">
        <i class="bi bi-exclamation-triangle"></i> Ocurrió un error. Intentá de nuevo.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ── Estadísticas ── -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-success"><i class="bi bi-check-circle"></i></div>
                <div>
                    <p class="stat-label">Disponibles</p>
                    <h3 class="stat-value"><?= $mesas_disponibles ?></h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger"><i class="bi bi-x-circle"></i></div>
                <div>
                    <p class="stat-label">Ocupadas</p>
                    <h3 class="stat-value"><?= $mesas_ocupadas ?></h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary"><i class="bi bi-grid-3x3"></i></div>
                <div>
                    <p class="stat-label">Total Mesas</p>
                    <h3 class="stat-value"><?= $total_mesas ?></h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <p class="stat-label">Ventas Hoy</p>
                    <h3 class="stat-value">$<?= number_format($ventas_hoy, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Zonas ── -->
    <div class="row g-4">

        <?php foreach ($zonas as $nombre_zona => $mesas): ?>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-geo-alt-fill"></i>
                        <?= $nombre_zona ?>
                    </h5>
                    <span class="badge bg-secondary"><?= count($mesas) ?> mesas</span>
                </div>
                <div class="card-body">

                    <?php foreach ($mesas as $mesa): ?>

                        <?php if ($mesa['dispo_mesa'] === 'Ocupada'): ?>
                        <!-- Mesa ocupada -->
                        <div class="mesa-card mesa-ocupada">
                            <div class="d-flex align-items-center gap-3">
                                <span class="indicador indicador-rojo"></span>
                                <div>
                                    <strong class="text-danger">Mesa <?= $mesa['id_mesa'] ?></strong>
                                    <div class="text-muted small">
                                            <?php if ($mesa['id_pedido']): ?>
                                            <?php if (($mesa['estado_pedido'] ?? '') === 'Listo'): ?>
                                                <span class="text-success fw-bold">
                                                    <i class="bi bi-bell-fill"></i> ¡Listo para servir!
                                                </span>
                                            <?php else: ?>
                                                <i class="bi bi-clock"></i> <?= $mesa['minutos_ocupada'] ?> min —
                                                <i class="bi bi-cash-register"></i> <?= htmlspecialchars($mesa['nom_cajero'] . ' ' . $mesa['ape_cajero']) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <i class="bi bi-exclamation-circle text-warning"></i> Sin pedido activo
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($mesa['id_pedido']): ?>
                                <a href="detalle_pedido.php?id=<?= $mesa['id_pedido'] ?>&mesa=<?= $mesa['id_mesa'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <button class="btn btn-sm btn-outline-success"
                                        onclick="confirmarLiberar(<?= $mesa['id_mesa'] ?>, '<?= $nombre_zona ?>', <?= $mesa['id_pedido'] ?>)">
                                    <i class="bi bi-check"></i> Liberar
                                </button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-warning"
                                        onclick="if(confirm('¿Resetear Mesa <?= $mesa['id_mesa'] ?>?')) location.href='liberar_mesa.php?mesa=<?= $mesa['id_mesa'] ?>&pedido=0'">
                                    <i class="bi bi-arrow-counterclockwise"></i> Resetear
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php else: ?>
                        <!-- Mesa disponible -->
                        <div class="mesa-card mesa-disponible">
                            <div class="d-flex align-items-center gap-3">
                                <span class="indicador indicador-verde"></span>
                                <div>
                                    <strong class="text-success">Mesa <?= $mesa['id_mesa'] ?></strong>
                                    <div class="text-muted small">
                                        <i class="bi bi-check-circle"></i> Disponible
                                    </div>
                                </div>
                            </div>
                            <a href="seleccionar_cajero.php?mesa=<?= $mesa['id_mesa'] ?>&zona=<?= urlencode($nombre_zona) ?>"
                               class="btn btn-sm btn-success">
                                <i class="bi bi-plus-circle"></i> Asignar
                            </a>
                        </div>
                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmarLiberar(idMesa, zona, idPedido) {
    const ok = confirm(`¿Liberar Mesa ${idMesa} (${zona})?\nEsto marcará el pedido como completado.`);
    if (ok) {
        window.location.href = `liberar_mesa.php?mesa=${idMesa}&pedido=${idPedido}`;
    }
}

// Limpiar parámetros de la URL sin recargar
if (window.location.search.includes('ok=') || window.location.search.includes('error=')) {
    history.replaceState(null, '', 'principal.php');
}

// Refrescar cada 30 segundos sobre URL limpia
setTimeout(() => window.location.href = 'principal.php', 30000);
</script>

</body>
</html>
<?php $conexion->close(); ?>
