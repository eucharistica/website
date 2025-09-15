<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);
$pdo = dbx();

$id = (int)($_GET['id'] ?? 0);
$cat = ['id'=>0,'name'=>'','slug'=>'','status'=>1,'description'=>''];

/* Cek fitur kolom */
$hasStatus=false; $hasDesc=false; $hasTS=false;
try{$hasStatus=(bool)$pdo->query("SHOW COLUMNS FROM categories LIKE 'status'")->fetch();}catch(Throwable $e){}
try{$hasDesc=(bool)$pdo->query("SHOW COLUMNS FROM categories LIKE 'description'")->fetch();}catch(Throwable $e){}

if ($id>0) {
  $st=$pdo->prepare("SELECT * FROM categories WHERE id=:id LIMIT 1");
  $st->execute([':id'=>$id]);
  if ($row=$st->fetch()) $cat=$row;
}

$me = $_SESSION['user'] ?? [];
$isAdmin = (($me['role'] ?? '') === 'admin');
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $id? 'Edit' : 'Tambah' ?> Kategori • RSUD Matraman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="/cms_categories.php"><i class="bi bi-tags"></i> Kategori</a>
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

  <form method="post" action="/cms_category_save.php">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="mb-3">
          <label class="form-label">Nama Kategori</label>
          <input name="name" class="form-control" required value="<?= htmlspecialchars($cat['name']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Slug (URL)</label>
          <input name="slug" class="form-control" value="<?= htmlspecialchars($cat['slug']) ?>" placeholder="otomatis dari nama bila dikosongkan">
          <div class="form-text">Gunakan huruf kecil dan tanda minus (-).</div>
        </div>
        <?php if($hasDesc): ?>
          <div class="mb-3">
            <label class="form-label">Deskripsi (opsional)</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($cat['description'] ?? '') ?></textarea>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-4">
        <?php if($hasStatus): ?>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="1" <?= (int)($cat['status'] ?? 1)===1?'selected':'' ?>>Aktif</option>
              <option value="0" <?= (int)($cat['status'] ?? 1)===0?'selected':'' ?>>Nonaktif</option>
            </select>
          </div>
        <?php endif; ?>
        <div class="d-grid gap-2">
          <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
          <a class="btn btn-outline-secondary" href="/cms_categories.php"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
        </div>
      </div>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const nameEl=document.querySelector('input[name="name"]'),
        slugEl=document.querySelector('input[name="slug"]');
  nameEl?.addEventListener('blur',()=>{
    if(!slugEl.value.trim()){
      slugEl.value=nameEl.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    }
  });
</script>
</body>
</html>
