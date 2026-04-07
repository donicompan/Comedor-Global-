<?php
/**
 * licencia.php — Motor de verificación de licencias
 *
 * Sistema de trial de 14 días + activación por clave.
 * Formato de clave: base64(email|YYYY-MM-DD):hmac_sha256
 *
 * Para generar una clave para un cliente usar generar_licencia.php (herramienta del desarrollador).
 */

// APP_SECRET se define en config.php (incluido vía conexion.php → app.php).
// Si este archivo se carga directo (ej: generar_licencia.php), cargamos config.
if (!defined('APP_SECRET')) {
    require_once __DIR__ . '/config.php';
}
define('TRIAL_DIAS', 14);
define('GRACE_DIAS', 3);   // días de gracia tras vencer una licencia paga

/**
 * Verifica el estado de la licencia.
 * Retorna array con: estado ('activa'|'prueba'|'expirada'), dias_restantes, hasta, email.
 */
function verificar_licencia(array &$app, mysqli $conn): array {
    // 1. ¿Hay una clave de licencia guardada?
    $clave = $app['licencia_clave'] ?? '';
    if ($clave) {
        $resultado = validar_clave_licencia($clave);
        if ($resultado['valida']) {
            // Calcular días restantes de la licencia activa
            $hoy   = new DateTime();
            $hasta = new DateTime($resultado['hasta']);
            $diff  = (int)$hoy->diff($hasta)->days;
            $dias_restantes = ($hoy <= $hasta) ? $diff : 0;
            return [
                'estado'         => $resultado['en_gracia'] ? 'gracia' : 'activa',
                'dias_restantes' => $dias_restantes,
                'hasta'          => $resultado['hasta'],
                'email'          => $resultado['email'],
            ];
        }
        // Clave inválida o expirada → tratamos como sin licencia
    }

    // 2. Modo trial — verificar/inicializar fecha de inicio
    $inicio = $app['trial_inicio'] ?? '';
    if (!$inicio) {
        $hoy = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor) VALUES ('trial_inicio', ?) ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->bind_param("ss", $hoy, $hoy);
        $stmt->execute();
        $stmt->close();
        $app['trial_inicio'] = $hoy;
        $inicio = $hoy;
    }

    $dias_pasados    = (int)(new DateTime())->diff(new DateTime($inicio))->days;
    $dias_restantes  = TRIAL_DIAS - $dias_pasados;

    if ($dias_restantes <= 0) {
        return ['estado' => 'expirada', 'dias_restantes' => 0, 'hasta' => null, 'email' => null];
    }

    return ['estado' => 'prueba', 'dias_restantes' => $dias_restantes, 'hasta' => null, 'email' => null];
}

/**
 * Valida una clave de licencia.
 * Retorna array con: valida (bool), hasta (string|null), email (string|null).
 */
function validar_clave_licencia(string $key): array {
    $invalida = ['valida' => false, 'hasta' => null, 'email' => null];

    $partes = explode(':', $key, 2);
    if (count($partes) !== 2) return $invalida;

    [$payload_b64, $hmac_recibido] = $partes;

    // Decodificar payload
    $payload = base64_decode($payload_b64, true);
    if ($payload === false) return $invalida;

    // Verificar HMAC
    $hmac_esperado = hash_hmac('sha256', $payload, APP_SECRET);
    if (!hash_equals($hmac_esperado, $hmac_recibido)) return $invalida;

    // Parsear payload: email|YYYY-MM-DD
    $datos = explode('|', $payload, 2);
    if (count($datos) !== 2) return $invalida;

    [$email, $hasta] = $datos;

    // Verificar vigencia
    $hoy = date('Y-m-d');
    if ($hoy > $hasta) {
        // Período de gracia: GRACE_DIAS días después del vencimiento
        $hasta_gracia = date('Y-m-d', strtotime($hasta . ' +' . GRACE_DIAS . ' days'));
        if ($hoy <= $hasta_gracia) {
            return ['valida' => true, 'en_gracia' => true, 'hasta' => $hasta, 'email' => $email];
        }
        return ['valida' => false, 'en_gracia' => false, 'expirada' => true, 'hasta' => $hasta, 'email' => $email];
    }

    return ['valida' => true, 'en_gracia' => false, 'hasta' => $hasta, 'email' => $email];
}

/**
 * Guarda una clave de licencia en la BD.
 */
function guardar_clave_licencia(string $clave, mysqli $conn): bool {
    $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor) VALUES ('licencia_clave', ?) ON DUPLICATE KEY UPDATE valor = ?");
    $stmt->bind_param("ss", $clave, $clave);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
