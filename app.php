<?php
/**
 * app.php — Cargador principal de la aplicación.
 *
 * Reemplaza include('conexion.php') en todas las páginas.
 * Se encarga de:
 *   1. Conectar a la base de datos
 *   2. Cargar la configuración del restaurante ($app)
 *   3. Verificar el estado de la licencia/trial
 *   4. Redirigir a expirado.php si el trial terminó
 */

// 1. Conexión a la base de datos
require_once __DIR__ . '/conexion.php';

// 2. Funciones de licencia
require_once __DIR__ . '/licencia.php';

// 2b. Versión de la app
require_once __DIR__ . '/version.php';

// 3. Cargar configuración (solo una vez por request)
if (!isset($app)) {

    // Auto-crear tabla configuracion si no existe (migración automática para instalaciones existentes)
    $conexion->query("
        CREATE TABLE IF NOT EXISTS `configuracion` (
          `clave` varchar(50) NOT NULL,
          `valor` text NOT NULL,
          PRIMARY KEY (`clave`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");
    $conexion->query("
        INSERT IGNORE INTO `configuracion` (`clave`, `valor`) VALUES
        ('nombre',             'Mi Restaurante'),
        ('moneda',             '\$'),
        ('zona1_nombre',       'Salón'),
        ('zona1_hasta',        '8'),
        ('zona2_nombre',       'Patio'),
        ('logo_path',          ''),
        ('trial_inicio',       ''),
        ('licencia_clave',     ''),
        ('contacto_email',     'matias.4kfull@gmail.com'),
        ('contacto_whatsapp',  '3875755630')
    ");

    $app = [];
    $res = $conexion->query("SELECT clave, valor FROM configuracion");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $app[$row['clave']] = $row['valor'];
        }
    }

    // Valores por defecto si faltan claves
    $app += [
        'nombre'             => 'Mi Restaurante',
        'moneda'             => '$',
        'zona1_nombre'       => 'Salón',
        'zona1_hasta'        => '8',
        'zona2_nombre'       => 'Patio',
        'logo_path'          => '',
        'trial_inicio'       => '',
        'licencia_clave'     => '',
        'contacto_email'     => 'matias.4kfull@gmail.com',
        'contacto_whatsapp'  => '3875755630',
        'contacto_cod_pais'  => '54',   // código de país para WhatsApp (Argentina=54)
    ];
}

// 4. Verificación de licencia
$_app_pagina  = basename($_SERVER['SCRIPT_FILENAME']);
$_app_exentas = [
    'expirado.php',
    'activar_licencia.php',
    'logout.php',
    'index.php',
    'procesa.php',
    'migrate_passwords.php',
    'generar_licencia.php',
    'updater.php',
    'cocina.php',         // pantalla de cocina: no redirigir para no cortar el servicio
    'cancelar_pedido.php',
];

if (!in_array($_app_pagina, $_app_exentas)) {
    if (!isset($app['licencia'])) {
        $app['licencia'] = verificar_licencia($app, $conexion);
    }
    // 'expirada' = sin licencia válida ni gracia → bloquear
    // 'gracia'   = licencia vencida pero dentro de los 3 días de gracia → dejar pasar con aviso
    if ($app['licencia']['estado'] === 'expirada') {
        header("Location: expirado.php");
        exit;
    }
} else {
    if (!isset($app['licencia'])) {
        $app['licencia'] = ['estado' => 'exempt', 'dias_restantes' => null, 'hasta' => null, 'email' => null];
    }
}

unset($_app_pagina, $_app_exentas);

// 5. Migraciones automáticas de base de datos
// Se crea la tabla de control y se aplican los archivos pendientes en migrations/.
(function (mysqli $conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS `migraciones` (
        `id`          SMALLINT UNSIGNED NOT NULL,
        `aplicada_en` DATETIME          NOT NULL DEFAULT NOW(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $dir = __DIR__ . '/migrations';
    if (!is_dir($dir)) return;

    $archivos = glob($dir . '/[0-9]*.php');
    if (!$archivos) return;
    sort($archivos);

    // IDs ya aplicados
    $aplicadas = [];
    $res = $conn->query("SELECT id FROM migraciones");
    if ($res) { while ($r = $res->fetch_row()) $aplicadas[] = (int)$r[0]; }

    foreach ($archivos as $archivo) {
        $id = (int)basename($archivo, '.php');
        if (!$id || in_array($id, $aplicadas)) continue;

        $migration = include $archivo;
        // Soporta dos formatos:
        //   callable → fn(mysqli): void  (para lógica compleja, compatible con MySQL 8)
        //   array    → ['SQL...', ...]   (para sentencias simples)
        if (is_callable($migration)) {
            $migration($conn);
        } else {
            foreach ((array)$migration as $sql) {
                if (trim($sql)) $conn->query($sql);
            }
        }
        $stmt = $conn->prepare("INSERT IGNORE INTO migraciones (id) VALUES (?)");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
})($conexion);
