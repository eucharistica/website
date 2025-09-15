<?php
require_once __DIR__ . '/inc/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /login.php'); exit;
}

$csrf = $_POST['csrf'] ?? '';
if (!csrf_validate($csrf)) {
  flash_set('err','Sesi kadaluarsa, ulangi login.');
  header('Location: /login.php'); exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
  flash_set('err','Masukkan username dan password.');
  header('Location: /login.php'); exit;
}

$pdo = dbx();
$ip  = client_ip();

/* ===== Rate limit berbasis IP/identity (5 gagal / 10 menit) ===== */
if (!login_rate_allowed($pdo, $username, $ip)) {
  audit_log('auth.password.rate_limited','user',0,['identity'=>$username,'ip'=>$ip]);
  flash_set('err','Terlalu banyak percobaan login. Coba lagi beberapa menit.');
  header('Location: /login.php'); exit;
}

/* ===== Ambil user ===== */
$st = $pdo->prepare("SELECT * FROM admin_users WHERE username = :u LIMIT 1");
$st->execute([':u'=>$username]);
$user = $st->fetch();

if (!$user || (int)$user['is_active'] !== 1) {
  // catat attempt gagal
  login_rate_note($pdo, $username, $ip, false);
  audit_log('auth.password.fail','user',0,['reason'=>'not_found_or_inactive','identity'=>$username,'ip'=>$ip]);
  flash_set('err','Username atau password salah.');
  header('Location: /login.php'); exit;
}

/* ===== Cek lockout per akun (5 gagal -> 15 menit) ===== */
if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
  $mins = ceil((strtotime($user['locked_until']) - time()) / 60);
  login_rate_note($pdo, $username, $ip, false);
  audit_log('auth.password.fail','user',(int)$user['id'],['reason'=>'locked','minutes'=>$mins,'ip'=>$ip]);
  flash_set('err','Akun terkunci sementara. Coba lagi dalam ~'.$mins.' menit.');
  header('Location: /login.php'); exit;
}

/* ===== Verifikasi password ===== */
if (!password_verify($password, $user['password_hash'])) {
  $failed = (int)$user['failed_attempts'] + 1;
  $lock   = null;
  if ($failed >= 5) {
    $lock = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');
    $failed = 0; // reset counter setelah mengunci
  }
  $up = $pdo->prepare("UPDATE admin_users SET failed_attempts=:f, locked_until=:l WHERE id=:id");
  $up->execute([':f'=>$failed, ':l'=>$lock, ':id'=>$user['id']]);

  login_rate_note($pdo, $username, $ip, false);
  audit_log('auth.password.fail','user',(int)$user['id'],['reason'=>'bad_password','ip'=>$ip]);
  flash_set('err','Username atau password salah.');
  header('Location: /login.php'); exit;
}

/* ===== Sukses login ===== */
$pdo->prepare("UPDATE admin_users SET failed_attempts=0, locked_until=NULL, last_login=NOW() WHERE id=:id")
    ->execute([':id'=>$user['id']]);

login_user($user); // set session + regenerate id

login_rate_note($pdo, $username, $ip, true);
audit_log('auth.password.success','user',(int)$user['id'],['ip'=>$ip]);

/* ===== Gate MFA untuk admin (jika diaktifkan) ===== */
if (user_needs_mfa($user) && !is_mfa_ok()) {
  header('Location: /mfa_verify.php'); exit;
}

/* ===== Redirect sesuai role/intention ===== */
redirect_after_login();
