<?php
/**
 * backup_bd.php — Descarga un volcado SQL de la base de datos.
 * Solo accesible para cajeros logueados.
 */
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'cajero') {
    header("Location: principal.php");
    exit;
}

require_once __DIR__ . '/conexion.php';

$db_name  = DB_NAME;
$filename = 'backup_' . $db_name . '_' . date('Y-m-d_H-i-s') . '.sql';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$output = fopen('php://output', 'w');

// Cabecera del archivo
fwrite($output, "-- Backup de base de datos: $db_name\n");
fwrite($output, "-- Generado: " . date('Y-m-d H:i:s') . "\n");
fwrite($output, "-- Sistema: Dony Software POS\n\n");
fwrite($output, "SET FOREIGN_KEY_CHECKS=0;\n\n");

// Obtener todas las tablas
$tablas = $conexion->query("SHOW TABLES")->fetch_all(MYSQLI_NUM);

foreach ($tablas as [$tabla]) {
    // DROP + CREATE
    $create = $conexion->query("SHOW CREATE TABLE `$tabla`")->fetch_row();
    fwrite($output, "DROP TABLE IF EXISTS `$tabla`;\n");
    fwrite($output, $create[1] . ";\n\n");

    // Datos
    $filas = $conexion->query("SELECT * FROM `$tabla`");
    if ($filas && $filas->num_rows > 0) {
        // Obtener nombres de columnas
        $cols_info  = $conexion->query("SHOW COLUMNS FROM `$tabla`")->fetch_all(MYSQLI_ASSOC);
        $col_names  = array_map(fn($c) => '`' . $c['Field'] . '`', $cols_info);
        $cols_str   = implode(', ', $col_names);

        fwrite($output, "INSERT INTO `$tabla` ($cols_str) VALUES\n");
        $total = $filas->num_rows;
        $i     = 0;
        while ($fila = $filas->fetch_row()) {
            $i++;
            $valores = array_map(function ($v) use ($conexion) {
                if ($v === null) return 'NULL';
                return "'" . $conexion->real_escape_string($v) . "'";
            }, $fila);
            $sep = ($i < $total) ? ',' : ';';
            fwrite($output, '(' . implode(', ', $valores) . ')' . $sep . "\n");
        }
        fwrite($output, "\n");
    }
}

fwrite($output, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($output);
$conexion->close();
exit;
