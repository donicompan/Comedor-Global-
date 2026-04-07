<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

include('app.php');
include('csrf.php');

$id_mesa = intval($_GET['mesa'] ?? $_POST['mesa'] ?? 0);
$zona    = htmlspecialchars($_GET['zona'] ?? $_POST['zona'] ?? '');

// Si quien ingresó ES cajero, no necesita elegir cajero — él mismo lo es.
if ($_SESSION['rol'] === 'cajero') {
    $_SESSION['id_cajero'] = intval($_SESSION['id_usuario']);
    header("Location: nuevo_pedido.php?mesa=$id_mesa&zona=" . urlencode($zona));
    exit;
}

// POST: guardar cajero seleccionado (solo llega el mozo)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $id_cajero = intval($_POST['id_cajero'] ?? 0);
    if ($id_cajero <= 0) {
        header("Location: seleccionar_cajero.php?mesa=$id_mesa&zona=" . urlencode($zona) . "&error=1");
        exit;
    }
    $_SESSION['id_cajero'] = $id_cajero;
    header("Location: nuevo_pedido.php?mesa=$id_mesa&zona=" . urlencode($zona));
    exit;
}

// GET: mostrar listado (solo el mozo llega hasta aquí)
$cajeros = $conexion->query("SELECT id_cajero, nom_cajero, ape_cajero, usu_cajero FROM cajero WHERE estado = 'Activo' ORDER BY nom_cajero")->fetch_all(MYSQLI_ASSOC);
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar Cajero — Mesa <?= $id_mesa ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container" style="max-width: 520px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-person-check"></i>
                Seleccionar cajero — Mesa <?= $id_mesa ?> (<?= $zona ?>)
            </h5>
        </div>
        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> Seleccioná un cajero para continuar.
                </div>
            <?php endif; ?>

            <?php if (empty($cajeros)): ?>
                <div class="alert alert-danger">
                    No hay cajeros registrados. Contactá al administrador.
                </div>
                <a href="principal.php" class="btn btn-secondary">Volver</a>
            <?php else: ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mesa" value="<?= $id_mesa ?>">
                    <input type="hidden" name="zona" value="<?= $zona ?>">
                    <p class="text-muted small mb-3">Seleccioná el cajero que gestionará este pedido:</p>
                    <div class="list-group mb-4">
                        <?php foreach ($cajeros as $cajero): ?>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                            <input type="radio" name="id_cajero" value="<?= $cajero['id_cajero'] ?>" required>
                            <div>
                                <strong><?= htmlspecialchars($cajero['nom_cajero'] . ' ' . $cajero['ape_cajero']) ?></strong>
                                <div class="text-muted small">@<?= htmlspecialchars($cajero['usu_cajero']) ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Continuar
                        </button>
                        <a href="principal.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
