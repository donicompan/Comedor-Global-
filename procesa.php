<?php
session_start();
include('app.php');
require_once __DIR__ . '/csrf.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

csrf_validate();

$usuario  = trim($_POST['user'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    header("Location: index.php?error=1");
    exit;
}

/**
 * Verifica la contraseña soportando tanto bcrypt (nuevo) como texto plano (legado).
 * Las contraseñas en texto plano deben migrarse con migrate_passwords.php.
 */
function verificar_password(string $input, string $stored): bool {
    // Si es un hash bcrypt válido
    if (strlen($stored) >= 60 && str_starts_with($stored, '$2y$')) {
        return password_verify($input, $stored);
    }
    // Compatibilidad legado: comparación de texto plano
    return $input === $stored;
}

// Buscar en cajeros
$stmt = $conexion->prepare("SELECT id_cajero, usu_cajero, pass_cajero FROM cajero WHERE usu_cajero = ? AND estado = 'Activo'");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($resultado && verificar_password($password, $resultado['pass_cajero'])) {
    session_regenerate_id(true);
    $_SESSION['usuario']    = $resultado['usu_cajero'];
    $_SESSION['id_usuario'] = $resultado['id_cajero'];
    $_SESSION['rol']        = 'cajero';
    header("Location: principal.php");
    exit;
}

// Buscar en mozos
$stmt = $conexion->prepare("SELECT id_mozo, usu_mozo, pass_mozo FROM mozo WHERE usu_mozo = ? AND estado = 'Activo'");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($resultado && verificar_password($password, $resultado['pass_mozo'])) {
    session_regenerate_id(true);
    $_SESSION['usuario']    = $resultado['usu_mozo'];
    $_SESSION['id_usuario'] = $resultado['id_mozo'];
    $_SESSION['rol']        = 'mozo';
    header("Location: principal.php");
    exit;
}

// Credenciales incorrectas
header("Location: index.php?error=1");
exit;
