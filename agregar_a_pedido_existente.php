<?php
/*
 * agregar_a_pedido_existente.php
 *
 * Toma los ítems del carrito de sesión ($_SESSION['pedido_items'])
 * y los inserta en el pedido existente indicado por
 * $_SESSION['id_pedido_existente']. También actualiza el total del pedido.
 *
 * Al finalizar redirige a detalle_pedido.php con el pedido actualizado.
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

include('app.php');

$id_pedido = intval($_SESSION['id_pedido_existente'] ?? 0);
$id_mesa   = intval($_SESSION['mesa'] ?? 0);
$zona      = htmlspecialchars($_SESSION['zona'] ?? '');
$items     = $_SESSION['pedido_items'] ?? [];

// Validaciones básicas
if ($id_pedido <= 0 || empty($items)) {
    unset($_SESSION['id_pedido_existente']);
    header("Location: principal.php");
    exit;
}

// Verificar que el pedido exista y esté en proceso
$stmt = $conexion->prepare("SELECT id_pedido, total_pedido FROM pedido WHERE id_pedido = ? AND estado_pedido = 'En Proceso'");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    // El pedido ya no existe o no está en proceso
    unset($_SESSION['id_pedido_existente']);
    $_SESSION['pedido_items'] = [];
    header("Location: detalle_pedido.php?id=$id_pedido&mesa=$id_mesa");
    exit;
}

// Configuración de tablas por tipo
$tablas = [
    'plato'  => ['detalle_plato',  'id_plato'],
    'bebida' => ['detalle_bebida', 'id_bebida'],
    'postre' => ['detalle_postre', 'id_postre'],
];

// Agrupar ítems: mismo producto + misma observación = una fila en detalle
$agrupado = [];
foreach ($items as $item) {
    $key = $item['tipo'] . '_' . $item['id'] . '_' . ($item['observacion'] ?? '');
    if (!isset($agrupado[$key])) {
        $agrupado[$key] = [
            'tipo'        => $item['tipo'],
            'id'          => $item['id'],
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

$conexion->begin_transaction();

try {
    $total_nuevo = 0;

    foreach ($agrupado as $item) {
        [$tabla, $col_id] = $tablas[$item['tipo']];
        $obs = $item['observacion'];

        // Verificar si ya existe una fila con el mismo producto y observación en este pedido
        // Si existe, se incrementa la cantidad y el subtotal; si no, se inserta una nueva fila.
        $col_pk = 'id_' . $tabla; // ej: id_detalle_plato
        $stmt = $conexion->prepare("
            SELECT {$col_pk} AS id_det, cantidad, subtotal
            FROM {$tabla}
            WHERE id_pedido = ? AND {$col_id} = ? AND IFNULL(observacion,'') = IFNULL(?,'')
        ");
        $stmt->bind_param("iis", $id_pedido, $item['id'], $obs);
        $stmt->execute();
        $existente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existente) {
            // Actualizar fila existente
            $nueva_cantidad = $existente['cantidad'] + $item['cantidad'];
            $nuevo_subtotal = $existente['subtotal'] + $item['subtotal'];

            $stmt = $conexion->prepare("
                UPDATE {$tabla}
                SET cantidad = ?, subtotal = ?
                WHERE {$col_pk} = ?
            ");
            $stmt->bind_param("iii", $nueva_cantidad, $nuevo_subtotal, $existente['id_det']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insertar nueva fila
            $stmt = $conexion->prepare("
                INSERT INTO {$tabla} (id_pedido, {$col_id}, cantidad, precio_unitario, subtotal, observacion)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiidds", $id_pedido, $item['id'], $item['cantidad'], $item['precio'], $item['subtotal'], $obs);
            $stmt->execute();
            $stmt->close();
        }

        $total_nuevo += $item['subtotal'];
    }

    // Actualizar el total del pedido sumando los nuevos ítems al total existente
    $stmt = $conexion->prepare("UPDATE pedido SET total_pedido = total_pedido + ? WHERE id_pedido = ?");
    $stmt->bind_param("di", $total_nuevo, $id_pedido);
    $stmt->execute();
    $stmt->close();

    $conexion->commit();

} catch (Exception $e) {
    $conexion->rollback();
    // En caso de error, volver al detalle sin cambios
    unset($_SESSION['id_pedido_existente']);
    $_SESSION['pedido_items'] = [];
    header("Location: detalle_pedido.php?id=$id_pedido&mesa=$id_mesa&error=1");
    exit;
}

// Limpiar sesión
unset($_SESSION['id_pedido_existente']);
$_SESSION['pedido_items'] = [];

$conexion->close();

// Redirigir al detalle del pedido actualizado
header("Location: detalle_pedido.php?id=$id_pedido&mesa=$id_mesa");
exit;
