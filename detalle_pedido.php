<?php
/*
 * detalle_pedido.php
 *
 * Muestra el detalle completo de un pedido guardado en la BD.
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

include('app.php');

$id_pedido = intval($_GET['id']   ?? 0);
$id_mesa   = intval($_GET['mesa'] ?? 0);

if ($id_pedido <= 0) {
    header("Location: principal.php");
    exit;
}

// ── Datos del pedido ──────────────────────────────────────
$stmt = $conexion->prepare("
    SELECT
        p.id_pedido, p.descr_pedido, p.estado_pedido,
        p.total_pedido, p.fecha_pedido, p.id_mesa, p.id_mozo,
        mo.nom_mozo, mo.ape_mozo,
        c.nom_cajero, c.ape_cajero,
        m.zona
    FROM pedido p
    LEFT JOIN mozo   mo ON p.id_mozo   = mo.id_mozo
    LEFT JOIN cajero c  ON p.id_cajero = c.id_cajero
    LEFT JOIN mesa   m  ON p.id_mesa   = m.id_mesa
    WHERE p.id_pedido = ?
");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    header("Location: principal.php");
    exit;
}

// ── Ítems del pedido (platos, bebidas, postres) ───────────
function getDetalle(mysqli $db, string $tabla, string $tabla_prod, string $col_nombre, string $col_id, int $id_pedido): array {
    $stmt = $db->prepare("
        SELECT p.{$col_nombre} AS nombre, d.cantidad, d.precio_unitario, d.subtotal,
               d.observacion
        FROM {$tabla} d
        INNER JOIN {$tabla_prod} p ON d.{$col_id} = p.{$col_id}
        WHERE d.id_pedido = ?
    ");
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $resultado;
}

$platos  = getDetalle($conexion, 'detalle_plato',  'plato',  'nom_plato',  'id_plato',  $id_pedido);
$bebidas = getDetalle($conexion, 'detalle_bebida', 'bebida', 'nom_bebida', 'id_bebida', $id_pedido);
$postres = getDetalle($conexion, 'detalle_postre', 'postre', 'nom_postre', 'id_postre', $id_pedido);

// ── Tiempo transcurrido ───────────────────────────────────
$diff    = (new DateTime())->diff(new DateTime($pedido['fecha_pedido']));
$tiempo  = $diff->h > 0 ? $diff->h . 'h ' . $diff->i . 'min' : $diff->i . ' min';
$zona    = $pedido['zona'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido #<?= $id_pedido ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>@media print { .no-print { display: none; } }</style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container" style="max-width: 760px;">

    <!-- ── Encabezado ── -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="mb-1">
                        <i class="bi bi-table"></i> Mesa <?= $pedido['id_mesa'] ?>
                        <span class="text-muted fw-normal">(<?= $zona ?>)</span>
                    </h4>
                    <p class="text-muted mb-1">
                        <i class="bi bi-clock"></i>
                        <?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?>
                        — hace <?= $tiempo ?>
                    </p>
                    <p class="text-muted mb-0">
                        <i class="bi bi-cash-register"></i>
                        <?= htmlspecialchars($pedido['nom_cajero'] . ' ' . $pedido['ape_cajero']) ?>
                        <?php if ($pedido['id_mozo'] !== null): ?>
                            &nbsp;|&nbsp;
                            <i class="bi bi-person-badge"></i>
                            <?= htmlspecialchars($pedido['nom_mozo'] . ' ' . $pedido['ape_mozo']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                    <?php if ($pedido['estado_pedido'] === 'En Proceso'): ?>
                        <span class="badge bg-warning text-dark fs-6">
                            <i class="bi bi-hourglass-split"></i> En Proceso
                        </span>
                    <?php elseif ($pedido['estado_pedido'] === 'Listo'): ?>
                        <span class="badge bg-info fs-6">
                            <i class="bi bi-bell"></i> Listo para servir
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success fs-6">
                            <i class="bi bi-check-circle"></i> Completado
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($pedido['descr_pedido'])): ?>
            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-chat-left-text"></i>
                <strong>Observación:</strong> <?= htmlspecialchars($pedido['descr_pedido']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Tabla de ítems ── -->
    <div class="card mb-4">
        <div class="card-header fw-bold">
            <i class="bi bi-list-ul"></i> Detalle del pedido
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Observación</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-end">P. Unit.</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $secciones = [
                    ['items' => $platos,  'badge' => 'danger',  'label' => 'Plato'],
                    ['items' => $bebidas, 'badge' => 'info',    'label' => 'Bebida'],
                    ['items' => $postres, 'badge' => 'warning', 'label' => 'Postre'],
                ];
                foreach ($secciones as $seccion):
                    foreach ($seccion['items'] as $item):
                ?>
                    <tr>
                        <td><span class="badge bg-<?= $seccion['badge'] ?> text-<?= $seccion['badge'] === 'warning' ? 'dark' : 'white' ?>"><?= $seccion['label'] ?></span></td>
                        <td><?= htmlspecialchars($item['nombre']) ?></td>
                        <td class="text-muted small fst-italic">
                            <?= !empty($item['observacion']) ? htmlspecialchars($item['observacion']) : '—' ?>
                        </td>
                        <td class="text-center"><?= $item['cantidad'] ?></td>
                        <td class="text-end">$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></td>
                        <td class="text-end fw-semibold">$<?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="4" class="text-end fw-bold">Total</td>
                        <td class="text-end fw-bold text-success fs-5">
                            $<?= number_format($pedido['total_pedido'], 0, ',', '.') ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ── Acciones ── -->
    <div class="d-flex flex-wrap gap-2 mb-5 no-print">
        <a href="principal.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>

        <?php if ($pedido['estado_pedido'] === 'En Proceso'): ?>
            <!-- Pedido en cocina: se puede agregar ítems o completar/cobrar -->
            <a href="limpiar_y_agregar.php?id_pedido=<?= $id_pedido ?>&mesa=<?= $pedido['id_mesa'] ?>&zona=<?= urlencode($zona) ?>"
               class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Agregar ítems
            </a>
            <button class="btn btn-warning"
                    onclick="if(confirm('¿Completar pedido y liberar la mesa?')) location.href='liberar_mesa.php?mesa=<?= $pedido['id_mesa'] ?>&pedido=<?= $id_pedido ?>'">
                <i class="bi bi-check-circle"></i> Completar pedido
            </button>
            <button class="btn btn-outline-danger"
                    onclick="if(confirm('¿Cancelar pedido? La mesa quedará libre y el pedido NO aparecerá en ventas.')) location.href='cancelar_pedido.php?id=<?= $id_pedido ?>&mesa=<?= $pedido['id_mesa'] ?>'">
                <i class="bi bi-x-circle"></i> Cancelar pedido
            </button>

        <?php elseif ($pedido['estado_pedido'] === 'Listo'): ?>
            <!-- Cocina terminó — el mozo sirve y cobra -->
            <span class="btn btn-success disabled">
                <i class="bi bi-bell-fill"></i> Listo para servir
            </span>
            <button class="btn btn-warning"
                    onclick="if(confirm('¿Servir, cobrar y liberar la mesa?')) location.href='liberar_mesa.php?mesa=<?= $pedido['id_mesa'] ?>&pedido=<?= $id_pedido ?>'">
                <i class="bi bi-cash-coin"></i> Cobrar y liberar mesa
            </button>
            <button class="btn btn-outline-danger"
                    onclick="if(confirm('¿Cancelar pedido? La mesa quedará libre y el pedido NO aparecerá en ventas.')) location.href='cancelar_pedido.php?id=<?= $id_pedido ?>&mesa=<?= $pedido['id_mesa'] ?>'">
                <i class="bi bi-x-circle"></i> Cancelar pedido
            </button>
        <?php endif; ?>

        <button class="btn btn-outline-info ms-auto" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conexion->close(); ?>
