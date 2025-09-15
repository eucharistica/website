<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);
$pdo = dbx();

$id = (int)($_GET['id'] ?? 0);
$svc = ['id'=>0,'name'=>'','slug'=>'','excerpt'=>'','body'=>'','cover_path'=>'','status'=>1];

if ($id>0) {
  $st=$pdo->prepare("SELECT * FROM services WHERE id=:id LIMIT 1");
  $st->execute([':id'=>$id]);
  if ($row=$st->fetch()) $svc=$row;
}
$needCover = empty($svc['cover_path']);

$images=[];
if ($svc['id']) {
  $st=$pdo->prepare("SELECT * FROM service_images WHERE service_id=:id ORDER BY sort_order ASC, id ASC");
  $st->execute([':id'=>$svc['id']]);
  $images=$st->fetchAll();
}

$me = $_SESSION['user'] ?? [];
$isAdmin = (($me['role'] ?? '')==='admin');
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $id? 'Edit' : 'Tambah' ?> Layanan • RSUD Matraman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.0.8/dist/trix.css">
<script src="https://cdn.jsdelivr.net/npm/trix@2.0.8/dist/trix.umd.min.js"></script>
<style>.trix-content img{max-width:100%;height:auto}.thumb{width:100%;height:140px;object-fit:cover;border:1px solid #e5e7eb;border-radius:.5rem;background:#fff}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="/cms_services.php"><i class="bi bi-collection"></i> Layanan</a>
    <div class="ms-auto d-flex gap-2">
      <?php if($id): ?>
        <a class="btn btn-outline-danger btn-sm" href="/cms_service_delete.php?id=<?= (int)$id ?>&csrf=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Hapus layanan ini?')"><i class="bi bi-trash"></i> Hapus</a>
      <?php endif; ?>
      <a class="btn btn-outline-secondary btn-sm" href="/cms_services.php"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <?php if($ok=flash_get('ok')): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($er=flash_get('err')): ?><div class="alert alert-danger  py-2 small"><?= htmlspecialchars($er) ?></div><?php endif; ?>

  <form method="post" action="/cms_service_save.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)$svc['id'] ?>">

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="mb-3">
          <label class="form-label">Nama Layanan</label>
          <input name="name" class="form-control" required value="<?= htmlspecialchars($svc['name']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Slug (URL)</label>
          <input name="slug" class="form-control" value="<?= htmlspecialchars($svc['slug']) ?>" placeholder="otomatis dari nama bila kosong">
        </div>
        <div class="mb-3">
          <label class="form-label">Ringkasan</label>
          <textarea name="excerpt" class="form-control" rows="3"><?= htmlspecialchars($svc['excerpt']) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Isi Layanan</label>
          <input id="body" type="hidden" name="body" value="<?= htmlspecialchars($svc['body']) ?>">
          <trix-editor input="body" class="trix-content" style="min-height:260px"></trix-editor>
          <div class="form-text">Upload di editor dimatikan. Gunakan kolom <b>Cover</b> & <b>Galeri Foto</b>.</div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="1" <?= (int)$svc['status']===1?'selected':'' ?>>Aktif</option>
            <option value="0" <?= (int)$svc['status']===0?'selected':'' ?>>Nonaktif</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Cover <?= $needCover?'<span class="text-danger">*</span>':'' ?></label>
          <?php if(!empty($svc['cover_path'])): ?>
            <img class="img-fluid rounded mb-2" src="/<?= htmlspecialchars($svc['cover_path']) ?>" alt="">
          <?php endif; ?>
          <input type="file" name="cover" class="form-control" accept="image/*" <?= $needCover?'required':'' ?>>
          <div class="form-text"><?= $needCover?'Wajib unggah untuk layanan baru/yang belum punya cover.':'Opsional: biarkan kosong jika tidak ganti.' ?></div>
        </div>

        <div class="mb-3">
          <label class="form-label">Tambah Foto Galeri (boleh banyak)</label>
          <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
          <div class="form-text">JPG/PNG/WEBP, maks ~5MB/foto.</div>
        </div>

        <div class="d-grid">
          <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
        </div>
      </div>
    </div>

    <?php if($images): ?>
      <hr class="my-4">
      <h2 class="h6">Galeri Saat Ini</h2>
      <div id="gal" class="row g-3">
        <?php foreach($images as $im): ?>
          <div class="col-6 col-md-4 col-lg-3" data-id="<?= (int)$im['id'] ?>">
            <div class="card h-100">
              <img class="thumb card-img-top" src="/<?= htmlspecialchars($im['path']) ?>" alt="">
              <div class="card-body p-2">
                <input type="text" class="form-control form-control-sm" name="cap[<?= (int)$im['id'] ?>]" value="<?= htmlspecialchars($im['caption'] ?? '') ?>" placeholder="Caption (opsional)">
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-secondary">ID #<?= (int)$im['id'] ?></small>
                <a href="/cms_service_media_delete.php?id=<?= (int)$im['id'] ?>&csrf=<?= urlencode(csrf_token()) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus foto ini?')"><i class="bi bi-trash"></i></a>
              </div>
              <input type="hidden" name="order[]" value="<?= (int)$im['id'] ?>">
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="small text-secondary mt-2"><i class="bi bi-info-circle"></i> Seret kartu untuk ubah urutan. Caption ikut disimpan saat klik <b>Simpan</b>.</div>
    <?php endif; ?>

  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // slugify otomatis
  const nameEl=document.querySelector('input[name="name"]'),
        slugEl=document.querySelector('input[name="slug"]');
  nameEl?.addEventListener('blur',()=>{ if(!slugEl.value.trim()){ slugEl.value=nameEl.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,''); }});
  // disable upload pada Trix
  document.addEventListener('trix-file-accept', e=>{ e.preventDefault(); alert('Upload di editor dimatikan. Gunakan Cover/Galeri.'); });

  // Drag & drop reorder galeri (update urutan input hidden)
  const gal=document.getElementById('gal');
  if(gal){
    let drag=null;
    gal.querySelectorAll('.col-6').forEach(el=>{ el.draggable=true; el.classList.add('draggable'); });
    gal.addEventListener('dragstart',e=>{ const col=e.target.closest('.draggable'); if(col){ drag=col; col.classList.add('opacity-50'); }});
    gal.addEventListener('dragend',e=>{ if(drag){ drag.classList.remove('opacity-50'); drag=null; renumber(); }});
    gal.addEventListener('dragover',e=>{
      e.preventDefault();
      const after=[...gal.querySelectorAll('.draggable:not(.opacity-50)')].find(el=> e.clientY <= el.getBoundingClientRect().top + el.offsetHeight/2 );
      const dragging=gal.querySelector('.opacity-50')?.closest('.draggable');
      if(!dragging) return;
      if(after==null) gal.appendChild(dragging); else gal.insertBefore(dragging, after);
    });
    function renumber(){
      [...gal.querySelectorAll('.draggable')].forEach((el)=>{ const id=el.dataset.id; const hidden=el.querySelector('input[type="hidden"][name="order[]"]'); hidden.value=id; });
    }
  }
</script>
</body>
</html>
