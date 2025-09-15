<?php
require_once __DIR__.'/inc/app.php';

$uid = (int)($_SESSION['user']['id'] ?? 0);
audit_log('auth.logout', 'user', $uid, [
  'from' => $_SERVER['HTTP_REFERER'] ?? null,
]);

// lanjut proses logout normal:
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

// redirect
header('Location: /'); exit;
