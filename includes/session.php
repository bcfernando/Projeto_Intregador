<?php
// includes/session.php
declare(strict_types=1);

// Segurança e política de sessão
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
// Se usar HTTPS, ative também:
// ini_set('session.cookie_secure', '1');

// 60 minutos
ini_set('session.gc_maxlifetime', '3600');

session_name('app_session');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_lifetime' => 0, // expira ao fechar o navegador
        'read_and_close'  => false,
    ]);
}

if (!defined('INACTIVITY_LIMIT')) {
    define('INACTIVITY_LIMIT', 3600); // 60 minutos
}
