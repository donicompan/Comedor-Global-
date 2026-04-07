<?php
/**
 * pwa_head.php — Meta tags PWA compartidos.
 * Incluir DENTRO del <head> de cada página.
 */
?>
<link rel="manifest" href="manifest.php">
<meta name="theme-color" content="#1a1a2e">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars(($app ?? [])['nombre'] ?? 'Dony Software POS') ?>">
<link rel="apple-touch-icon" href="img/LogoCardon.jpeg">
