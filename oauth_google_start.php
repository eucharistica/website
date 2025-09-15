<?php
require_once __DIR__ . '/inc/oauth_google.php';
$intent = isset($_GET['intent']) && strtolower($_GET['intent'])==='staff' ? 'staff' : 'patient';
$_SESSION['oauth_intent'] = $intent; 
$url = google_build_auth_url();
header('Location: '.$url);
exit;
