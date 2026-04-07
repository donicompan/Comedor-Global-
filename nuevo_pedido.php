<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

include('app.php');

$id_mesa = intval($_GET['mesa'] ?? 0);
$zona    = htmlspecialchars($_GET['zona'] ?? '');
$rol     = $_SESSION['rol'] ?? '';

if (!isset($_SESSION['pedido_items'])) {
    $_SESSION['pedido_items'] = [];
}

// Si la mesa cambió, limpiar el carrito y el cajero asignado.
// Comparamos como enteros para evitar fallos por tipo string vs int.
if (isset($_SESSION['mesa']) && intval($_SESSION['mesa']) !== $id_mesa) {
    $_SESSION['pedido_items'] = [];
    unset($_SESSION['id_cajero']);
}

$_SESSION['mesa'] = $id_mesa;
$_SESSION['zona'] = $zona;

// ── Eliminar un ítem del carrito ──────────────────────────
if (isset($_GET['eliminar'])) {
    $i = intval($_GET['eliminar']);
    if (isset($_SESSION['pedido_items'][$i])) {
        array_splice($_SESSION['pedido_items'], $i, 1);
    }
    header("Location: nuevo_pedido.php?mesa=$id_mesa&zona=" . urlencode($zona));
    exit;
}

// ── Limpiar todo el carrito ───────────────────────────────
if (isset($_GET['limpiar'])) {
    $_SESSION['pedido_items'] = [];
    header("Location: nuevo_pedido.php?mesa=$id_mesa&zona=" . urlencode($zona));
    exit;
}

// ── Determinar cajero y mozo según el rol ─────────────────
//
// Si ingresó como CAJERO:
//   - El cajero ES quien toma el pedido → id_cajero = su propio ID
//   - No hay mozo involucrado → id_mozo = 0 (se guarda 1 en BD por FK, pero no se muestra)
//
// Si ingresó como MOZO:
//   - Debe haber elegido un cajero previamente en seleccionar_cajero.php
//   - id_mozo = su propio ID

if ($rol === 'cajero') {
    $id_cajero = intval($_SESSION['id_usuario']);
    $id_mozo   = null; // el cajero opera sin mozo — se guarda NULL en la BD

} elseif ($rol === 'mozo') {
    $id_mozo   = intval($_SESSION['id_usuario']);
    $id_cajero = intval($_SESSION['id_cajero'] ?? 0);

    if ($id_cajero <= 0) {
        header("Location: seleccionar_cajero.php?mesa=$id_mesa&zona=" . urlencode($zona));
        exit;
    }

} else {
    header("Location: index.php");
    exit;
}

// ── POST: guardar el pedido ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = validarAntesDeGuardar($id_mozo, $id_cajero, $conexion);

    if (!$error) {
        $id_pedido = guardarPedido($id_mesa, $id_mozo, $id_cajero, $rol, $conexion);
        if ($id_pedido) {
            $_SESSION['pedido_items'] = [];
            unset($_SESSION['id_cajero']);
            header("Location: detalle_pedido.php?id=$id_pedido&mesa=$id_mesa");
            exit;
        }
        $error = "Ocurrió un error al guardar el pedido. Intentá de nuevo.";
    }
}

// ── Agrupar ítems para la tabla ───────────────────────────
$agrupado = agruparItems($_SESSION['pedido_items']);

// ── Nombres para mostrar en el encabezado ────────────────
$cajero = $conexion->query("SELECT nom_cajero, ape_cajero FROM cajero WHERE id_cajero = $id_cajero")->fetch_assoc();
$nombre_cajero = $cajero ? $cajero['nom_cajero'] . ' ' . $cajero['ape_cajero'] : '—';

// El mozo solo se muestra si quien opera es un mozo
if ($rol === 'mozo') {
    $mozo = $conexion->query("SELECT nom_mozo, ape_mozo FROM mozo WHERE id_mozo = $id_mozo")->fetch_assoc();
    $nombre_mozo = $mozo ? $mozo['nom_mozo'] . ' ' . $mozo['ape_mozo'] : '—';
} else {
    $nombre_mozo = null; // el cajero opera solo, no hay mozo que mostrar
}

// ============================================================
// FUNCIONES
// ============================================================

