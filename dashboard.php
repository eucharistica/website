<?php
// /dashboard.php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

$pdo = dbx();
$me  = $_SESSION['user'] ?? [];
$role = $me['role'] ?? '';
$isAdmin = ($role === 'admin');

function table_exists(PDO $pdo, string $name): bool { try{$pdo->query("SELECT 1 FROM `{$name}` LIMIT 1"); return true;}catch(Throwable $e){return false;} }
function count_safe(PDO $pdo, string $sql, array $bind=[]): ?int { try{$st=$pdo->prepare($sql); foreach($bind as $k=>$v)$st->bindValue($k,$v); $st->execute(); return (int)$st->fetchColumn(); }catch(Throwable $e){ return null; } }
function fetch_all_safe(PDO $pdo, string $sql, array $bind=[]): array { try{$st=$pdo->prepare($sql); foreach($bind as $k=>$v)$st->bindValue($k,$v); $st->execute(); return $st->fetchAll(); }catch(Throwable $e){ return []; } }

$posts_total      = table_exists($pdo,'posts') ? count_safe($pdo,"SELECT COUNT(*) FROM posts") : null;
$posts_pub        = table_exists($pdo,'posts') ? count_safe($pdo,"SELECT COUNT(*) FROM posts WHERE status='published' AND COALESCE(published_at,NOW())<=NOW()") : null;
$posts_draft      = table_exists($pdo,'posts') ? count_safe($pdo,"SELECT COUNT(*) FROM posts WHERE status='draft'") : null;
$posts_scheduled  = table_exists($pdo,'posts') ? count_safe($pdo,"SELECT COUNT(*) FROM posts WHERE status='published' AND published_at>NOW()") : null;
$categories_count = table_exists($pdo,'categories') ? count_safe($pdo,"SELECT COUNT(*) FROM categories") : null;
$services_count   = table_exists($pdo,'services')   ? count_safe($pdo,"SELECT COUNT(*) FROM services")   : null;
$users_count      = table_exists($pdo,'admin_users')? count_safe($pdo,"SELECT COUNT(*) FROM admin_users") : null;
$carousel_count   = table_exists($pdo,'carousel')   ? count_safe($pdo,"SELECT COUNT(*) FROM carousel WHERE status=1") : null;

$latest_posts = table_exists($pdo,'posts')
  ? fetch_all_safe($pdo,"SELECT id,title,slug,status,published_at,updated_at FROM posts ORDER BY COALESCE(published_at,updated_at) DESC, id DESC LIMIT 5")
  : [];

