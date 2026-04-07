<?php
/*
 * cocina.php — Pantalla de cocina para Smart TV
 *
 * No requiere login — pensada para estar siempre abierta en el TV.
 * Muestra todos los pedidos 'En Proceso' ordenados del más viejo al más nuevo.
 * Se refresca automáticamente cada 30 segundos.
 */
include('app.php');

// Pedidos activos, del más viejo al más nuevo
$pedidos = $conexion->query("
    SELECT
        p.id_pedido,
        p.id_mesa,
        p.descr_pedido,
        p.fecha_pedido,
        m.zona,
        TIMESTAMPDIFF(MINUTE, p.fecha_pedido, NOW()) AS minutos_espera
    FROM pedido p
    INNER JOIN mesa m ON p.id_mesa = m.id_mesa
    WHERE p.estado_pedido = 'En Proceso'
    ORDER BY p.fecha_pedido ASC
")->fetch_all(MYSQLI_ASSOC);

// Traer ítems de un pedido con sus observaciones desde la BD
function getItems(mysqli $db, int $id_pedido): array {
    $items = [];

    $stmt = $db->prepare("
        SELECT pl.nom_plato AS nombre, dp.cantidad, dp.observacion, 'plato' AS tipo
        FROM detalle_plato dp
        INNER JOIN plato pl ON dp.id_plato = pl.id_plato
        WHERE dp.id_pedido = ?
        ORDER BY dp.id_detalle_plato ASC
    ");
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $items = array_merge($items, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();

    $stmt = $db->prepare("
        SELECT b.nom_bebida AS nombre, db2.cantidad, db2.observacion, 'bebida' AS tipo
        FROM detalle_bebida db2
        INNER JOIN bebida b ON db2.id_bebida = b.id_bebida
        WHERE db2.id_pedido = ?
        ORDER BY db2.id_detalle_bebida ASC
    ");
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $items = array_merge($items, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();

    $stmt = $db->prepare("
        SELECT po.nom_postre AS nombre, dpo.cantidad, dpo.observacion, 'postre' AS tipo
        FROM detalle_postre dpo
        INNER JOIN postre po ON dpo.id_postre = po.id_postre
        WHERE dpo.id_pedido = ?
        ORDER BY dpo.id_detalle_postre ASC
    ");
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $items = array_merge($items, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();

    return $items;
}

foreach ($pedidos as &$pedido) {
    $pedido['items'] = getItems($conexion, $pedido['id_pedido']);
}
unset($pedido);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cocina — <?= htmlspecialchars($app['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #1a1a2e;
            font-family: 'Poppins', sans-serif;
            color: white;
            min-height: 100vh;
            padding-bottom: 50px;
        }

        /* ── Header ──────────────────────────────────────────── */
        .cocina-header {
            background: #16213e;
            padding: 16px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #e63946;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .cocina-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
        }

        .reloj {
            font-size: 1.5rem;
            font-weight: 700;
            color: #a8dadc;
            font-variant-numeric: tabular-nums;
            letter-spacing: 2px;
        }

        /* ── Sin pedidos ─────────────────────────────────────── */
        .sin-pedidos {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: calc(100vh - 100px);
            color: #4a4e69;
        }

        .sin-pedidos i   { font-size: 5rem; margin-bottom: 16px; }
        .sin-pedidos p   { font-size: 1.2rem; }

        /* ── Grid ────────────────────────────────────────────── */
        .pedidos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 24px;
        }

        /* ── Tarjeta de pedido ───────────────────────────────── */
        .pedido-card {
            background: #16213e;
            border-radius: 16px;
            border: 2px solid #0f3460;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: entrada 0.4s ease;
        }

        @keyframes entrada {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Color del borde según urgencia */
        .pedido-card.normal      { border-color: #2a9d8f; }
        .pedido-card.advertencia { border-color: #f4a261; }
        .pedido-card.urgente     { border-color: #e63946; }

        /* ── Header de la tarjeta ────────────────────────────── */
        .pedido-header {
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pedido-header.normal      { background: #2a9d8f; }
        .pedido-header.advertencia { background: #f4a261; color: #1a1a2e; }
        .pedido-header.urgente     { background: #e63946; }

        .mesa-numero  { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .mesa-zona    { font-size: 0.75rem; opacity: 0.85; }
        .tiempo-badge { font-size: 1rem; font-weight: 700; }
        .hora-entrada { font-size: 0.72rem; opacity: 0.8; margin-top: 2px; }

        /* ── Observación general ─────────────────────────────── */
        .obs-general {
            margin: 12px 18px 0;
            padding: 8px 12px;
            background: rgba(244, 162, 97, 0.15);
            border-left: 3px solid #f4a261;
            border-radius: 0 8px 8px 0;
            font-size: 0.82rem;
            color: #f4a261;
        }

        /* ── Lista de ítems ──────────────────────────────────── */
        .items-lista {
            padding: 14px 18px;
            flex-grow: 1;
        }

        .item-fila {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .item-fila:last-child { border-bottom: none; }

        .item-cantidad {
            min-width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .item-cantidad.plato  { background: #e63946; }
        .item-cantidad.bebida { background: #457b9d; }
        .item-cantidad.postre { background: #6a4c93; }

        .item-nombre {
            font-size: 1rem;
            font-weight: 600;
            color: #f1faee;
            line-height: 1.3;
        }

        /* Observación por ítem — destacada en naranja */
        .item-obs {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 8px;
            background: rgba(244, 162, 97, 0.2);
            border: 1px solid rgba(244, 162, 97, 0.4);
            border-radius: 4px;
            font-size: 0.78rem;
            color: #f4a261;
            font-style: italic;
        }

        /* ── Botón listo ─────────────────────────────────────── */
        .pedido-footer {
            padding: 12px 18px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .btn-listo {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 10px;
            background: #2a9d8f;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-listo:hover  { background: #21867a; }
        .btn-listo:active { transform: scale(0.98); }

        /* ── Barra inferior de refresco ──────────────────────── */
        .refresh-bar {
            position: fixed;
            bottom: 0; left: 0;
            width: 100%;
            background: #0f3460;
            padding: 6px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: #4a4e69;
        }

        .progress-wrap {
            width: 180px;
            height: 4px;
            background: #16213e;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-bar-anim {
            height: 100%;
            background: #2a9d8f;
            animation: progreso 30s linear forwards;
        }

        @keyframes progreso {
            from { width: 0; }
            to   { width: 100%; }
        }
    </style>
</head>
<body>

<!-- ── Header ── -->
<div class="cocina-header">
    <h1>
        <i class="bi bi-fire text-danger"></i>
        Cocina — <?= htmlspecialchars($app['nombre']) ?>
        <span class="fs-5 fw-normal text-white-50 ms-3">
            <?= count($pedidos) ?> pedido<?= count($pedidos) !== 1 ? 's' : '' ?> activo<?= count($pedidos) !== 1 ? 's' : '' ?>
        </span>
    </h1>
    <span class="reloj" id="reloj">--:--:--</span>
</div>

<!-- ── Contenido principal ── -->
<?php if (empty($pedidos)): ?>

    <div class="sin-pedidos">
        <i class="bi bi-check-circle-fill text-success"></i>
        <p>Todo al día — sin pedidos pendientes</p>
    </div>

<?php else: ?>

    <div class="pedidos-grid">
        <?php foreach ($pedidos as $pedido):
            $min    = intval($pedido['minutos_espera']);
            $estado = $min >= 20 ? 'urgente' : ($min >= 10 ? 'advertencia' : 'normal');
            $zona   = $pedido['zona'];
            $tiempo = $min >= 60
                        ? floor($min / 60) . 'h ' . ($min % 60) . 'min'
                        : $min . ' min';
        ?>
        <div class="pedido-card <?= $estado ?>">

            <!-- Encabezado -->
            <div class="pedido-header <?= $estado ?>">
                <div>
                    <div class="mesa-numero">Mesa <?= $pedido['id_mesa'] ?></div>
                    <div class="mesa-zona"><?= $zona ?></div>
                </div>
                <div class="text-end">
                    <div class="tiempo-badge">
                        <i class="bi bi-clock"></i> <?= $tiempo ?>
                    </div>
                    <div class="hora-entrada">
                        Entró: <?= date('H:i', strtotime($pedido['fecha_pedido'])) ?>
                    </div>
                </div>
            </div>

            <!-- Observación general del pedido -->
            <?php if (!empty($pedido['descr_pedido'])): ?>
            <div class="obs-general">
                <i class="bi bi-chat-left-text"></i>
                <?= htmlspecialchars($pedido['descr_pedido']) ?>
            </div>
            <?php endif; ?>

            <!-- Lista de ítems -->
            <div class="items-lista">
                <?php if (empty($pedido['items'])): ?>
                    <p class="text-muted small">Sin ítems</p>
                <?php else: ?>
                    <?php foreach ($pedido['items'] as $item): ?>
                    <div class="item-fila">
                        <div class="item-cantidad <?= $item['tipo'] ?>">
                            <?= $item['cantidad'] ?>
                        </div>
                        <div>
                            <div class="item-nombre">
                                <?= htmlspecialchars($item['nombre']) ?>
                            </div>
                            <?php if (!empty($item['observacion'])): ?>
                            <span class="item-obs">
                                <i class="bi bi-exclamation-circle"></i>
                                <?= htmlspecialchars($item['observacion']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            
            <!-- Botón marcar listo -->
            <div class="pedido-footer">
                <form method="POST" action="marcar_listo.php">
                    <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                    <button type="submit" class="btn-listo">
                        <i class="bi bi-check-lg"></i> ¡Listo!
                    </button>
                </form>
            </div>

        </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<!-- ── Barra de refresco ── -->
<div class="refresh-bar">
    <span><i class="bi bi-arrow-clockwise"></i> Actualización automática cada 30 seg</span>
    <div class="progress-wrap">
        <div class="progress-bar-anim"></div>
    </div>
</div>

<script>
// Reloj en tiempo real
function tick() {
    const d  = new Date();
    const hh = String(d.getHours()).padStart(2,'0');
    const mm = String(d.getMinutes()).padStart(2,'0');
    const ss = String(d.getSeconds()).padStart(2,'0');
    document.getElementById('reloj').textContent = `${hh}:${mm}:${ss}`;
}
tick();
setInterval(tick, 1000);

// Refresco automático — siempre sobre la URL limpia
setTimeout(() => location.reload(), 30000);
</script>

</body>
</html>
