<?php
/*
 * cancelar_pedido.php
 *
 * Anula un pedido activo (En Proceso o Listo):
 *   - Cambia estado_pedido a 'Anulado'
 *   - Libera la mesa (dispo_mesa = 'Disponible')
 *   - El pedido no aparece en reportes de ventas (filtran por 'Completado')
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

include('app.php');

$id_pedido = intval($_GET['id']   ?? 0);
$id_mesa   = intval($_GET['mesa'] ?? 0);

if (!$id_pedido || !$id_mesa) {
    header("Location: principal.php");
    exit;
}

// Verificar que el pedido exista y esté activo
$stmt = $conexion->prepare("SELECT estado_pedido FROM pedido WHERE id_pedido = ? AND id_mesa = ?");
$stmt->bind_param("ii", $id_pedido, $id_mesa);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido || !in_array($pedido['estado_pedido'], ['En Proceso', 'Listo'])) {
    header("Location: principal.php?error=1");
    exit;
}

// Anular pedido y liberar mesa en una transacción
$conexion->begin_transaction();
try {
    $stmt = $conexion->prepare("UPDATE pedido SET estado_pedido = 'Anulado' WHERE id_pedido = ?");
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("UPDATE mesa SET dispo_mesa = 'Disponible' WHERE id_mesa = ?");
    $stmt->bind_param("i", $id_mesa);
    $stmt->execute();
    $stmt->close();

    $conexion->commit();
    header("Location: principal.php?ok=1");
} catch (Exception $e) {
    $conexion->rollback();
    header("Location: principal.php?error=1");
}
exit;
