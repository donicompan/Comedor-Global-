<?php
/**
 * 001 — Agrega columna imagen a plato, bebida y postre.
 * Compatible con MySQL 8.0 (no soporta ADD COLUMN IF NOT EXISTS).
 */
return function (mysqli $conn): void {
    foreach (['plato', 'bebida', 'postre'] as $tabla) {
        $res = $conn->query("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = '$tabla'
              AND COLUMN_NAME  = 'imagen'
        ");
        if ($res && (int)$res->fetch_row()[0] === 0) {
            $conn->query("ALTER TABLE `$tabla` ADD COLUMN `imagen` VARCHAR(120) NOT NULL DEFAULT ''");
        }
    }
};