// Agrupa ítems solo para MOSTRAR en la tabla del carrito (visual).
// Ítems del mismo producto con distinta observación quedan en filas separadas.
function agruparItems(array $items): array {
    $agrupado = [];
    foreach ($items as $item) {
        // La clave incluye la observación: mismo producto con distinta obs = fila distinta
        $key = $item['tipo'] . '_' . $item['id'] . '_' . ($item['observacion'] ?? '');
        if (!isset($agrupado[$key])) {
            $agrupado[$key] = [
                'tipo'        => $item['tipo'],
                'id'          => $item['id'],
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
    return $agrupado;
}

function validarAntesDeGuardar(?int $id_mozo, int $id_cajero, mysqli $conexion): string {
    if (empty($_SESSION['pedido_items'])) return "El carrito está vacío.";
    if ($id_cajero <= 0) return "No se pudo asignar un cajero.";

    $cajero_existe = $conexion->query("SELECT id_cajero FROM cajero WHERE id_cajero = $id_cajero")->num_rows;
    if (!$cajero_existe) return "El cajero asignado no existe.";

    if ($id_mozo > 0) {
        $mozo_existe = $conexion->query("SELECT id_mozo FROM mozo WHERE id_mozo = $id_mozo")->num_rows;
        if (!$mozo_existe) return "El mozo asignado no existe.";
    }

    return '';
}

function guardarPedido(int $id_mesa, ?int $id_mozo, int $id_cajero, string $rol, mysqli $conexion): int {
    $items       = $_SESSION['pedido_items'];
    $observacion = trim($_POST['observacion'] ?? '');

    // Calcular total sumando el precio de cada unidad individual
    $total = array_sum(array_column($items, 'precio'));

    $tablas = [
        'plato'  => ['detalle_plato',  'id_plato'],
        'bebida' => ['detalle_bebida', 'id_bebida'],
        'postre' => ['detalle_postre', 'id_postre'],
    ];

    $conexion->begin_transaction();

    try {
        // Insertar el pedido principal
        $stmt = $conexion->prepare("
            INSERT INTO pedido (descr_pedido, estado_pedido, id_mozo, id_mesa, id_cajero, total_pedido, fecha_pedido)
            VALUES (?, 'En Proceso', ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("siiii", $observacion, $id_mozo, $id_mesa, $id_cajero, $total);
        $stmt->execute();
        $id_pedido = $stmt->insert_id;
        $stmt->close();

        // Agrupar para insertar en detalle — misma obs + mismo producto = una fila
        $agrupado = agruparItems($items);

        foreach ($agrupado as $item) {
            [$tabla, $col_id] = $tablas[$item['tipo']];
            $obs = $item['observacion'];
            $stmt = $conexion->prepare("
                INSERT INTO $tabla (id_pedido, $col_id, cantidad, precio_unitario, subtotal, observacion)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiidds", $id_pedido, $item['id'], $item['cantidad'], $item['precio'], $item['subtotal'], $obs);
            $stmt->execute();
            $stmt->close();
        }

        // Marcar la mesa como ocupada
        $stmt = $conexion->prepare("UPDATE mesa SET dispo_mesa = 'Ocupada' WHERE id_mesa = ?");
        $stmt->bind_param("i", $id_mesa);
        $stmt->execute();
        $stmt->close();

        $conexion->commit();
        return $id_pedido;

    } catch (Exception $e) {
        $conexion->rollback();
        return 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Pedido — Mesa <?= $id_mesa ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container" style="max-width: 800px;">

    <h4 class="mb-1">
        <i class="bi bi-cart3"></i> Pedido — Mesa <?= $id_mesa ?>
        <span class="text-muted fw-normal">(<?= $zona ?>)</span>
    </h4>

    <p class="text-muted small mb-4">
        <i class="bi bi-cash-register"></i> <?= htmlspecialchars($nombre_cajero) ?>
        <?php if ($nombre_mozo): ?>
            &nbsp;|&nbsp;
            <i class="bi bi-person-badge"></i> <?= htmlspecialchars($nombre_mozo) ?>
        <?php endif; ?>
    </p>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Botones -->
    <div class="d-flex gap-2 mb-4">
        <a href="carta.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Agregar productos
        </a>
        <a href="modificar_pedido.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>"
           class="btn btn-outline-warning <?= empty($_SESSION['pedido_items']) ? 'disabled' : '' ?>">
            <i class="bi bi-pencil"></i> Modificar
        </a>
        <a href="cuenta.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>"
           class="btn btn-outline-info <?= empty($_SESSION['pedido_items']) ? 'disabled' : '' ?>">
            <i class="bi bi-receipt"></i> Ver cuenta
        </a>
        <a href="nuevo_pedido.php?mesa=<?= $id_mesa ?>&zona=<?= urlencode($zona) ?>&limpiar=1"
           class="btn btn-outline-danger ms-auto <?= empty($_SESSION['pedido_items']) ? 'disabled' : '' ?>"
           onclick="return confirm('¿Vaciar el carrito?')">
            <i class="bi bi-trash"></i> Vaciar
        </a>
        <a href="principal.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Tabla del carrito -->
    <div class="card mb-4">
        <div class="card-header fw-bold">
            <i class="bi bi-list-ul"></i> Ítems del pedido
        </div>

        <?php if (empty($agrupado)): ?>
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-cart-x" style="font-size: 2.5rem;"></i>
            <p class="mt-2">El carrito está vacío. Agregá productos desde la carta.</p>
        </div>

        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>Observación</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $total = 0;
                foreach ($agrupado as $item):
                    $total += $item['subtotal'];
                ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary me-1"><?= ucfirst($item['tipo']) ?></span>
                            <?= htmlspecialchars($item['nombre']) ?>
                        </td>
                        <td class="text-muted small">
                            <?= $item['observacion'] !== '' ? htmlspecialchars($item['observacion']) : '—' ?>
                        </td>
                        <td class="text-center"><?= $item['cantidad'] ?></td>
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
        <?php endif; ?>
    </div>

    <!-- Confirmar pedido -->
    <?php if (!empty($agrupado)): ?>
    <div class="card">
        <div class="card-header fw-bold bg-success text-white">
            <i class="bi bi-check-circle"></i> Confirmar pedido
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">
                        Observación general <span class="text-muted">(opcional)</span>
                    </label>
                    <textarea name="observacion" class="form-control" rows="2"
                              placeholder="Ej: alergia a los mariscos, es cumpleaños, etc."></textarea>
                </div>
                <button type="submit" name="guardar" class="btn btn-success">
                    <i class="bi bi-send"></i> Guardar y enviar pedido
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
