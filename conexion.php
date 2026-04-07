<?php
require_once __DIR__ . '/config.php';

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conexion->connect_error) {
    error_log("DB connect error: " . $conexion->connect_error);
    http_response_code(503);
    die("El servicio no está disponible en este momento. Intentá más tarde.");
}

$conexion->set_charset("utf8mb4");
