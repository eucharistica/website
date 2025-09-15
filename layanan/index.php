<?php
require_once __DIR__ . '/../inc/app.php';
$pdo = dbx();

// Router sederhana: /layanan  => list, /layanan/{slug} => detail
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path,'/'));
$slug = $parts[0]==='layanan' && !empty($parts[1]) ? $parts[1] : '';

if ($slug === '') {
  // ==== LIST ====
  $rows = $pdo->query("SELECT id,name,slug,excerpt,cover_path FROM services WHERE status=1 ORDER BY sort_order ASC, name ASC")->fetchAll();
  ?>
  <!DOCTYPE html>
  <html lang="id" data-bs-theme="light">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Layanan • RSUD Matraman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
      .ratio-card{overflow:hidden;border-radius:.5rem;border:1px solid #e5e7eb;background:#fff}
      .img-cover{width:100%;height:100%;object-fit:cover;display:block}
    </style>
    <style>
      /* ... CSS yang sudah ada ... */
      .gallery-img{cursor:zoom-in}
      .modal-lightbox .modal-content{background:transparent;border:0;box-shadow:none}
      .modal-lightbox .btn-nav{position:absolute;top:50%;transform:translateY(-50%);z-index:1056}
      .modal-lightbox .btn-nav.start{left:.5rem}
      .modal-lightbox .btn-nav.end{right:.5rem}
      .modal-lightbox .caption{color:#fff;text-shadow:0 1px 2px rgba(0,0,0,.6)}
      .modal-lightbox .btn-close-bottom{position:absolute;right:.75rem;bottom:.75rem;z-index:1056}
    </style>

  </head>
  <body>
  <?php include $_SERVER['DOCUMENT_ROOT'].'/inc/header.php'; ?>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-3">
      <div>
        <h1 class="h4 mb-1">Semua Layanan</h1>
        <p class="text-secondary mb-0">Pilih layanan untuk melihat detail & foto fasilitas.</p>
      </div>
    </div>

    <div class="row g-3 g-lg-4">
      <?php foreach($rows as $s): ?>
        <div class="col-md-6 col-lg-4">
          <article class="card h-100">
            <div class="ratio ratio-16x9 ratio-card">
              <?php if(!empty($s['cover_path'])): ?>
                <img class="img-cover" src="/<?= htmlspecialchars($s['cover_path']) ?>" alt="<?= htmlspecialchars($s['name']) ?>">
              <?php else: ?>
                <div class="bg-light"></div>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <a href="/layanan/<?= htmlspecialchars($s['slug']) ?>" class="h6 d-block mb-1 link-dark text-decoration-none"><?= htmlspecialchars($s['name']) ?></a>
              <p class="card-text small text-secondary mb-2"><?= htmlspecialchars(mb_strimwidth((string)$s['excerpt'],0,140,'…','UTF-8')) ?></p>
              <a class="btn btn-sm btn-outline-primary" href="/layanan/<?= htmlspecialchars($s['slug']) ?>">Lihat Detail</a>
            </div>
          </article>
        </div>
      <?php endforeach; if(empty($rows)): ?>
        <div class="col-12 text-center text-secondary">Belum ada layanan.</div>
      <?php endif; ?>
    </div>
  </div>
  <?php include $_SERVER['DOCUMENT_ROOT'].'/inc/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
  </html>
  <?php
  exit;
}

// ==== DETAIL SLUG ====
$st=$pdo->prepare("SELECT * FROM services WHERE slug=:s AND status=1 LIMIT 1");
$st->execute([':s'=>$slug]);
$svc=$st->fetch();
if(!$svc){ http_response_code(404); echo '<!DOCTYPE html><meta charset="utf-8"><title>404</title>Not found'; exit; }

$imgsSt=$pdo->prepare("SELECT * FROM service_images WHERE service_id=:id ORDER BY sort_order ASC, id ASC");
$imgsSt->execute([':id'=>$svc['id']]); $imgs=$imgsSt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($svc['name']) ?> • RSUD Matraman</title>
  <meta name="description" content="<?= htmlspecialchars(mb_strimwidth((string)$svc['excerpt'],0,150,'…','UTF-8')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .hero{background:linear-gradient(180deg,#fff,#f8fafc);padding:2rem 0}
    .hero .cover{border-radius:1rem;box-shadow:0 10px 30px rgba(2,8,23,.06);object-fit:cover;width:100%;height:100%}
    .gal .card img{height:160px;object-fit:cover}
    @media(min-width:992px){.gal .card img{height:200px}}
    .trix-content img{max-width:100%;height:auto}
  </style>
</head>
<body>
<?php include $_SERVER['DOCUMENT_ROOT'].'/inc/header.php'; ?>

<section class="hero">
  <div class="container">
    <div class="row g-3 align-items-center">
      <div class="col-lg-6">
        <div class="ratio ratio-16x9">
          <?php if(!empty($svc['cover_path'])): ?>
            <img class="cover" src="/<?= htmlspecialchars($svc['cover_path']) ?>" alt="<?= htmlspecialchars($svc['name']) ?>">
          <?php else: ?>
            <div class="bg-light rounded-3"></div>
          <?php endif; ?>
        </div>
      </div>  
      <div class="col-lg-6">
        <h1 class="h3 mb-2"><?= htmlspecialchars($svc['name']) ?></h1>
        <p class="text-secondary mb-3"><?= nl2br(htmlspecialchars($svc['excerpt'])) ?></p>
        <div class="trix-content"><?= $svc['body'] ?></div>
      </div>
    </div>
  </div>
</section>

<?php if($imgs): ?>
<section class="py-4">
  <div class="container">
    <h2 class="h5 mb-3">Informasi Layanan</h2>
    <div class="row g-3 gal">
      <?php foreach($imgs as $i => $im): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100">
              <img
                src="/<?= htmlspecialchars($im['path']) ?>"
                class="card-img-top gallery-img"
                alt="<?= htmlspecialchars($im['caption'] ?: $svc['name']) ?>"
                loading="lazy"
                data-index="<?= (int)$i ?>"
                data-src="/<?= htmlspecialchars($im['path']) ?>"
                data-caption="<?= htmlspecialchars($im['caption'] ?? '') ?>">
              <?php if(!empty($im['caption'])): ?>
                <div class="card-body py-2"><div class="small text-secondary"><?= htmlspecialchars($im['caption']) ?></div></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include $_SERVER['DOCUMENT_ROOT'].'/inc/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Lightbox Modal -->
<!-- Lightbox Modal -->
<div class="modal fade modal-lightbox" id="lightboxModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <!-- close kanan-atas (tetap) -->
      <button type="button" class="btn-close btn-close-white position-absolute end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

      <div class="modal-body p-0 position-relative">
        <img id="lbImg" class="w-100" style="max-height:80vh;object-fit:contain" alt="">
        <div class="caption small px-3 py-2" id="lbCap"></div>
        <button type="button" class="btn btn-light btn-nav start" id="lbPrev" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></button>
        <button type="button" class="btn btn-light btn-nav end" id="lbNext" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></button>

        <!-- close kanan-bawah (baru) -->
        <button type="button" class="btn btn-light btn-sm rounded-pill btn-close-bottom" id="lbCloseBottom">
          <i class="bi bi-x-lg"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<script <?= csp_script_attr() ?>>
(function(){
  const thumbs = Array.from(document.querySelectorAll('.gal .gallery-img'));
  if (!thumbs.length) return;

  const modalEl = document.getElementById('lightboxModal');
  const modal   = new bootstrap.Modal(modalEl);
  const imgEl   = document.getElementById('lbImg');
  const capEl   = document.getElementById('lbCap');
  const prevBtn = document.getElementById('lbPrev');
  const nextBtn = document.getElementById('lbNext');
  const closeBottomBtn = document.getElementById('lbCloseBottom');

  let idx = 0;

  function show(i){
    if (!thumbs.length) return;
    idx = (i + thumbs.length) % thumbs.length;
    const t = thumbs[idx];
    imgEl.src = t.dataset.src || t.src;
    imgEl.alt = t.alt || '';
    capEl.textContent = t.dataset.caption || '';
  }

  // Buka modal saat klik thumbnail
  thumbs.forEach((t, i)=>{
    t.addEventListener('click', ()=>{ show(i); modal.show(); });
  });

  // Navigasi tombol
  prevBtn.addEventListener('click', ()=> show(idx - 1));
  nextBtn.addEventListener('click', ()=> show(idx + 1));

  // Tombol tutup kanan-bawah
  closeBottomBtn.addEventListener('click', ()=> modal.hide());

  // Keyboard ← / →
  modalEl.addEventListener('shown.bs.modal', ()=> { modalEl.focus(); });
  modalEl.addEventListener('keydown', (e)=>{
    if (e.key === 'ArrowLeft')  { e.preventDefault(); show(idx - 1); }
    if (e.key === 'ArrowRight') { e.preventDefault(); show(idx + 1); }
  });

  // Tutup saat klik area kosong di dalam modal-body (bukan gambar/prev/next)
  modalEl.querySelector('.modal-body').addEventListener('click', (e)=>{
    const clickedInsideImage = imgEl.contains(e.target);
    const clickedNav = prevBtn.contains(e.target) || nextBtn.contains(e.target);
    if (!clickedInsideImage && !clickedNav) modal.hide();
  });

  // Swipe di mobile
  let sx = 0;
  imgEl.addEventListener('touchstart', e => { sx = e.touches[0].clientX; }, {passive:true});
  imgEl.addEventListener('touchend',   e => {
    const dx = e.changedTouches[0].clientX - sx;
    if (Math.abs(dx) > 40) { dx > 0 ? show(idx - 1) : show(idx + 1); }
  }, {passive:true});
})();
</script>

</body>
</html>
