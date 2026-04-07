<?php
/*
 * descripcion.php
 *
 * Muestra los ítems seleccionados en carta.php.
 * Permite agregar una observación INDIVIDUAL por cada ítem.
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['items_temp'])) {
    header("Location: carta.php");
    exit;
}

include('app.php');

$id_mesa = $_SESSION['mesa'];
$zona    = $_SESSION['zona'];

$config = [
    'plato'  => ['tabla' => 'plato',  'nombre' => 'nom_plato',  'precio' => 'precio_plato',  'id' => 'id_plato'],
    'bebida' => ['tabla' => 'bebida', 'nombre' => 'nom_bebida', 'precio' => 'precio_bebida', 'id' => 'id_bebida'],
    'postre' => ['tabla' => 'postre', 'nombre' => 'nom_postre', 'precio' => 'precio_postre', 'id' => 'id_postre'],
];

// ── POST: agregar ítems al carrito con su observación individual ──────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['pedido_items'])) {
        $_SESSION['pedido_items'] = [];
    }

    foreach ($_SESSION['items_temp'] as $index => $item) {
        $cfg  = $config[$item['tipo']];
        $obs  = trim($_POST['obs'][$index] ?? '');

        $stmt = $conexion->prepare("SELECT {$cfg['nombre']}, {$cfg['precio']} FROM {$cfg['tabla']} WHERE {$cfg['id']} = ?");
        $stmt->bind_param("i", $item['id']);
        $stmt->execute();
        $datos = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$datos) continue;

        // Una entrada por unidad para poder eliminarlas de a una en modificar_pedido
        for ($u = 0; $u < $item['cantidad']; $u++) {
            $_SESSION['pedido_items'][] = [
                'tipo'        => $item['tipo'],
                'id'          => $item['id'],
                'nombre'      => $datos[$cfg['nombre']],
                'precio'      => $datos[$cfg['precio']],
                'observacion' => $obs,
            ];
        }
    }

    unset($_SESSION['items_temp']);

    // Si hay un pedido existente al que agregar ítems, ir al procesador específico
    if (!empty($_SESSION['id_pedido_existente'])) {
        header("Location: agregar_a_pedido_existente.php");
    } else {
        header("Location: nuevo_pedido.php?mesa=$id_mesa&zona=" . urlencode($zona));
    }
    exit;
}

// ── GET: construir la lista con nombres para mostrar el formulario ────
$items_vista = [];
foreach ($_SESSION['items_temp'] as $index => $item) {
    $cfg  = $config[$item['tipo']];
    $stmt = $conexion->prepare("SELECT {$cfg['nombre']}, {$cfg['precio']} FROM {$cfg['tabla']} WHERE {$cfg['id']} = ?");
    $stmt->bind_param("i", $item['id']);
    $stmt->execute();
    $datos = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $items_vista[] = [
        'index'    => $index,
        'nombre'   => $datos[$cfg['nombre']] ?? 'Desconocido',
        'precio'   => $datos[$cfg['precio']] ?? 0,
        'cantidad' => $item['cantidad'],
        'tipo'     => $item['tipo'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Observaciones — Mesa <?= $id_mesa ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container" style="max-width: 620px;">

    <h4 class="mb-1">
        <i class="bi bi-chat-left-text"></i>
        Observaciones — Mesa <?= $id_mesa ?>
    </h4>
    <p class="text-muted small mb-4">Podés agregar una observación específica para cada ítem.</p>

    <form method="POST">
        <?php foreach ($items_vista as $item): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-secondary me-1"><?= ucfirst($item['tipo']) ?></span>
                        <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                    </div>
                    <div class="text-end text-muted small">
                        x<?= $item['cantidad'] ?> &nbsp;|&nbsp;
                        $<?= number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?>
                    </div>
                </div>
                <textarea name="obs[<?= $item['index'] ?>]"
                          class="form-control form-control-sm"
                          rows="2"
                          placeholder="Observación para este ítem (opcional)..."></textarea>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="d-flex gap-2 mt-2 mb-5">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Agregar al pedido
            </button>
            <a href="carta.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>" class="btn btn-outline-secondary">
                Volver a la carta
            </a>
        </div>
    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
