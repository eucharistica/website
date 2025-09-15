<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin']); // hanya admin

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: users.php'); exit; }
if (!csrf_validate($_POST['csrf'] ?? '')) { flash_set('err','Sesi kedaluwarsa.'); header('Location: users.php'); exit; }

$rows = $_POST['rows'] ?? [];
if (!is_array($rows) || empty($rows)) { flash_set('err','Tidak ada data.'); header('Location: users.php'); exit; }

$allowedRoles = ['admin','editor','operator','patient'];
$myId = (int)($_SESSION['user']['id'] ?? 0);

$pdo = dbx();
$pdo->beginTransaction();
try {
  $st = $pdo->prepare("UPDATE admin_users SET role=:role, is_active=:act WHERE id=:id");

  foreach ($rows as $id => $data) {
    $id = (int)($data['id'] ?? $id);
    if ($id <= 0) continue;

    // Normalisasi
    $role = (string)($data['role'] ?? 'patient');
    if (!in_array($role, $allowedRoles, true)) $role = 'patient';
    $is_active = !empty($data['is_active']) ? 1 : 0;

    // Lindungi diri sendiri dari lockout tak sengaja
    if ($id === $myId) {
      // tidak boleh nonaktifkan diri sendiri atau menurunkan role sendiri
      $role = $_SESSION['user']['role']; 
      $is_active = 1;
    }

    $st->execute([':role'=>$role, ':act'=>$is_active, ':id'=>$id]);

    // Jika yang diubah adalah user saat ini, update sesi role-nya (misal dinaikkan dari editor -> admin oleh admin lain)
    if ($id === $myId) {
      $_SESSION['user']['role'] = $role;
    }
  }

  $pdo->commit();
  flash_set('ok', 'Perubahan pengguna berhasil disimpan.');
} catch (Throwable $e) {
  $pdo->rollBack();
  error_log('users_save error: '.$e->getMessage());
  flash_set('err', 'Gagal menyimpan: '.$e->getMessage());
}
header('Location: users.php');
