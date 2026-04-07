<?php
/**
 * expirado.php — Pantalla de licencia vencida / activación
 *
 * Se muestra cuando el período de prueba termina.
 * Permite ingresar una clave de licencia para reactivar.
 */
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/licencia.php';

// Auto-crear tabla configuracion si no existe
$conexion->query("CREATE TABLE IF NOT EXISTS `configuracion` (`clave` varchar(50) NOT NULL, `valor` text NOT NULL, PRIMARY KEY (`clave`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
$conexion->query("INSERT IGNORE INTO `configuracion` (`clave`,`valor`) VALUES ('nombre','Mi Restaurante'),('logo_path',''),('moneda','\$'),('zona1_nombre','Salón'),('zona1_hasta','8'),('zona2_nombre','Patio'),('trial_inicio',''),('licencia_clave',''),('contacto_email','matias.4kfull@gmail.com'),('contacto_whatsapp','3875755630')");

// Cargar nombre y logo desde configuración
$app = [];
$res = $conexion->query("SELECT clave, valor FROM configuracion");
if ($res) {
    while ($r = $res->fetch_assoc()) $app[$r['clave']] = $r['valor'];
}
$app += [
    'nombre'            => 'Mi Restaurante',
    'logo_path'         => '',
    'moneda'            => '$',
    'contacto_email'    => 'matias.4kfull@gmail.com',
    'contacto_whatsapp' => '3875755630',
];
$contacto_email    = htmlspecialchars($app['contacto_email']);
$contacto_wa       = preg_replace('/\D/', '', $app['contacto_whatsapp']);
$contacto_cod_pais = preg_replace('/\D/', '', $app['contacto_cod_pais'] ?? '54');

// Verificar si ya tienen licencia activa (caso: ya activaron pero llegaron aquí igual)
$lic_clave = $app['licencia_clave'] ?? '';
if ($lic_clave) {
    $r = validar_clave_licencia($lic_clave);
    if ($r['valida']) {
        header("Location: principal.php");
        exit;
    }
}

$error  = $_GET['error']  ?? '';
$errores = [
    'clave_vacia'   => 'Ingresá una clave de licencia.',
    'clave_invalida'=> 'La clave ingresada no es válida o está vencida.',
];

$nombre = htmlspecialchars($app['nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licencia vencida — <?= $nombre ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .exp-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            max-width: 960px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }

        .exp-header {
            background: linear-gradient(135deg, #e63946, #c1121f);
            color: white;
            padding: 40px 48px;
            text-align: center;
        }

        .exp-header .icon { font-size: 4rem; margin-bottom: 12px; }
        .exp-header h1   { font-size: 2rem; font-weight: 700; margin-bottom: 6px; }
        .exp-header p    { opacity: 0.9; font-size: 1.05rem; margin: 0; }

        .exp-body { padding: 40px 48px; }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .feature-item .bi {
            font-size: 1.2rem;
            color: #2a9d8f;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .activate-box {
            background: #f8f9fa;
            border-radius: 14px;
            padding: 28px;
            border: 2px dashed #dee2e6;
        }

        .activate-box.has-error { border-color: #dc3545; }

        .btn-activate {
            background: linear-gradient(135deg, #2a9d8f, #1d7a70);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 1rem;
            width: 100%;
            transition: opacity 0.2s;
        }

        .btn-activate:hover { opacity: 0.9; color: white; }

        .contact-card {
            background: #f0f7ff;
            border-radius: 12px;
            padding: 20px 24px;
            border-left: 4px solid #0d6efd;
        }

        @media (max-width: 576px) {
            .exp-header, .exp-body { padding: 28px 24px; }
            .exp-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="exp-card">

    <!-- Header -->
    <div class="exp-header">
        <?php if ($app['logo_path'] && file_exists(__DIR__ . '/' . $app['logo_path'])): ?>
        <div class="mb-3">
            <img src="<?= htmlspecialchars($app['logo_path']) ?>" alt="Logo"
                 style="max-height: 70px; max-width: 200px; filter: brightness(0) invert(1);">
        </div>
        <?php else: ?>
        <div class="icon"><i class="bi bi-shop"></i></div>
        <?php endif; ?>
        <h1><?= $nombre ?></h1>
        <p><i class="bi bi-clock-history"></i> El período de prueba ha finalizado</p>
    </div>

    <!-- Body -->
    <div class="exp-body">
        <div class="row g-5">

            <!-- Columna izquierda: beneficios -->
            <div class="col-lg-6">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-stars text-warning"></i>
                    ¿Qué incluye la licencia?
                </h5>

                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Uso ilimitado</strong><br><small class="text-muted">Sin restricciones de pedidos, mesas o usuarios</small></div>
                </div>
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Panel de mesas en tiempo real</strong><br><small class="text-muted">Salón, patio y cualquier zona que configures</small></div>
                </div>
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Pantalla de cocina para TV</strong><br><small class="text-muted">Pedidos ordenados por tiempo de espera</small></div>
                </div>
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Reportes de ventas</strong><br><small class="text-muted">Por día, semana y mes con ranking de productos</small></div>
                </div>
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Gestión de personal</strong><br><small class="text-muted">Cajeros y mozos con roles diferenciados</small></div>
                </div>
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Logo y nombre personalizado</strong><br><small class="text-muted">Configurá la identidad de tu restaurante</small></div>
                </div>
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Actualizaciones incluidas</strong><br><small class="text-muted">Durante la vigencia de tu licencia</small></div>
                </div>

                <hr class="my-4">

                <!-- Precios -->
                <div class="contact-card mb-3" style="background:#f0fff4; border-left-color:#2a9d8f;">
                    <h6 class="fw-bold mb-2"><i class="bi bi-tag-fill text-success"></i> Planes disponibles</h6>
                    <div class="row g-2 text-center small">
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fw-bold text-muted">Mensual</div>
                                <div class="fs-6 fw-bold text-success">Consultá</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 border-success">
                                <div class="fw-bold text-success">Anual</div>
                                <div class="fs-6 fw-bold text-success">Consultá</div>
                                <div style="font-size:.65rem;" class="text-success">2 meses gratis</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fw-bold text-muted">Vitalicia</div>
                                <div class="fs-6 fw-bold text-success">Consultá</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contacto -->
                <div class="contact-card">
                    <h6 class="fw-bold mb-2"><i class="bi bi-headset text-primary"></i> ¿Cómo obtener tu licencia?</h6>
                    <p class="small text-muted mb-2">Contactanos y en menos de 24 horas tenés tu clave.</p>
                    <div class="d-flex flex-column gap-2">
                        <a href="mailto:<?= $contacto_email ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-envelope"></i> <?= $contacto_email ?>
                        </a>
                        <a href="https://wa.me/<?= $contacto_cod_pais . $contacto_wa ?>" target="_blank" class="btn btn-sm btn-success">
                            <i class="bi bi-whatsapp"></i> WhatsApp — <?= htmlspecialchars($app['contacto_whatsapp']) ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: activación -->
            <div class="col-lg-6">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-key-fill text-success"></i>
                    Activar licencia
                </h5>

                <?php if ($error && isset($errores[$error])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?= $errores[$error] ?>
                </div>
                <?php endif; ?>

                <div class="activate-box <?= $error ? 'has-error' : '' ?>">
                    <p class="text-muted small mb-3">
                        Si ya compraste tu licencia, ingresá la clave que te enviamos:
                    </p>
                    <form action="activar_licencia.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Clave de licencia</label>
                            <textarea name="licencia_clave" class="form-control font-monospace"
                                      rows="4" placeholder="Pegá tu clave aquí..." required
                                      style="font-size: 0.75rem; resize: none;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-activate">
                            <i class="bi bi-check-circle"></i> Activar ahora
                        </button>
                    </form>
                </div>

                <div class="alert alert-info mt-4 small">
                    <i class="bi bi-info-circle"></i>
                    <strong>¿Ya tenés licencia activa?</strong>
                    Si ya activaste y ves esta pantalla, contactanos para verificar tu clave.
                </div>

                <div class="text-center mt-3">
                    <a href="logout.php" class="text-muted small">
                        <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
