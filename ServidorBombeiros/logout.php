<?php
require_once __DIR__ . '/includes/session.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'] ?? false, $p['httponly'] ?? true);
}
session_destroy();

header('Location: login.php?msg=Você+saiu+do+sistema');
exit;
