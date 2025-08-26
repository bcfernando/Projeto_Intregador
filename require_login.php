<?php
// require_login.php
require_once __DIR__ . '/includes/session.php';

// Bloqueia acesso de não logados
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php?msg=Faça+login');
    exit;
}

// Expira por inatividade
$now  = time();
$last = $_SESSION['last_activity'] ?? 0;

if ($last > 0 && ($now - $last) > INACTIVITY_LIMIT) {
    // Limpa sessão
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'] ?? false, $p['httponly'] ?? true);
    }
    session_destroy();
    header('Location: login.php?msg=Sessão+expirada');
    exit;
}

// Atualiza carimbo
$_SESSION['last_activity'] = $now;
