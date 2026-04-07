<?php
// ══════════════════════════════════════════════════════════════════════
// config.php — Configuración de instalación
// ══════════════════════════════════════════════════════════════════════
// 1. Copiá este archivo como config.php
// 2. Completá con los datos reales del cliente
// 3. NUNCA subas config.php a control de versiones (.gitignore ya lo excluye)

// ── Base de datos ─────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario_mysql');
define('DB_PASS', 'tu_contraseña_mysql');
define('DB_NAME', 'comedor');

// ── Licencias ─────────────────────────────────────────────────────────
// Clave secreta para firmar licencias — pedísela al desarrollador.
// NO modificar después de emitir claves (invalidaría todas las licencias).
define('APP_SECRET', 'REEMPLAZAR_CON_LA_CLAVE_SECRETA');

// ── Actualizaciones automáticas ───────────────────────────────────────
// URL del manifest JSON que el sistema consulta para detectar actualizaciones.
// El desarrollador provee esta URL junto con cada instalación.
define('UPDATE_MANIFEST_URL', '');   // ← completar con la URL que te dé el desarrollador

// Hosts autorizados para descargar actualizaciones (no modificar).
define('UPDATE_ALLOWED_HOSTS', ['github.com', 'raw.githubusercontent.com']);
