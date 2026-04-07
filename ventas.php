<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}
if ($_SESSION['rol'] !== 'cajero') {
    header("Location: principal.php");
    exit;
}

include('app.php');

// ============================================================
// PERÍODO ACTIVO (hoy / semana / mes)
// ============================================================
$periodo = $_GET['periodo'] ?? 'hoy';
$periodos_validos = ['hoy', 'semana', 'mes'];
if (!in_array($periodo, $periodos_validos)) $periodo = 'hoy';

switch ($periodo) {
    case 'semana':
        $fecha_desde = date('Y-m-d', strtotime('monday this week'));
        $fecha_hasta = date('Y-m-d', strtotime('sunday this week'));
        $label_periodo = 'Esta semana';
        break;
    case 'mes':
        $fecha_desde = date('Y-m-01');
        $fecha_hasta = date('Y-m-t');
        $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $label_periodo = 'Este mes (' . $meses[(int)date('n') - 1] . ')';
        break;
    default: // hoy
        $fecha_desde = date('Y-m-d');
        $fecha_hasta = date('Y-m-d');
        $label_periodo = 'Hoy (' . date('d/m/Y') . ')';
}

// ============================================================
// QUERIES
// ============================================================

// ── Total recaudado y cantidad de pedidos ──────────────────
$stmt = $conexion->prepare("
    SELECT
        COUNT(*)            AS total_pedidos,
        COALESCE(SUM(total_pedido), 0) AS total_recaudado,
        COALESCE(AVG(total_pedido), 0) AS ticket_promedio,
        COALESCE(MAX(total_pedido), 0) AS ticket_maximo
    FROM pedido
    WHERE estado_pedido = 'Completado'
      AND DATE(fecha_pedido) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── Pedidos completados con detalle ───────────────────────
$stmt = $conexion->prepare("
    SELECT
        p.id_pedido,
        p.id_mesa,
        p.total_pedido,
        p.fecha_pedido,
        c.nom_cajero, c.ape_cajero,
        mo.nom_mozo,  mo.ape_mozo,
        m.zona
    FROM pedido p
    LEFT JOIN cajero c  ON p.id_cajero = c.id_cajero
    LEFT JOIN mozo   mo ON p.id_mozo   = mo.id_mozo
    LEFT JOIN mesa   m  ON p.id_mesa   = m.id_mesa
    WHERE p.estado_pedido = 'Completado'
      AND DATE(p.fecha_pedido) BETWEEN ? AND ?
    ORDER BY p.fecha_pedido DESC
");
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Productos más vendidos (platos) ───────────────────────
$stmt = $conexion->prepare("
    SELECT pl.nom_plato AS nombre, SUM(dp.cantidad) AS total_vendido,
           SUM(dp.subtotal) AS total_recaudado, 'Plato' AS tipo
    FROM detalle_plato dp
    INNER JOIN plato pl ON dp.id_plato = pl.id_plato
    INNER JOIN pedido p  ON dp.id_pedido = p.id_pedido
    WHERE p.estado_pedido = 'Completado'
      AND DATE(p.fecha_pedido) BETWEEN ? AND ?
    GROUP BY pl.id_plato, pl.nom_plato
    ORDER BY total_vendido DESC
    LIMIT 10
");
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$top_platos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Productos más vendidos (bebidas) ──────────────────────
$stmt = $conexion->prepare("
    SELECT b.nom_bebida AS nombre, SUM(db2.cantidad) AS total_vendido,
           SUM(db2.subtotal) AS total_recaudado, 'Bebida' AS tipo
    FROM detalle_bebida db2
    INNER JOIN bebida b ON db2.id_bebida = b.id_bebida
    INNER JOIN pedido p ON db2.id_pedido = p.id_pedido
    WHERE p.estado_pedido = 'Completado'
      AND DATE(p.fecha_pedido) BETWEEN ? AND ?
    GROUP BY b.id_bebida, b.nom_bebida
    ORDER BY total_vendido DESC
    LIMIT 10
");
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$top_bebidas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Productos más vendidos (postres) ──────────────────────
$stmt = $conexion->prepare("
    SELECT po.nom_postre AS nombre, SUM(dpo.cantidad) AS total_vendido,
           SUM(dpo.subtotal) AS total_recaudado, 'Postre' AS tipo
    FROM detalle_postre dpo
    INNER JOIN postre po ON dpo.id_postre = po.id_postre
    INNER JOIN pedido p  ON dpo.id_pedido = p.id_pedido
    WHERE p.estado_pedido = 'Completado'
      AND DATE(p.fecha_pedido) BETWEEN ? AND ?
    GROUP BY po.id_postre, po.nom_postre
    ORDER BY total_vendido DESC
    LIMIT 10
");
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$top_postres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Unir y ordenar todos los productos por cantidad vendida
$top_productos = array_merge($top_platos, $top_bebidas, $top_postres);
usort($top_productos, function($a, $b) {
    return $b['total_vendido'] - $a['total_vendido'];
});
$top_productos = array_slice($top_productos, 0, 10);
$max_vendido   = $top_productos[0]['total_vendido'] ?? 1;

// ── Ventas por cajero ─────────────────────────────────────
$stmt = $conexion->prepare("
    SELECT
        CONCAT(c.nom_cajero, ' ', c.ape_cajero) AS nombre,
        COUNT(p.id_pedido)                       AS total_pedidos,
        COALESCE(SUM(p.total_pedido), 0)         AS total_recaudado
    FROM pedido p
    INNER JOIN cajero c ON p.id_cajero = c.id_cajero
    WHERE p.estado_pedido = 'Completado'
      AND DATE(p.fecha_pedido) BETWEEN ? AND ?
    GROUP BY c.id_cajero
    ORDER BY total_recaudado DESC
");
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$por_cajero = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Ventas por mozo (solo pedidos que tienen mozo) ────────
$stmt = $conexion->prepare("
    SELECT
        CONCAT(mo.nom_mozo, ' ', mo.ape_mozo) AS nombre,
        COUNT(p.id_pedido)                     AS total_pedidos,
        COALESCE(SUM(p.total_pedido), 0)       AS total_recaudado
    FROM pedido p
    INNER JOIN mozo mo ON p.id_mozo = mo.id_mozo
    WHERE p.estado_pedido = 'Completado'
      AND DATE(p.fecha_pedido) BETWEEN ? AND ?
    GROUP BY mo.id_mozo
    ORDER BY total_recaudado DESC
");
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$por_mozo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Exportar CSV ──────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $moneda_csv = $app['moneda'] ?? '$';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ventas_' . $periodo . '_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 para Excel
    fputcsv($out, ['#Pedido', 'Fecha', 'Mesa', 'Zona', 'Cajero', 'Mozo', 'Total'], ';');
    foreach ($pedidos as $p) {
        fputcsv($out, [
            $p['id_pedido'],
            date('d/m/Y H:i', strtotime($p['fecha_pedido'])),
            'Mesa ' . $p['id_mesa'],
            $p['zona'] ?? '',
            $p['nom_cajero'] . ' ' . $p['ape_cajero'],
            !empty($p['nom_mozo']) ? $p['nom_mozo'] . ' ' . $p['ape_mozo'] : '',
            number_format($p['total_pedido'], 2, ',', '.'),
        ], ';');
    }
    fclose($out);
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas — <?= htmlspecialchars($app['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }

        /* ── Stat cards ── */
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: white; flex-shrink: 0;
        }
        .stat-label { font-size: 0.75rem; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 1.6rem; font-weight: 700; color: #222; line-height: 1.1; }

        /* ── Secciones ── */
        .seccion-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .seccion-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Barra de ranking ── */
        .ranking-bar-wrap {
            background: #f0f2f5;
            border-radius: 6px;
            height: 10px;
            overflow: hidden;
            flex-grow: 1;
        }
        .ranking-bar {
            height: 100%;
            border-radius: 6px;
            transition: width 0.6s ease;
        }

        /* ── Pestañas de período ── */
        .periodo-tabs .btn {
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.88rem;
        }

        /* ── Tabla de pedidos ── */
        .table th { font-size: 0.8rem; text-transform: uppercase; color: #888; letter-spacing: 0.4px; }

        /* ── Print ── */
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .seccion-card { box-shadow: none; border: 1px solid #ddd; }
            .stat-card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container-fluid px-4 pb-5">

    <!-- ── Encabezado + pestañas ── -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line"></i> Reporte de Ventas</h4>
            <p class="text-muted small mb-0"><?= $label_periodo ?></p>
        </div>
        <div class="d-flex gap-2 periodo-tabs">
            <a href="?periodo=hoy"
               class="btn <?= $periodo === 'hoy'    ? 'btn-primary'         : 'btn-outline-secondary' ?>">
                <i class="bi bi-sun"></i> Hoy
            </a>
            <a href="?periodo=semana"
               class="btn <?= $periodo === 'semana' ? 'btn-primary'         : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar-week"></i> Semana
            </a>
            <a href="?periodo=mes"
               class="btn <?= $periodo === 'mes'    ? 'btn-primary'         : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar-month"></i> Mes
            </a>
            <button class="btn btn-outline-dark ms-2" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Título solo para impresión -->
    <div class="d-none d-print-block mb-4">
        <h3 class="fw-bold"><?= htmlspecialchars($app['nombre']) ?> — Reporte de Ventas</h3>
        <p><?= $label_periodo ?> &nbsp;·&nbsp; Generado el <?= date('d/m/Y H:i') ?></p>
        <hr>
    </div>

    <!-- ── Stat cards ── -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-success"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <p class="stat-label mb-0">Total recaudado</p>
                    <p class="stat-value mb-0">$<?= number_format($resumen['total_recaudado'], 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary"><i class="bi bi-receipt"></i></div>
                <div>
                    <p class="stat-label mb-0">Pedidos cerrados</p>
                    <p class="stat-value mb-0"><?= $resumen['total_pedidos'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning"><i class="bi bi-graph-up"></i></div>
                <div>
                    <p class="stat-label mb-0">Compra promedio</p>
                    <p class="stat-value mb-0">$<?= number_format($resumen['ticket_promedio'], 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger"><i class="bi bi-trophy"></i></div>
                <div>
                    <p class="stat-label mb-0">Compra maxima</p>
                    <p class="stat-value mb-0">$<?= number_format($resumen['ticket_maximo'], 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- ── Productos más vendidos ── -->
        <div class="col-lg-6">
            <div class="seccion-card h-100">
                <div class="seccion-header">
                    <i class="bi bi-star-fill text-warning"></i> Productos más vendidos
                </div>
                <div class="p-3">
                    <?php if (empty($top_productos)): ?>
                        <p class="text-muted text-center py-4">Sin datos para este período.</p>
                    <?php else: ?>
                        <?php foreach ($top_productos as $i => $prod):
                            $pct   = round(($prod['total_vendido'] / $max_vendido) * 100);
                            $colores = ['Plato' => '#e63946', 'Bebida' => '#457b9d', 'Postre' => '#6a4c93'];
                            $badges  = ['Plato' => 'danger',  'Bebida' => 'info',    'Postre' => 'secondary'];
                            $color = $colores[$prod['tipo']] ?? '#888';
                            $badge = $badges[$prod['tipo']]  ?? 'dark';
                        ?>
                        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                            <span class="fw-bold text-muted" style="width:22px;font-size:0.85rem;">#<?= $i+1 ?></span>
                            <div style="flex-grow:1;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold" style="font-size:0.9rem;">
                                        <?= htmlspecialchars($prod['nombre']) ?>
                                        <span class="badge bg-<?= $badge ?> ms-1" style="font-size:0.65rem;"><?= $prod['tipo'] ?></span>
                                    </span>
                                    <span class="text-muted small"><?= $prod['total_vendido'] ?> uds.</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ranking-bar-wrap">
                                        <div class="ranking-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                                    </div>
                                    <span class="text-muted small" style="width:80px;text-align:right;">
                                        $<?= number_format($prod['total_recaudado'], 0, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Ventas por cajero y mozo ── -->
        <div class="col-lg-6">
            <div class="seccion-card mb-4">
                <div class="seccion-header">
                    <i class="bi bi-cash-register text-primary"></i> Ventas por cajero
                </div>
                <div class="p-3">
                    <?php if (empty($por_cajero)): ?>
                        <p class="text-muted text-center py-3">Sin datos.</p>
                    <?php else: ?>
                        <?php
                        $max_caj = max(array_column($por_cajero, 'total_recaudado')) ?: 1;
                        foreach ($por_cajero as $caj):
                            $pct = round(($caj['total_recaudado'] / $max_caj) * 100);
                        ?>
                        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                            <i class="bi bi-person-circle fs-4 text-primary"></i>
                            <div style="flex-grow:1;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-semibold" style="font-size:0.9rem;"><?= htmlspecialchars($caj['nombre']) ?></span>
                                    <span class="text-muted small"><?= $caj['total_pedidos'] ?> pedidos</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ranking-bar-wrap">
                                        <div class="ranking-bar" style="width:<?= $pct ?>%;background:#0d6efd;"></div>
                                    </div>
                                    <span class="fw-semibold text-primary small" style="width:90px;text-align:right;">
                                        $<?= number_format($caj['total_recaudado'], 0, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($por_mozo)): ?>
            <div class="seccion-card">
                <div class="seccion-header">
                    <i class="bi bi-person-badge text-success"></i> Ventas por mozo
                </div>
                <div class="p-3">
                    <?php
                    $max_moz = max(array_column($por_mozo, 'total_recaudado')) ?: 1;
                    foreach ($por_mozo as $moz):
                        $pct = round(($moz['total_recaudado'] / $max_moz) * 100);
                    ?>
                    <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                        <i class="bi bi-person-circle fs-4 text-success"></i>
                        <div style="flex-grow:1;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold" style="font-size:0.9rem;"><?= htmlspecialchars($moz['nombre']) ?></span>
                                <span class="text-muted small"><?= $moz['total_pedidos'] ?> pedidos</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="ranking-bar-wrap">
                                    <div class="ranking-bar" style="width:<?= $pct ?>%;background:#198754;"></div>
                                </div>
                                <span class="fw-semibold text-success small" style="width:90px;text-align:right;">
                                    $<?= number_format($moz['total_recaudado'], 0, ',', '.') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Listado de pedidos del período ── -->
        <div class="col-12">
            <div class="seccion-card">
                <div class="seccion-header d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-list-ul text-secondary"></i>
                    Pedidos completados
                    <span class="badge bg-secondary"><?= count($pedidos) ?></span>
                    <?php if (!empty($pedidos)): ?>
                    <a href="?periodo=<?= $periodo ?>&export=csv"
                       class="btn btn-sm btn-outline-success ms-auto no-print">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Exportar CSV
                    </a>
                    <?php endif; ?>
                </div>
                <?php if (empty($pedidos)): ?>
                    <p class="text-muted text-center py-5">No hay pedidos completados en este período.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Mesa</th>
                                <th>Fecha y hora</th>
                                <th>Cajero</th>
                                <th>Mozo</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pedidos as $p):
                            $zona = $p['zona'] ?? '';
                        ?>
                            <tr>
                                <td class="text-muted small">#<?= $p['id_pedido'] ?></td>
                                <td>
                                    <span class="fw-semibold">Mesa <?= $p['id_mesa'] ?></span>
                                    <span class="text-muted small ms-1">(<?= $zona ?>)</span>
                                </td>
                                <td class="small"><?= date('d/m/Y H:i', strtotime($p['fecha_pedido'])) ?></td>
                                <td><?= htmlspecialchars($p['nom_cajero'] . ' ' . $p['ape_cajero']) ?></td>
                                <td class="text-muted small">
                                    <?= !empty($p['nom_mozo']) ? htmlspecialchars($p['nom_mozo'] . ' ' . $p['ape_mozo']) : '—' ?>
                                </td>
                                <td class="text-end fw-semibold text-success">
                                    $<?= number_format($p['total_pedido'], 0, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="5" class="text-end fw-bold">Total del período</td>
                                <td class="text-end fw-bold text-success fs-5">
                                    $<?= number_format($resumen['total_recaudado'], 0, ',', '.') ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
