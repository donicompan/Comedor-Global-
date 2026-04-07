<?php
/**
 * index.php — Pantalla de login
 */
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/csrf.php';

// Auto-crear tabla configuracion si no existe
$conexion->query("
    CREATE TABLE IF NOT EXISTS `configuracion` (
      `clave` varchar(50) NOT NULL,
      `valor` text NOT NULL,
      PRIMARY KEY (`clave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
");
$conexion->query("
    INSERT IGNORE INTO `configuracion` (`clave`, `valor`) VALUES
    ('nombre','Mi Restaurante'),('moneda','\$'),('zona1_nombre','Salón'),
    ('zona1_hasta','8'),('zona2_nombre','Patio'),('logo_path',''),
    ('trial_inicio',''),('licencia_clave',''),
    ('contacto_email','matias.4kfull@gmail.com'),('contacto_whatsapp','3875755630')
");

// Cargar nombre y logo desde configuración
$app_login = ['nombre' => 'Mi Restaurante', 'logo_path' => ''];
$res = $conexion->query("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre','logo_path')");
if ($res) {
    while ($r = $res->fetch_assoc()) $app_login[$r['clave']] = $r['valor'];
}

$nombre_app = htmlspecialchars($app_login['nombre']);
$logo_path  = $app_login['logo_path'];
$error      = $_GET['error'] ?? '';
$lic_ok     = $_GET['lic']   ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $nombre_app ?> — Acceso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px 36px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }

        .logo-wrap {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo-wrap img {
            max-height: 80px;
            max-width: 200px;
            object-fit: contain;
            border-radius: 8px;
        }

        .logo-wrap .icon-fallback {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #0f3460, #16213e);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 8px;
        }

        .app-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }

        .app-subtitle {
            color: #888;
            font-size: 0.85rem;
            margin: 0;
        }

        .form-control {
            border-radius: 10px;
            padding: 11px 14px;
            border-color: #e0e0e0;
        }

        .form-control:focus { border-color: #0f3460; box-shadow: 0 0 0 3px rgba(15,52,96,0.12); }

        .btn-login {
            background: linear-gradient(135deg, #0f3460, #16213e);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: opacity 0.2s;
        }

        .btn-login:hover { opacity: 0.9; color: white; }

        .input-icon { position: relative; }
        .input-icon .bi {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            pointer-events: none;
        }
        .input-icon .form-control { padding-left: 38px; }
    </style>
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="#1a1a2e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Cardón POS">
    <link rel="apple-touch-icon" href="img/LogoCardon.jpeg">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js').catch(()=>{});</script>
</head>
<body>

<div class="login-card">

    <!-- Logo y nombre -->
    <div class="logo-wrap">
        <?php if ($logo_path && file_exists(__DIR__ . '/' . $logo_path)): ?>
            <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo">
            <p class="app-name mt-2"><?= $nombre_app ?></p>
        <?php else: ?>
            <div class="icon-fallback"><i class="bi bi-shop"></i></div>
            <p class="app-name"><?= $nombre_app ?></p>
        <?php endif; ?>
        <p class="app-subtitle">Sistema de gestión de pedidos</p>
    </div>

    <!-- Mensajes -->
    <?php if ($lic_ok === 'activada'): ?>
    <div class="alert alert-success py-2 small text-center mb-3">
        <i class="bi bi-check-circle-fill"></i> ¡Licencia activada correctamente!
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small text-center mb-3">
        <i class="bi bi-exclamation-triangle"></i> Usuario o contraseña incorrectos.
    </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form action="procesa.php" method="POST">
        <?= csrf_field() ?>
        <div class="mb-3 input-icon">
            <i class="bi bi-person"></i>
            <input type="text" name="user" class="form-control" placeholder="Usuario" required autofocus autocomplete="username">
        </div>
        <div class="mb-4 input-icon">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" class="form-control" placeholder="Contraseña" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
        </button>
    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
