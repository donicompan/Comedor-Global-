<?php
/*
 * cuenta.php
 *
 * Muestra un resumen del carrito actual a modo de cuenta previa,
 * antes de que el pedido sea guardado en la base de datos.
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$id_mesa = intval($_GET['mesa'] ?? 0);
$zona    = htmlspecialchars($_GET['zona'] ?? '');
$items   = $_SESSION['pedido_items'] ?? [];

// Agrupar ítems para mostrarlos de forma ordenada
// Ítems con distinta observación se muestran por separado
$agrupado = [];
foreach ($items as $item) {
    $key = $item['tipo'] . '_' . $item['id'] . '_' . ($item['observacion'] ?? '');
    if (!isset($agrupado[$key])) {
        $agrupado[$key] = [
            'nombre'      => $item['nombre'],
            'precio'      => $item['precio'],
            'cantidad'    => 1,
            'subtotal'    => $item['precio'],
            'observacion' => $item['observacion'] ?? '',
        ];
    } else {
        $agrupado[$key]['cantidad']++;
        $agrupado[$key]['subtotal'] += $item['precio'];
    }
}

$total = array_sum(array_column($agrupado, 'subtotal'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuenta — Mesa <?= $id_mesa ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container" style="max-width: 600px;">

    <h4 class="mb-1">
        <i class="bi bi-receipt"></i> Cuenta — Mesa <?= $id_mesa ?>
    </h4>
    <p class="text-muted mb-4"><?= htmlspecialchars($zona) ?></p>

    <?php if (empty($agrupado)): ?>
        <div class="alert alert-info">No hay productos cargados.</div>

    <?php else: ?>
        <div class="card mb-4">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">P. Unit.</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($agrupado as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nombre']) ?></td>
                            <td class="text-center"><?= $item['cantidad'] ?></td>
                            <td class="text-end">$<?= number_format($item['precio'], 0, ',', '.') ?></td>
                            <td class="text-end fw-semibold">$<?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold text-success fs-5">
                                $<?= number_format($total, 0, ',', '.') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <a href="nuevo_pedido.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>"
       class="btn btn-success">
        <i class="bi bi-arrow-left"></i> Volver al pedido
    </a>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
