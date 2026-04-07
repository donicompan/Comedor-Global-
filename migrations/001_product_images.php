<?php
/**
 * 001 — Agrega columna imagen a plato, bebida y postre.
 * Los valores existentes quedan vacíos; la imagen es opcional.
 */
return [
    "ALTER TABLE plato  ADD COLUMN IF NOT EXISTS imagen VARCHAR(120) NOT NULL DEFAULT ''",
    "ALTER TABLE bebida ADD COLUMN IF NOT EXISTS imagen VARCHAR(120) NOT NULL DEFAULT ''",
    "ALTER TABLE postre ADD COLUMN IF NOT EXISTS imagen VARCHAR(120) NOT NULL DEFAULT ''",
];
