<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);
$pdo = dbx();

$id = (int)($_GET['id'] ?? 0);
$row = ['id'=>0,'path'=>'','url'=>'','status'=>1];

if ($id>0) {
  $st=$pdo->prepare("SELECT * FROM carousel WHERE id=:id LIMIT 1");
  $st->execute([':id'=>$id]);
  if ($r=$st->fetch()) $row=$r;
}
$needPhoto = empty($row['path']);
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $id?'Edit':'Tambah' ?> Carousel • RSUD Matraman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>.preview{width:100%;height:260px;object-fit:cover;border-radius:.5rem;border:1px solid #e5e7eb}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="/cms_carousel.php"><i class="bi bi-images"></i> Carousel</a>
    <div class="ms-auto d-flex gap-2">
      <?php if($id): ?>
        <a class="btn btn-outline-danger btn-sm" href="/cms_carousel_delete.php?id=<?= (int)$id ?>&csrf=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Hapus item ini?')"><i class="bi bi-trash"></i> Hapus</a>
      <?php endif; ?>
      <a class="btn btn-outline-secondary btn-sm" href="/cms_carousel.php"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <?php if($ok=flash_get('ok')): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($er=flash_get('err')): ?><div class="alert alert-danger  py-2 small"><?= htmlspecialchars($er) ?></div><?php endif; ?>

  <form method="post" action="/cms_carousel_save.php" enctype="multipart/form-data" class="row g-4">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

    <div class="col-lg-7">
      <div class="mb-3">
        <label class="form-label">Foto <?= $needPhoto?'<span class="text-danger">*</span>':'' ?></label>
        <?php if(!empty($row['path'])): ?>
          <img class="preview mb-2" src="/<?= htmlspecialchars($row['path']) ?>" alt="">
        <?php endif; ?>
        <input type="file" name="photo" class="form-control" accept="image/*" <?= $needPhoto?'required':'' ?>>
        <div class="form-text">JPG/PNG/WEBP, ~5MB.</div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="mb-3">
        <label class="form-label">URL (opsional)</label>
        <input type="url" name="url" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($row['url']) ?>">
        <div class="form-text">Jika diisi, gambar pada beranda akan bisa diklik ke tautan ini.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="1" <?= (int)$row['status']===1?'selected':'' ?>>Aktif (tampil)</option>
          <option value="0" <?= (int)$row['status']===0?'selected':'' ?>>Nonaktif</option>
        </select>
      </div>
      <div class="d-grid">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
      </div>
    </div>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
