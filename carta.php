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
    <title>Carta — Mesa <?= $id_mesa ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
        function renderSeccion(string $titulo, string $icono, array $productos, string $tipo, string $campo_id, string $campo_nombre, string $campo_precio): void {
        ?>
        <div class="card mb-4">
            <div class="card-header fw-bold">
                <i class="bi bi-<?= $icono ?>"></i> <?= $titulo ?>
            </div>
            <div class="card-body p-0">
                <?php foreach ($productos as $item): ?>
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                    <div>
                        <strong><?= htmlspecialchars($item[$campo_nombre]) ?></strong>
                        <div class="text-muted small">$<?= number_format($item[$campo_precio], 0, ',', '.') ?></div>
                    </div>
                    <div style="width: 80px;">
                        <input type="number"
                               name="<?= $tipo ?>[<?= $item[$campo_id] ?>]"
                               min="0" value="0"
                               class="form-control form-control-sm text-center">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php } ?>

        <?php renderSeccion('Platos',  'egg-fried',   $platos,  'plato',  'id_plato',  'nom_plato',  'precio_plato'); ?>
        <?php renderSeccion('Bebidas', 'cup-straw',   $bebidas, 'bebida', 'id_bebida', 'nom_bebida', 'precio_bebida'); ?>
        <?php renderSeccion('Postres', 'cake2',       $postres, 'postre', 'id_postre', 'nom_postre', 'precio_postre'); ?>

        <div class="d-flex gap-2 mb-5">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Confirmar selección
            </button>
            <a href="nuevo_pedido.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>" class="btn btn-outline-secondary">
                Cancelar
            </a>
        </div>

    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
