<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);
$pdo = dbx();

$id = (int)($_GET['id'] ?? 0);
$post = [
  'id'=>0,'title'=>'','slug'=>'','excerpt'=>'','body'=>'',
  'cover_path'=>'','status'=>'draft','published_at'=>null
];

if ($id > 0) {
  $st = $pdo->prepare("SELECT * FROM posts WHERE id=:id LIMIT 1");
  $st->execute([':id'=>$id]);
  if ($row = $st->fetch()) $post = $row;
}

/* -- Kategori (tahan banting bila kolom status belum ada) -- */
$hasStatus = false;
try {
  $chk = $pdo->query("SHOW COLUMNS FROM categories LIKE 'status'");
  $hasStatus = (bool)$chk->fetch();
} catch(Throwable $e) {}
$sqlCats = "SELECT id,name,slug".($hasStatus?",status":"")." FROM categories ".($hasStatus?"WHERE status=1 ":"")."ORDER BY name";
try {
  $cats = $pdo->query($sqlCats)->fetchAll();
} catch(Throwable $e) {
  // fallback kasar
  $cats = $pdo->query("SELECT id,name,slug FROM categories ORDER BY name")->fetchAll();
}

$selectedCatIds = [];
if (!empty($post['id'])) {
  $st = $pdo->prepare("SELECT category_id FROM post_categories WHERE post_id=:id");
  $st->execute([':id'=>$post['id']]);
  $selectedCatIds = array_map('intval', array_column($st->fetchAll(), 'category_id'));
}

