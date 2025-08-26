<?php
require_once __DIR__ . '/includes/session.php';

if (!empty($_SESSION['usuario_id'])) {
    $_SESSION['last_activity'] = time();
    http_response_code(204);
    exit;
}
http_response_code(401);
