<?php
/*
 * limpiar_y_agregar.php
 *
 * Limpia el carrito de sesión antes de agregar ítems a un pedido
 * ya existente. Así se evita que ítems de un pedido anterior
 * interfieran con el pedido actual.
 *
 * Parámetros GET esperados:
 *   id_pedido  — ID del pedido existente al que se agregarán ítems
 *   mesa       — número de mesa
 *   zona       — nombre de la zona (Salón / Patio)
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$id_pedido = intval($_GET['id_pedido'] ?? 0);
$id_mesa   = intval($_GET['mesa']      ?? 0);
$zona      = htmlspecialchars($_GET['zona'] ?? '');

if ($id_pedido <= 0 || $id_mesa <= 0) {
    header("Location: principal.php");
    exit;
}

// Limpiar el carrito de sesión para no mezclar con pedidos anteriores
$_SESSION['pedido_items'] = [];

// Guardar el id_pedido existente en sesión para que el flujo lo use
$_SESSION['id_pedido_existente'] = $id_pedido;
$_SESSION['mesa'] = $id_mesa;
$_SESSION['zona'] = $zona;

// Ir a la carta normalmente
header("Location: carta.php?mesa=$id_mesa&zona=" . urlencode($zona));
exit;