/* -- Mode publish awal -- */
$publish_mode = 'now';
$published_at_val = '';
if ($post['status']==='published' && !empty($post['published_at'])) {
  $publish_mode = 'at';
  $dt = new DateTime($post['published_at'], new DateTimeZone('Asia/Jakarta'));
  $published_at_val = $dt->format('Y-m-d\TH:i');
}
$needCover = empty($post['cover_path']); // cover wajib kalau belum ada sama sekali
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $id? 'Edit' : 'Tambah' ?> Berita • RSUD Matraman</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.0.8/dist/trix.css">
  <script src="https://cdn.jsdelivr.net/npm/trix@2.0.8/dist/trix.umd.min.js"></script>
  <style>.trix-content img{max-width:100%;height:auto}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="/cms_posts.php"><i class="bi bi-journal-text"></i> CMS Berita</a>
    <div class="ms-auto d-flex gap-2">
      <?php if($id): ?>
        <a class="btn btn-outline-danger btn-sm" href="/cms_post_delete.php?id=<?= (int)$id ?>&csrf=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Hapus berita ini?')"><i class="bi bi-trash"></i> Hapus</a>
      <?php endif; ?>
      <a class="btn btn-outline-secondary btn-sm" href="/cms_posts.php"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <?php if($ok=flash_get('ok')): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($er=flash_get('err')): ?><div class="alert alert-danger  py-2 small"><?= htmlspecialchars($er) ?></div><?php endif; ?>

  <form method="post" action="/cms_post_save.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="mb-3">
          <label class="form-label">Judul</label>
          <input name="title" class="form-control" required value="<?= htmlspecialchars($post['title']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Slug (URL)</label>
          <input name="slug" class="form-control" value="<?= htmlspecialchars($post['slug']) ?>" placeholder="otomatis dari judul bila dikosongkan">
        </div>

        <div class="mb-3">
          <label class="form-label">Ringkasan</label>
          <textarea name="excerpt" class="form-control" rows="3"><?= htmlspecialchars($post['excerpt']) ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Isi</label>
          <input id="body" type="hidden" name="body" value="<?= htmlspecialchars($post['body']) ?>">
          <trix-editor input="body" class="trix-content" style="min-height:260px"></trix-editor>
          <div class="form-text">Upload di editor dimatikan. Gunakan kolom <b>Cover</b> di samping.</div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" id="status" class="form-select">
            <option value="draft"     <?= $post['status']==='draft'?'selected':'' ?>>Draft</option>
            <option value="published" <?= $post['status']==='published'?'selected':'' ?>>Published</option>
          </select>
          <div class="form-text">Published akan tampil di beranda/API.</div>
        </div>

        <!-- Waktu Publish -->
        <div id="publishTiming" class="mb-3 <?= $post['status']==='published' ? '' : 'd-none' ?>">
          <label class="form-label d-flex justify-content-between">
            <span>Waktu Publish</span>
            <span class="small text-secondary">WIB (UTC+07:00)</span>
          </label>

          <div class="form-check">
            <input class="form-check-input" type="radio" name="publish_mode" id="pub_now" value="now" <?= $publish_mode==='now'?'checked':'' ?>>
            <label class="form-check-label" for="pub_now">Publikasikan <b>sekarang</b> saat disimpan</label>
          </div>

          <div class="form-check mt-1">
            <input class="form-check-input" type="radio" name="publish_mode" id="pub_at" value="at" <?= $publish_mode==='at'?'checked':'' ?>>
            <label class="form-check-label" for="pub_at">Jadwalkan pada:</label>
          </div>

          <input type="datetime-local" class="form-control mt-2" name="published_at" id="published_at"
                 value="<?= htmlspecialchars($published_at_val) ?>"
                 <?= $publish_mode==='at' ? '' : 'disabled' ?>>
          <div class="form-text">Kosongkan untuk pakai waktu sekarang.</div>
        </div>

        <!-- Kategori -->
        <?php if ($cats): ?>
          <div class="mb-3">
            <label class="form-label">Kategori</label>
            <div class="border rounded p-2" style="max-height:180px;overflow:auto">
              <?php foreach ($cats as $c): $cid=(int)$c['id']; $checked = in_array($cid, $selectedCatIds, true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="cat[]" value="<?= $cid ?>" id="cat<?= $cid ?>" <?= $checked ?>>
                  <label class="form-check-label" for="cat<?= $cid ?>"><?= htmlspecialchars($c['name']) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="form-text">Centang untuk mengelompokkan berita.</div>
          </div>
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label">
            Cover <?= $needCover ? '<span class="text-danger">*</span>' : '' ?>
          </label>
          <?php if(!empty($post['cover_path'])): ?>
            <img src="/<?= htmlspecialchars($post['cover_path']) ?>" class="img-fluid rounded mb-2" alt="">
          <?php endif; ?>
          <input type="file" name="cover" class="form-control" accept="image/*" <?= $needCover ? 'required' : '' ?>>
          <div class="form-text">
            <?= $needCover
              ? 'Wajib unggah untuk berita baru/yang belum punya cover. JPG/PNG/WEBP, maks ~5MB.'
              : 'Opsional: kosongkan bila ingin mempertahankan cover saat ini.' ?>
          </div>
        </div>

        <div class="d-grid">
          <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
        </div>
      </div>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script <?= csp_script_attr() ?>>
  // Auto-slug
  const title=document.querySelector('input[name="title"]'),
        slug =document.querySelector('input[name="slug"]');
  title?.addEventListener('blur',()=>{
    if(!slug.value.trim()){
      slug.value=title.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    }
  });

  // Matikan upload file di Trix
  document.addEventListener('trix-file-accept', e => { e.preventDefault(); alert('Upload via editor dimatikan. Gunakan field Cover.'); });

  // Tampilkan/sembunyikan pengaturan waktu publish berdasarkan status
  const statusSel = document.getElementById('status');
  const timingBox = document.getElementById('publishTiming');
  function toggleTiming(){
    timingBox.classList.toggle('d-none', statusSel.value !== 'published');
  }
  statusSel.addEventListener('change', toggleTiming);

  // Enable/disable datetime-local berdasar radio
  const pubNow = document.getElementById('pub_now');
  const pubAt  = document.getElementById('pub_at');
  const pubInp = document.getElementById('published_at');
  function toggleDateInput(){ pubInp.disabled = !pubAt.checked; }
  pubNow.addEventListener('change', toggleDateInput);
  pubAt.addEventListener('change', toggleDateInput);

  // Inisialisasi tampilan saat load
  toggleTiming();
  toggleDateInput();
</script>
</body>
</html>
