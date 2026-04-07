<?php
/*
 * modificar_item.php
 *
 * Permite editar la observación de un ítem individual del carrito.
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$i       = intval($_GET['i'] ?? -1);
$id_mesa = intval($_GET['mesa'] ?? 0);
$zona    = htmlspecialchars($_GET['zona'] ?? '');

// Verificar que el índice existe
if ($i < 0 || !isset($_SESSION['pedido_items'][$i])) {
    header("Location: modificar_pedido.php?mesa=$id_mesa&zona=" . urlencode($zona));
    exit;
}

// POST: guardar la observación editada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['pedido_items'][$i]['observacion'] = trim($_POST['observacion'] ?? '');
    header("Location: modificar_pedido.php?mesa=$id_mesa&zona=" . urlencode($zona));
    exit;
}

$item = $_SESSION['pedido_items'][$i];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar ítem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container" style="max-width: 480px;">

    <h4 class="mb-4">
        <i class="bi bi-pencil"></i> Editar observación
    </h4>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-1 text-muted small">Producto</p>
            <p class="fw-bold mb-0"><?= htmlspecialchars($item['nombre']) ?></p>
        </div>
    </div>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Observación</label>
            <textarea name="observacion" class="form-control" rows="3"
                      placeholder="Ej: sin sal, bien cocido..."><?= htmlspecialchars($item['observacion'] ?? '') ?></textarea>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-lg"></i> Guardar
            </button>
            <a href="modificar_pedido.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>"
               class="btn btn-outline-secondary">
                Cancelar
            </a>
        </div>
    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
