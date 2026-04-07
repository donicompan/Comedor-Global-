<?php
/*
 * descripcion_item.php
 *
 * Recibe el formulario de carta.php con las cantidades elegidas.
 * Guarda los ítems seleccionados en $_SESSION['items_temp']
 * y redirige a descripcion.php para agregar observaciones.
 */
session_start();

$id_mesa = intval($_POST['mesa'] ?? 0);
$zona    = htmlspecialchars($_POST['zona'] ?? '');

if (!$id_mesa || !$zona) {
    header("Location: principal.php");
    exit;
}

$_SESSION['mesa'] = $id_mesa;
$_SESSION['zona'] = $zona;

// Construir la lista de ítems con cantidad > 0
$items = [];

$tipos = [
    'plato'  => $_POST['plato']  ?? [],
    'bebida' => $_POST['bebida'] ?? [],
    'postre' => $_POST['postre'] ?? [],
];

foreach ($tipos as $tipo => $productos) {
    foreach ($productos as $id => $cantidad) {
        $cantidad = intval($cantidad);
        if ($cantidad > 0) {
            $items[] = [
                'tipo'     => $tipo,
                'id'       => intval($id),
                'cantidad' => $cantidad,
            ];
        }
    }
}

// Si no eligió nada, volver a la carta
if (empty($items)) {
    header("Location: carta.php?mesa=$id_mesa&zona=" . urlencode($zona));
    exit;
}

$_SESSION['items_temp'] = $items;

header("Location: descripcion.php");
exit;
