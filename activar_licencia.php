<?php
/**
 * activar_licencia.php — Handler de activación de licencia.
 * Valida la clave recibida por POST y la guarda en la BD.
 */
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/licencia.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: expirado.php");
    exit;
}

$clave = trim($_POST['licencia_clave'] ?? '');

if (!$clave) {
    header("Location: expirado.php?error=clave_vacia");
    exit;
}

$resultado = validar_clave_licencia($clave);

if (!$resultado['valida']) {
    header("Location: expirado.php?error=clave_invalida");
    exit;
}

// Guardar la clave en la BD
guardar_clave_licencia($clave, $conexion);

// Redirigir según si hay sesión activa
$destino = isset($_SESSION['usuario']) ? 'principal.php' : 'index.php';
header("Location: $destino?lic=activada");
exit;
