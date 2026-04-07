<?php
/*
 * marcar_listo.php
 *
 * Recibe el POST de cocina.php cuando el cocinero marca un pedido como listo.
 * Cambia el estado del pedido a 'Listo' (distinto de 'Completado' que es cuando se cobra).
 * Requiere agregar 'Listo' como estado válido o usar el mismo flujo existente.
 */
include('app.php');

$id_pedido = intval($_POST['id_pedido'] ?? 0);
$id_mesa   = intval($_POST['id_mesa']   ?? 0);

if ($id_pedido <= 0) {
    header("Location: cocina.php");
    exit;
}

// Cambiamos el estado a 'Listo' para que la pantalla de cocina lo oculte
// pero el mozo/cajero todavía lo ve en el panel como pendiente de cobro
$stmt = $conexion->prepare("UPDATE pedido SET estado_pedido = 'Listo' WHERE id_pedido = ?");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$stmt->close();

$conexion->close();
header("Location: cocina.php");
exit;
