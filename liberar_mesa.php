<?php
/*
 * liberar_mesa.php
 *
 * Marca el pedido como Completado y la mesa como Disponible.
 * Es una operación pura (sin HTML), redirige siempre al final.
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}
if ($_SESSION['rol'] !== 'cajero') {
    header("Location: principal.php");
    exit;
}

include('app.php');

$id_mesa   = intval($_GET['mesa']   ?? 0);
$id_pedido = intval($_GET['pedido'] ?? 0);

if ($id_mesa <= 0 || $id_pedido <= 0) {
    header("Location: principal.php?error=datos_invalidos");
    exit;
}

$conexion->begin_transaction();

try {
    // Solo actualizar el pedido si existe uno real
    if ($id_pedido > 0) {
        $stmt = $conexion->prepare("UPDATE pedido SET estado_pedido = 'Completado' WHERE id_pedido = ? AND estado_pedido IN ('En Proceso', 'Listo')");
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();
        $stmt->close();
    }

    // Siempre liberar la mesa
    $stmt = $conexion->prepare("UPDATE mesa SET dispo_mesa = 'Disponible' WHERE id_mesa = ?");
    $stmt->bind_param("i", $id_mesa);
    $stmt->execute();
    $stmt->close();

    $conexion->commit();
    header("Location: principal.php?ok=mesa_liberada");

} catch (Exception $e) {
    $conexion->rollback();
    header("Location: principal.php?error=error_al_liberar");
}

$conexion->close();
exit;
