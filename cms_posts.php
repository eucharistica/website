<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

$pdo = dbx();

/* ==== Ambil parameter GET (tanpa helper input()) ==== */
$q       = trim((string)($_GET['q'] ?? ''));
$status  = (string)($_GET['status'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$per     = min(50, max(5, (int)($_GET['per_page'] ?? 12)));
$off     = ($page - 1) * $per;

/* ==== Filter & Query ==== */
$where = [];
$bind  = [];

if ($q !== '') {
  $where[] = '(p.title LIKE :q OR p.slug LIKE :q OR p.excerpt LIKE :q)';
  $bind[':q'] = '%'.$q.'%';
}
if (in_array($status, ['draft','published'], true)) {
  $where[] = 'p.status = :s';
  $bind[':s'] = $status;
}
$wsql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* Hitung total */
$stc = $pdo->prepare("SELECT COUNT(*) FROM posts p $wsql");
foreach ($bind as $k=>$v) $stc->bindValue($k, $v);
$stc->execute();
$total = (int)$stc->fetchColumn();

/* Ambil data halaman */
$sql = "SELECT p.*, u.full_name AS author_name
        FROM posts p
        LEFT JOIN admin_users u ON u.id = p.author_id
        $wsql
        ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
        LIMIT :lim OFFSET :off";
$st = $pdo->prepare($sql);
foreach ($bind as $k=>$v) $st->bindValue($k, $v);
$st->bindValue(':lim', $per, PDO::PARAM_INT);
$st->bindValue(':off', $off, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

$pages = max(1, (int)ceil($total / $per));
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CMS Berita</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  .img-cover{width:100%;height:100%;object-fit:cover;display:block}
  .ratio-card{overflow:hidden;border-top-left-radius:.375rem;border-top-right-radius:.375rem}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="/dashboard.php"><i class="bi bi-journal-text"></i> CMS Berita</a>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-primary btn-sm" href="/cms_post_edit.php"><i class="bi bi-plus-lg"></i> Tambah</a>
      <a class="btn btn-outline-secondary btn-sm" href="/dashboard.php"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <?php if($ok=flash_get('ok')): ?><div class="alert alert-success py-2 small"><?=htmlspecialchars($ok)?></div><?php endif; ?>
  <?php if($er=flash_get('err')): ?><div class="alert alert-danger py-2 small"><?=htmlspecialchars($er)?></div><?php endif; ?>

  <form class="row g-2 mb-3" method="get">
    <div class="col-md-5">
      <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari judul/slug">
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">(Semua status)</option>
        <option value="draft" <?= $status==='draft'?'selected':'' ?>>Draft</option>
        <option value="published" <?= $status==='published'?'selected':'' ?>>Published</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="per_page" class="form-select">
        <?php foreach([6,12,18,24,36,48] as $pp): ?>
          <option value="<?= $pp ?>" <?= $pp==$per?'selected':'' ?>><?= $pp ?>/hal</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button>
    </div>
  </form>

  <div class="row g-3">
    <?php foreach($rows as $r): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <?php if(!empty($r['cover_path'])): ?>
            <div class="ratio ratio-16x9 ratio-card">
              <!-- Pakai <img> langsung agar cover lama tanpa varian tetap tampil -->
              <img src="/<?= htmlspecialchars(ltrim($r['cover_path'],'/')) ?>" class="img-cover" alt="">
            </div>
          <?php endif; ?>
          <div class="card-body">
            <div class="small text-secondary mb-1">
              <?= htmlspecialchars($r['status']) ?>
              <?php if(!empty($r['author_name'])): ?> • <?= htmlspecialchars($r['author_name']) ?><?php endif; ?>
              <?php if(!empty($r['published_at'])): ?>
                • <i class="bi bi-calendar-event"></i>
                <?= htmlspecialchars(date('d M Y', strtotime($r['published_at']))) ?>
              <?php endif; ?>
            </div>
            <div class="h6"><?= htmlspecialchars($r['title']) ?></div>
            <p class="small text-secondary mb-2"><?= htmlspecialchars($r['excerpt'] ?? '') ?></p>
            <div class="d-flex gap-2">
              <?php if(!empty($r['slug'])): ?>
                <a class="btn btn-sm btn-outline-primary" href="/berita/<?= htmlspecialchars($r['slug']) ?>" target="_blank">Lihat</a>
              <?php endif; ?>
              <a class="btn btn-sm btn-primary" href="/cms_post_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <a class="btn btn-sm btn-outline-danger" href="/cms_post_delete.php?id=<?= (int)$r['id'] ?>&csrf=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Hapus berita ini?')">Hapus</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if(empty($rows)): ?>
      <div class="text-center text-secondary">Tidak ada data.</div>
    <?php endif; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="small text-secondary">Total <?= $total ?> • Halaman <?= $page ?>/<?= $pages ?></div>
    <?php $base = function($p) use($q,$status,$per){ return '?q='.urlencode($q).'&status='.urlencode($status).'&per_page='.$per.'&page='.$p; }; ?>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm <?= $page<=1?'disabled':'' ?>" href="<?= $page<=1?'#':$base($page-1) ?>">&laquo; Prev</a>
      <a class="btn btn-outline-secondary btn-sm <?= $page>=$pages?'disabled':'' ?>" href="<?= $page>=$pages?'#':$base($page+1) ?>">Next &raquo;</a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
