<?php
/**
 * Helpers de protección CSRF.
 * Incluir en cualquier página que procese formularios POST.
 */

/**
 * Genera (o reutiliza) un token CSRF para la sesión actual.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Devuelve el campo hidden listo para insertar en un <form>.
 */
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Valida el token enviado en $_POST['_csrf'].
 * Muere con error 403 si no coincide.
 */
function csrf_validate(): void {
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('Solicitud inválida (token CSRF incorrecto). Recargá la página e intentá de nuevo.');
    }
}
