<?php
/*
 * modificar_pedido.php
 *
 * Lista todos los ítems del carrito.
 * Permite eliminar unidades individuales o editar la observación de cada una.
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$id_mesa = intval($_GET['mesa'] ?? 0);
$zona    = htmlspecialchars($_GET['zona'] ?? '');

if (!isset($_SESSION['pedido_items'])) {
    $_SESSION['pedido_items'] = [];
}

// Eliminar un ítem por su índice
if (isset($_GET['del'])) {
    $i = intval($_GET['del']);
    if (isset($_SESSION['pedido_items'][$i])) {
        array_splice($_SESSION['pedido_items'], $i, 1);
    }
    header("Location: modificar_pedido.php?mesa=$id_mesa&zona=" . urlencode($zona));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Pedido — Mesa <?= $id_mesa ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container" style="max-width: 700px;">

    <h4 class="mb-4">
        <i class="bi bi-pencil-square"></i>
        Modificar pedido — Mesa <?= $id_mesa ?>
    </h4>

    <?php if (empty($_SESSION['pedido_items'])): ?>
        <div class="alert alert-info">No hay ítems en el carrito.</div>

    <?php else: ?>
        <div class="card">
            <div class="card-header fw-bold">Ítems actuales</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Observación</th>
                            <th class="text-end">Precio</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($_SESSION['pedido_items'] as $i => $item): ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <span class="badge bg-secondary me-1"><?= ucfirst($item['tipo']) ?></span>
                                <?= htmlspecialchars($item['nombre']) ?>
                            </td>
                            <td class="text-muted small">
                                <?= $item['observacion'] !== '' ? htmlspecialchars($item['observacion']) : '—' ?>
                            </td>
                            <td class="text-end">$<?= number_format($item['precio'], 0, ',', '.') ?></td>
                            <td class="text-end">
                                <a href="modificar_item.php?i=<?= $i ?>&mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>"
                                   class="btn btn-sm btn-outline-primary me-1" title="Editar observación">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="modificar_pedido.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>&del=<?= $i ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Eliminar"
                                   onclick="return confirm('¿Eliminar este ítem?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="nuevo_pedido.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>"
           class="btn btn-success">
            <i class="bi bi-check-lg"></i> Volver al pedido
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
