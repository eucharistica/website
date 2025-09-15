<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);
$pdo = dbx();

$q = trim($_GET['q'] ?? '');
$where=[]; $bind=[];
if($q!==''){ $where[]="(url LIKE :q)"; $bind[':q']="%$q%"; }
$wsql = $where?('WHERE '.implode(' AND ',$where)):'';

$st=$pdo->prepare("SELECT * FROM carousel $wsql ORDER BY sort_order ASC, id DESC");
foreach($bind as $k=>$v)$st->bindValue($k,$v);
$st->execute(); $rows=$st->fetchAll();

$me = $_SESSION['user'] ?? [];
$isAdmin = (($me['role'] ?? '')==='admin');
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Carousel • RSUD Matraman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>.thumb{width:120px;height:68px;object-fit:cover;border:1px solid #e5e7eb;border-radius:.25rem}.draggable{cursor:grab}.dragging{opacity:.6}.badge-status{min-width:76px;text-align:center}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="/cms_posts.php"><i class="bi bi-journal-text"></i> Berita</a>
      <a class="btn btn-outline-secondary btn-sm" href="/cms_services.php"><i class="bi bi-collection"></i> Layanan</a>
      <a class="btn btn-primary btn-sm" href="/cms_carousel.php"><i class="bi bi-images"></i> Carousel</a>
      <?php if($isAdmin): ?><a class="btn btn-outline-secondary btn-sm" href="/cms_audit_log.php"><i class="bi bi-clipboard-data"></i> Audit Log</a><?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">
  <?php if($ok=flash_get('ok')): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($er=flash_get('err')): ?><div class="alert alert-danger  py-2 small"><?= htmlspecialchars($er) ?></div><?php endif; ?>

  <div class="d-flex justify-content-between align-items-end mb-3">
    <form class="row g-2" method="get">
      <div class="col-auto"><input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari URL…"></div>
      <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button></div>
    </form>
    <div class="d-flex gap-2">
      <a class="btn btn-primary" href="/cms_carousel_edit.php"><i class="bi bi-plus-lg"></i> Tambah Foto</a>
      <a class="btn btn-outline-secondary" href="/" target="_blank"><i class="bi bi-globe2"></i> Lihat Beranda</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle" id="tbl">
      <thead><tr><th style="width:42px"></th><th>Foto</th><th>URL</th><th class="text-center" style="width:120px">Status</th><th style="width:200px">Aksi</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr class="draggable" data-id="<?= (int)$r['id'] ?>">
          <td class="text-secondary"><i class="bi bi-list"></i></td>
          <td><img class="thumb" src="/<?= htmlspecialchars($r['path']) ?>" alt=""></td>
          <td class="small"><?= $r['url'] ? '<a href="'.htmlspecialchars($r['url']).'" target="_blank">'.htmlspecialchars($r['url']).'</a>' : '<span class="text-secondary">—</span>' ?></td>
          <td class="text-center"><?= ((int)$r['status']===1?'<span class="badge text-bg-success badge-status">Aktif</span>':'<span class="badge text-bg-secondary badge-status">Nonaktif</span>') ?></td>
          <td>
            <a class="btn btn-sm btn-primary" href="/cms_carousel_edit.php?id=<?= (int)$r['id'] ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <a class="btn btn-sm btn-outline-danger" href="/cms_carousel_delete.php?id=<?= (int)$r['id'] ?>&csrf=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Hapus item carousel ini?')"><i class="bi bi-trash"></i> Hapus</a>
          </td>
        </tr>
        <?php endforeach; if(empty($rows)): ?>
          <tr><td colspan="5" class="text-center text-secondary">Belum ada foto.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="small text-secondary mt-2"><i class="bi bi-info-circle"></i> Drag & drop baris untuk mengubah urutan.</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const tbody=document.querySelector('#tbl tbody');
let dragEl=null;
tbody.querySelectorAll('tr.draggable').forEach(tr=>{tr.draggable=true;});
tbody.addEventListener('dragstart',e=>{const tr=e.target.closest('tr.draggable'); if(!tr)return; dragEl=tr; tr.classList.add('dragging');});
tbody.addEventListener('dragend',e=>{if(dragEl){dragEl.classList.remove('dragging'); dragEl=null; saveOrder();}});
tbody.addEventListener('dragover',e=>{
  e.preventDefault();
  const dragging=tbody.querySelector('.dragging'); if(!dragging)return;
  const after=[...tbody.querySelectorAll('tr.draggable:not(.dragging)')].find(row=> e.clientY <= row.getBoundingClientRect().top + row.offsetHeight/2 );
  if(!after) tbody.appendChild(dragging); else tbody.insertBefore(dragging, after);
});
async function saveOrder(){
  const ids=[...tbody.querySelectorAll('tr.draggable')].map(tr=>tr.dataset.id);
  try{
    await fetch('/cms_carousel_sort.php',{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'fetch'},body:JSON.stringify({csrf:'<?= csrf_token() ?>',ids})});
  }catch(e){console.error(e);}
}
</script>
</body>
</html>
