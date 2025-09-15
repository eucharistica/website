<?php
require_once __DIR__ . '/inc/oauth_google.php';

if (!isset($_GET['state'], $_GET['code'])) {
  // Param tidak lengkap
  flash_set('err','Login Google gagal: parameter tidak lengkap.');
  header('Location: /login.php'); exit;
}

$state = (string)$_GET['state'];
$code  = (string)$_GET['code'];

$sess = $_SESSION['oauth_google'] ?? null;
if (!$sess || !hash_equals($sess['state'], $state) || (time() - ($sess['ts'] ?? 0) > 600)) {
  flash_set('err','Sesi login Google tidak valid/kedaluwarsa.');
  header('Location: /login.php'); exit;
}

try {
  // Tukar code -> token, ambil profil
  $tokens = google_exchange_code($code);
  $ui     = google_fetch_userinfo($tokens['access_token']);

  if (empty($ui['email_verified'])) {
    throw new Exception('Email Google belum terverifikasi.');
  }

  // Intent (admin/editor/patient) yang ditentukan saat klik tombol
  $intent      = $_SESSION['oauth_intent'] ?? 'patient';
  $defaultRole = determine_role_for_google((string)$ui['email'], (string)$intent);

  // Buat/temukan user dari Google
  $user = find_or_create_user_from_google($ui, $tokens, $defaultRole);

  // Login ke sistem
  login_user($user);

  // Bersihkan jejak oauth sementara
  unset($_SESSION['oauth_google'], $_SESSION['oauth_intent']);

  // === AUDIT: sukses login ===
  // new_user akan ikut bila fungsi find_or_create mengembalikan indikator tersebut
  $meta = [
    'method'    => 'google',
    'email'     => $user['email']      ?? ($ui['email'] ?? null),
    'full_name' => $user['full_name']  ?? ($ui['name']  ?? null),
    'role'      => $user['role']       ?? null,
    'intent'    => $intent,
  ];
  if (isset($user['is_new']))        { $meta['new_user'] = (bool)$user['is_new']; }
  elseif (isset($user['just_created'])) { $meta['new_user'] = (bool)$user['just_created']; }

  // Rate note
  $pdo = dbx();
  login_rate_note($pdo, $user['email'] ?? ($ui['email'] ?? 'oauth_google'), client_ip(), true);

  audit_log('auth.oauth.success','user',$user['id'] ?? 0,['provider'=>'google']);

  // MFA gate jika admin
  if (user_needs_mfa($user) && !is_mfa_ok()) {
    header('Location:/mfa_verify.php'); exit;
  }

  redirect_after_login();

} catch (Throwable $e) {
  error_log('OAuth Google error: '.$e->getMessage());
  $pdo = dbx(); login_rate_note($pdo, $ui['email'] ?? 'oauth_google', client_ip(), false);
  audit_log('auth.oauth.fail','user',0,['provider'=>'google','error'=>$e->getMessage()]);
  flash_set('err','Login Google gagal: '.$e->getMessage());
  header('Location: /login.php'); exit;
}
