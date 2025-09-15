<?php /* ===== portal.php (Dashboard Pasien) ===== */ ?>
<?php
require_once __DIR__ . '/inc/app.php';
require_login();
if (is_staff_role(current_user_role())) { header('Location: dashboard.php'); exit; }
$u = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Portal Pasien • RSUD Matraman</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#">
      <img src="assets/logo-rsud.png" alt="RSUD Matraman" style="height:28px" />
      <span class="fw-semibold">Portal Pasien</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <?php if (!empty($u['avatar_url'])): ?>
        <img src="<?= htmlspecialchars($u['avatar_url']) ?>" class="rounded-circle" style="width:28px;height:28px;object-fit:cover" alt="">
      <?php endif; ?>
      <span class="small text-secondary d-none d-md-inline">Hai, <?= htmlspecialchars($u['full_name']) ?></span>
      <a class="btn btn-outline-secondary btn-sm" href="logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card h-100"><div class="card-body">
        <div class="h6 mb-2">Profil</div>
        <div class="small text-secondary">Nama: <?= htmlspecialchars($u['full_name']) ?></div>
        <div class="small text-secondary">Email: <?= htmlspecialchars($u['email'] ?? '') ?></div>
        <div class="small text-secondary">Role : <?= htmlspecialchars($u['role']) ?></div>
      </div></div>
    </div>
    <div class="col-12 col-lg-8">
      <div class="card h-100"><div class="card-body">
        <div class="h6 mb-2">Menu Cepat</div>
        <div class="d-grid gap-2">
          <a class="btn btn-outline-primary" href="#jadwal"><i class="bi bi-calendar-week me-1"></i> Cek Jadwal Dokter</a>
          <a class="btn btn-outline-primary disabled" href="#"><i class="bi bi-heart-pulse me-1"></i> Riwayat Kunjungan (coming soon)</a>
          <a class="btn btn-outline-primary disabled" href="#"><i class="bi bi-file-medical me-1"></i> Hasil Pemeriksaan (coming soon)</a>
        </div>
      </div></div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
