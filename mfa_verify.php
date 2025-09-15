<?php
require_once __DIR__.'/inc/app.php';
require_role(['admin','editor']); // admin yang butuh MFA; editor bisa dilewati jika tidak diaktifkan
$pdo = dbx();
$me = $_SESSION['user'] ?? [];
if (!user_needs_mfa($me)) { header('Location:/dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_validate($_POST['csrf'] ?? '')) { flash_set('err','Sesi habis'); header('Location:/mfa_verify.php'); exit; }
  $code = trim($_POST['code'] ?? '');
  $secret = (string)($me['mfa_secret'] ?? '');
  if ($secret && mfa_verify($secret, $code)) {
    $_SESSION['mfa_ok'] = true;
    audit_log('mfa.verify','user',$me['id'],null);
    header('Location:/dashboard.php'); exit;
  }
  flash_set('err','Kode salah, coba lagi.');
  header('Location:/mfa_verify.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verifikasi MFA • RSUD Matraman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="container py-4">
  <h1 class="h5 mb-3">Verifikasi MFA</h1>
  <p class="text-secondary">Masukkan 6 digit kode dari aplikasi Authenticator.</p>
  <?php if($m=flash_get('err')): ?><div class="alert alert-danger small"><?=htmlspecialchars($m)?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div class="mb-2"><input name="code" class="form-control" inputmode="numeric" maxlength="6" required placeholder="######"></div>
    <button class="btn btn-primary">Verifikasi</button>
    <a class="btn btn-outline-secondary" href="/logout.php">Keluar</a>
  </form>
</body></html>
