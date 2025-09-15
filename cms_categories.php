<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);
$pdo = dbx();

$me = $_SESSION['user'] ?? [];
$isAdmin = (($me['role'] ?? '') === 'admin');

$q = trim($_GET['q'] ?? '');

/* Deteksi kolom status */
$hasStatus=false;
try{$chk=$pdo->query("SHOW COLUMNS FROM categories LIKE 'status'"); $hasStatus=(bool)$chk->fetch();}catch(Throwable $e){}

$where=[]; $bind=[];
if ($q!==''){ $where[]='(c.name LIKE :q OR c.slug LIKE :q OR '.($hasStatus?'c.description LIKE :q':'c.slug LIKE :q').')'; $bind[':q']='%'.$q.'%'; }
$wsql=$where?('WHERE '.implode(' AND ',$where)):'';

$sql="
  SELECT c.*,
         (SELECT COUNT(*) FROM post_categories pc WHERE pc.category_id=c.id) AS posts_count
  FROM categories c
  $wsql
  ORDER BY ".($hasStatus?'c.status DESC, ':'')." c.name ASC
";
$st=$pdo->prepare($sql);
foreach($bind as $k=>$v)$st->bindValue($k,$v);
$st->execute(); $rows=$st->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kategori Berita • RSUD Matraman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="/dashboard.php"><i class="bi bi-hospital"></i> Dashboard</a>
    <div class="ms-auto d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="/cms_posts.php"><i class="bi bi-journal-text"></i> Berita</a>
      <a class="btn btn-primary btn-sm" href="/cms_categories.php"><i class="bi bi-tags"></i> Kategori</a>
      <a class="btn btn-outline-secondary btn-sm" href="/cms_services.php"><i class="bi bi-collection"></i> Layanan</a>
      <?php if ($isAdmin): ?><a class="btn btn-outline-secondary btn-sm" href="/cms_audit_log.php"><i class="bi bi-clipboard-data"></i> Audit Log</a><?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">
  <?php if($ok=flash_get('ok')): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($er=flash_get('err')): ?><div class="alert alert-danger  py-2 small"><?= htmlspecialchars($er) ?></div><?php endif; ?>

  <div class="d-flex justify-content-between align-items-end mb-3">
    <form class="row g-2" method="get">
      <div class="col-auto"><input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama/slug"></div>
      <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button></div>
    </form>
    <a class="btn btn-primary" href="/cms_category_edit.php"><i class="bi bi-plus-lg"></i> Tambah Kategori</a>
  </div>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr>
        <th style="width:60px">ID</th>
        <th>Nama</th>
        <th>Slug</th>
        <?php if($hasStatus): ?><th class="text-center" style="width:120px">Status</th><?php endif; ?>
        <th class="text-center" style="width:120px"># Post</th>
        <th style="width:220px">Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td class="text-secondary"><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td class="small text-secondary"><?= htmlspecialchars($r['slug']) ?></td>
          <?php if($hasStatus): ?>
            <td class="text-center"><?= ((int)($r['status']??1)===1?'<span class="badge text-bg-success">Aktif</span>':'<span class="badge text-bg-secondary">Nonaktif</span>') ?></td>
          <?php endif; ?>
          <td class="text-center"><?= (int)$r['posts_count'] ?></td>
          <td>
            <a class="btn btn-sm btn-primary" href="/cms_category_edit.php?id=<?= (int)$r['id'] ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <a class="btn btn-sm btn-outline-danger" href="/cms_category_delete.php?id=<?= (int)$r['id'] ?>&csrf=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Hapus kategori ini? Relasi post akan terlepas.')"><i class="bi bi-trash"></i> Hapus</a>
          </td>
        </tr>
      <?php endforeach; if(empty($rows)): ?>
        <tr><td colspan="<?= $hasStatus?6:5 ?>" class="text-center text-secondary">Tidak ada kategori.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