$latest_audit = ($isAdmin && table_exists($pdo,'audit_log'))
  ? fetch_all_safe($pdo,"SELECT a.created_at,a.action,a.target_type,a.target_id,COALESCE(u.full_name,u.email) AS user_name
                         FROM audit_log a LEFT JOIN admin_users u ON u.id=a.user_id
                         ORDER BY a.created_at DESC LIMIT 8")
  : [];
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Admin • RSUD Matraman</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>.card-stat .display-6{line-height:1}.card-hover:hover{box-shadow:0 10px 30px rgba(2,8,23,.08)}.badge-role{border:1px solid #e5e7eb}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="/dashboard.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/cms_posts.php"><i class="bi bi-journal-text me-1"></i>Berita</a></li>
        <li class="nav-item"><a class="nav-link" href="/cms_categories.php"><i class="bi bi-tags me-1"></i>Kategori</a></li>
        <li class="nav-item"><a class="nav-link" href="/cms_services.php"><i class="bi bi-collection me-1"></i>Layanan</a></li>
        <li class="nav-item"><a class="nav-link" href="/cms_carousel.php"><i class="bi bi-images me-1"></i>Carousel</a></li>
        <?php if ($isAdmin): ?><li class="nav-item"><a class="nav-link" href="/cms_audit_log.php"><i class="bi bi-clipboard-data me-1"></i>Audit Log</a></li><?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="/users.php"><i class="bi bi-people me-1"></i>Pengguna</a></li>
      </ul>
      <div class="ms-lg-3 d-flex align-items-center gap-2">
        <span class="badge bg-light text-dark badge-role">Login: <b><?= htmlspecialchars($me['full_name'] ?? $me['username'] ?? 'User') ?></b> (<?= htmlspecialchars($role) ?>)</span>
        <a class="btn btn-outline-secondary btn-sm" href="/"><i class="bi bi-house me-1"></i>Frontsite</a>
        <a class="btn btn-outline-danger btn-sm" href="/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
      </div>
    </div>
  </div>
</nav>

<div class="container py-4">

  <div class="d-flex flex-wrap gap-2 mb-3">
    <a href="/cms_post_edit.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Berita</a>
    <a href="/cms_category_edit.php" class="btn btn-outline-primary"><i class="bi bi-plus me-1"></i>Kategori Baru</a>
    <a href="/cms_services.php" class="btn btn-outline-secondary"><i class="bi bi-collection me-1"></i>Kelola Layanan</a>
    <a href="/cms_carousel.php" class="btn btn-outline-secondary"><i class="bi bi-images me-1"></i>Kelola Carousel</a>
    <?php if ($isAdmin): ?><a href="/cms_audit_log.php" class="btn btn-outline-secondary"><i class="bi bi-clipboard-data me-1"></i>Lihat Audit Log</a><?php endif; ?>
  </div>

  <div class="row g-3">
    <div class="col-md-6 col-lg-3">
      <div class="card card-stat card-hover h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div><div class="text-secondary small">Total Berita</div><div class="display-6 fw-bold"><?= $posts_total ?? '—' ?></div></div>
          <i class="bi bi-newspaper fs-1 text-secondary"></i>
        </div>
        <div class="mt-3 small text-secondary">
          <span class="me-3">Published: <b><?= $posts_pub ?? '—' ?></b></span>
          <span class="me-3">Draft: <b><?= $posts_draft ?? '—' ?></b></span>
          <span>Terjadwal: <b><?= $posts_scheduled ?? '—' ?></b></span>
        </div>
        <a class="stretched-link" href="/cms_posts.php"></a>
      </div></div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-stat card-hover h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div><div class="text-secondary small">Kategori</div><div class="display-6 fw-bold"><?= $categories_count ?? '—' ?></div></div>
          <i class="bi bi-tags fs-1 text-secondary"></i>
        </div>
        <div class="mt-3 small text-secondary">Kelola pengelompokan berita.</div>
        <a class="stretched-link" href="/cms_categories.php"></a>
      </div></div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-stat card-hover h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div><div class="text-secondary small">Layanan</div><div class="display-6 fw-bold"><?= $services_count ?? '—' ?></div></div>
          <i class="bi bi-collection fs-1 text-secondary"></i>
        </div>
        <div class="mt-3 small text-secondary">Tampilkan di frontsite.</div>
        <a class="stretched-link" href="/cms_services.php"></a>
      </div></div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-stat card-hover h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div><div class="text-secondary small">Carousel Aktif</div><div class="display-6 fw-bold"><?= $carousel_count ?? '—' ?></div></div>
          <i class="bi bi-images fs-1 text-secondary"></i>
        </div>
        <div class="mt-3 small text-secondary">Atur gambar slider beranda.</div>
        <a class="stretched-link" href="/cms_carousel.php"></a>
      </div></div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h2 class="h6 mb-0"><i class="bi bi-clock-history me-1"></i> Berita Terbaru</h2>
          <a href="/cms_posts.php" class="small">Lihat semua</a>
        </div>
        <div class="card-body">
          <?php if($latest_posts): ?>
            <ul class="list-group list-group-flush">
              <?php foreach($latest_posts as $p): ?>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
                  <div class="me-2">
                    <div class="fw-semibold"><a class="text-decoration-none" href="/cms_post_edit.php?id=<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a></div>
                    <div class="small text-secondary">
                      <?= htmlspecialchars($p['status']) ?>
                      <?php if($p['status']==='published' && !empty($p['published_at'])): ?>
                        • <?= date('Y-m-d H:i', strtotime($p['published_at'])) ?> WIB
                        <?= (strtotime($p['published_at'])>time())?'<span class="badge text-bg-warning ms-1">Terjadwal</span>':'' ?>
                      <?php endif; ?>
                    </div>
                  </div>
                  <a class="btn btn-sm btn-outline-secondary" href="/berita/<?= htmlspecialchars($p['slug']) ?>">Lihat</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="text-secondary small">Belum ada data atau tabel <code>posts</code> belum dibuat.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($isAdmin): // Sembunyikan seluruh kartu Aktivitas untuk non-admin ?>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h2 class="h6 mb-0"><i class="bi bi-activity me-1"></i> Aktivitas</h2>
          <a href="/cms_audit_log.php" class="small">Lihat log</a>
        </div>
        <div class="card-body">
          <?php if($latest_audit): ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Target</th></tr></thead>
                <tbody>
                  <?php foreach($latest_audit as $a): ?>
                    <tr>
                      <td class="small text-secondary"><?= date('Y-m-d H:i:s', strtotime($a['created_at'])) ?> WIB</td>
                      <td class="small"><?= htmlspecialchars($a['user_name'] ?? '—') ?></td>
                      <td class="small"><span class="badge text-bg-light border"><?= htmlspecialchars($a['action']) ?></span></td>
                      <td class="small"><?= htmlspecialchars($a['target_type']) ?> #<?= (int)$a['target_id'] ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-secondary small">Belum ada data atau tabel <code>audit_log</code> belum ada.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
