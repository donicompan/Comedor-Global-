<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

include('app.php');

$id_mesa = intval($_GET['mesa'] ?? 0);
$zona    = htmlspecialchars($_GET['zona'] ?? '');

// Traer todos los productos ordenados alfabéticamente
$platos  = $conexion->query("SELECT * FROM plato  ORDER BY nom_plato")->fetch_all(MYSQLI_ASSOC);
$bebidas = $conexion->query("SELECT * FROM bebida  ORDER BY nom_bebida")->fetch_all(MYSQLI_ASSOC);
$postres = $conexion->query("SELECT * FROM postre  ORDER BY nom_postre")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta — Mesa <?= $id_mesa ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'pwa_head.php'; ?>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; }
        .seccion-header { background: linear-gradient(135deg,#1a1a2e,#0f3460); color:white; border-radius:12px; padding:12px 16px; margin-bottom:0; }
        .producto-row { display:flex; align-items:center; gap:12px; padding:10px 14px; border-bottom:1px solid #f0f0f0; background:white; transition:background .15s; }
        .producto-row:last-child { border-bottom:none; border-radius:0 0 12px 12px; }
        .producto-row:hover { background:#f8f9fa; }
        .producto-img { width:56px; height:56px; border-radius:10px; object-fit:cover; flex-shrink:0; background:#f0f0f0; }
        .producto-img-placeholder { width:56px; height:56px; border-radius:10px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.4rem; color:#ccc; }
        .producto-info { flex:1; min-width:0; }
        .producto-nombre { font-weight:600; font-size:.95rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .producto-desc { font-size:.78rem; color:#888; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .producto-precio { font-size:.85rem; font-weight:700; color:#198754; white-space:nowrap; }
        .qty-input { width:64px; text-align:center; border-radius:8px; border:1px solid #dee2e6; padding:6px 4px; font-size:.9rem; font-weight:600; }
        .qty-input:focus { outline:none; border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.15); }
        .seccion-wrap { border-radius:12px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:20px; }
        .btn-confirmar { position:sticky; bottom:16px; z-index:10; }
        @media(max-width:400px) {
            .producto-img, .producto-img-placeholder { width:44px; height:44px; font-size:1.1rem; }
            .producto-nombre { font-size:.88rem; }
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container" style="max-width: 720px;">

    <h4 class="mb-4">
        <i class="bi bi-journal-text"></i>
        Carta — Mesa <?= $id_mesa ?> <span class="text-muted fw-normal">(<?= $zona ?>)</span>
    </h4>

    <!--
        El formulario envía las cantidades a descripcion_item.php,
        que guarda los ítems en sesión y redirige a descripcion.php
        para agregar observaciones antes de confirmar.
    -->
    <form action="descripcion_item.php" method="POST">
        <input type="hidden" name="mesa" value="<?= $id_mesa ?>">
        <input type="hidden" name="zona" value="<?= $zona ?>">

        <?php
        // Función local para renderizar una sección de productos
        // Así no repetimos el mismo bloque HTML tres veces
        function renderSeccion(string $titulo, string $icono, array $productos, string $tipo, string $campo_id, string $campo_nombre, string $campo_precio, string $campo_desc = '', string $campo_img = ''): void {
            if (empty($productos)) return;
        ?>
        <div class="seccion-wrap">
            <div class="seccion-header">
                <i class="bi bi-<?= $icono ?>"></i> <?= $titulo ?>
                <span class="badge bg-white text-dark ms-2 small"><?= count($productos) ?></span>
            </div>
            <?php foreach ($productos as $item):
                $img    = $campo_img && !empty($item[$campo_img]) ? $item[$campo_img] : '';
                $desc   = $campo_desc ? ($item[$campo_desc] ?? '') : '';
                $precio = '$' . number_format($item[$campo_precio], 0, ',', '.');
            ?>
            <div class="producto-row">
                <?php if ($img && file_exists(__DIR__ . '/' . $img)): ?>
                    <img src="<?= htmlspecialchars($img) ?>" alt="" class="producto-img">
                <?php else: ?>
                    <div class="producto-img-placeholder">
                        <i class="bi bi-<?= $icono ?>"></i>
                    </div>
                <?php endif; ?>

                <div class="producto-info">
                    <div class="producto-nombre"><?= htmlspecialchars($item[$campo_nombre]) ?></div>
                    <?php if ($desc): ?>
                    <div class="producto-desc"><?= htmlspecialchars($desc) ?></div>
                    <?php endif; ?>
                    <div class="producto-precio"><?= $precio ?></div>
                </div>

                <input type="number"
                       name="<?= $tipo ?>[<?= $item[$campo_id] ?>]"
                       min="0" value="0" max="99"
                       class="qty-input"
                       inputmode="numeric">
            </div>
            <?php endforeach; ?>
        </div>
        <?php } ?>

        <?php renderSeccion('Platos',  'egg-fried', $platos,  'plato',  'id_plato',  'nom_plato',  'precio_plato',  'descr_plato',  'imagen'); ?>
        <?php renderSeccion('Bebidas', 'cup-straw', $bebidas, 'bebida', 'id_bebida', 'nom_bebida', 'precio_bebida', 'desc_bebida',   'imagen'); ?>
        <?php renderSeccion('Postres', 'cake2',     $postres, 'postre', 'id_postre', 'nom_postre', 'precio_postre', 'desc_postre',   'imagen'); ?>

        <div class="btn-confirmar d-flex gap-2 pb-4 pt-2">
            <button type="submit" class="btn btn-primary flex-grow-1 py-3 fw-bold fs-6">
                <i class="bi bi-check-circle-fill"></i> Confirmar selección
            </button>
            <a href="nuevo_pedido.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>" class="btn btn-outline-secondary py-3 px-4">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>

    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
