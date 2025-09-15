<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin']);

$pdo = dbx();
$me  = $_SESSION['user'] ?? [];

$q       = trim($_GET['q'] ?? '');
$user_id = (int)($_GET['user_id'] ?? 0);
$action  = trim($_GET['action'] ?? '');
$dfrom   = trim($_GET['from'] ?? '');
$dto     = trim($_GET['to'] ?? '');
$page    = max(1,(int)($_GET['page'] ?? 1));
$per     = min(100, max(10,(int)($_GET['per'] ?? 50)));
$off     = ($page-1)*$per;

$where=[]; $bind=[];
if($q!==''){ $where[]="(a.action LIKE :q OR a.target_type LIKE :q OR a.ip LIKE :q OR a.user_agent LIKE :q)"; $bind[':q']="%$q%"; }
if($user_id>0){ $where[]="a.user_id=:uid"; $bind[':uid']=$user_id; }
if($action!==''){ $where[]="a.action=:ac"; $bind[':ac']=$action; }
if($dfrom!==''){ $where[]="a.created_at >= :df"; $bind[':df']=$dfrom.' 00:00:00'; }
if($dto!==''){ $where[]="a.created_at <= :dt"; $bind[':dt']=$dto.' 23:59:59'; }
$wsql = $where?('WHERE '.implode(' AND ',$where)):'';

$stc=$pdo->prepare("SELECT COUNT(*) FROM audit_log a $wsql");
foreach($bind as $k=>$v) $stc->bindValue($k,$v);
$stc->execute(); $total=(int)$stc->fetchColumn();

$std=$pdo->prepare("
  SELECT a.*, u.full_name, u.email
  FROM audit_log a
  LEFT JOIN admin_users u ON u.id=a.user_id
  $wsql
  ORDER BY a.created_at DESC
  LIMIT :lim OFFSET :off
");
foreach($bind as $k=>$v) $std->bindValue($k,$v);
$std->bindValue(':lim',$per,PDO::PARAM_INT);
$std->bindValue(':off',$off,PDO::PARAM_INT);
$std->execute(); $rows=$std->fetchAll();

$users = $pdo->query("SELECT id, COALESCE(full_name,email) AS name FROM admin_users ORDER BY name")->fetchAll();
$pages = max(1,(int)ceil($total/$per));
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Audit Log • RSUD Matraman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>.ua{max-width:340px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.meta-cell{max-width:360px}pre.json{max-height:60vh;overflow:auto}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="/dashboard.php"><i class="bi bi-hospital"></i> Dashboard</a>
    <div class="ms-auto d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="/cms_posts.php"><i class="bi bi-journal-text"></i> Berita</a>
      <a class="btn btn-outline-secondary btn-sm" href="/cms_categories.php"><i class="bi bi-tags"></i> Kategori</a>
      <a class="btn btn-outline-secondary btn-sm" href="/cms_services.php"><i class="bi bi-collection"></i> Layanan</a>
      <a class="btn btn-primary btn-sm" href="/cms_audit_log.php"><i class="bi bi-clipboard-data"></i> Audit Log</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-end mb-3">
    <h1 class="h5 mb-0">Audit Log</h1>
    <div class="small text-secondary">Total: <?= $total ?></div>
  </div>

  <form class="row g-2 mb-3">
    <div class="col-md-3"><input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari aksi/target/ip/UA"></div>
    <div class="col-md-2">
      <select class="form-select" name="user_id">
        <option value="0">Semua Pengguna</option>
        <?php foreach($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= $user_id==(int)$u['id']?'selected':'' ?>><?= htmlspecialchars($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input class="form-control" name="action" value="<?= htmlspecialchars($action) ?>" placeholder="action e.g. post.update"></div>
    <div class="col-md-2"><input type="date" class="form-control" name="from" value="<?= htmlspecialchars($dfrom) ?>"></div>
    <div class="col-md-2"><input type="date" class="form-control" name="to"   value="<?= htmlspecialchars($dto) ?>"></div>
    <div class="col-md-1 d-grid"><button class="btn btn-outline-primary"><i class="bi bi-search"></i></button></div>
  </form>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th style="width:160px">Waktu</th><th>Pengguna</th><th>Aksi</th><th>Target</th><th class="meta-cell">Meta</th><th>IP</th><th>UA</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r):
          $metaRaw = (string)($r['meta'] ?? '');
          $metaTrunc = mb_strimwidth($metaRaw, 0, 120, '…', 'UTF-8');
        ?>
          <tr>
            <td class="small text-secondary"><?= date('Y-m-d H:i:s', strtotime($r['created_at'])) ?> WIB</td>
            <td><?php if(!empty($r['full_name'])): ?><div><?= htmlspecialchars($r['full_name']) ?></div><div class="small text-secondary"><?= htmlspecialchars($r['email'] ?? '') ?></div><?php else: ?><span class="text-secondary">#<?= (int)$r['user_id'] ?></span><?php endif; ?></td>
            <td><span class="badge text-bg-light border"><?= htmlspecialchars($r['action']) ?></span></td>
            <td class="small"><div><?= htmlspecialchars($r['target_type']) ?> #<?= (int)$r['target_id'] ?></div></td>
            <td class="small">
              <div class="text-secondary"><?= htmlspecialchars($metaTrunc) ?></div>
              <?php if(strlen($metaRaw) > strlen($metaTrunc)): ?>
                <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#metaModal" data-meta='<?= htmlspecialchars($metaRaw, ENT_QUOTES) ?>'>lihat</button>
              <?php endif; ?>
            </td>
            <td class="small text-secondary"><?= htmlspecialchars($r['ip']) ?></td>
            <td class="ua small text-secondary" title="<?= htmlspecialchars($r['user_agent']) ?>"><?= htmlspecialchars($r['user_agent']) ?></td>
          </tr>
        <?php endforeach; if(empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-secondary">Tidak ada data.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php $mk=function($p)use($q,$user_id,$action,$dfrom,$dto,$per){return '?q='.urlencode($q).'&user_id='.$user_id.'&action='.urlencode($action).'&from='.urlencode($dfrom).'&to='.urlencode($dto).'&per='.$per.'&page='.$p;}; ?>
  <div class="d-flex justify-content-between align-items-center">
    <div class="small text-secondary">Halaman <?= $page ?>/<?= max(1,(int)ceil($total/$per)) ?> • <?= $per ?>/hal</div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm <?= $page<=1?'disabled':'' ?>" href="<?= $page<=1?'#':$mk($page-1) ?>">&laquo; Prev</a>
      <a class="btn btn-outline-secondary btn-sm <?= ($page*$per)>=$total?'disabled':'' ?>" href="<?= ($page*$per)>=$total?'#':$mk($page+1) ?>">Next &raquo;</a>
    </div>
  </div>
</div>

<div class="modal fade" id="metaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-braces-asterisk me-2"></i>Meta Detail</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><pre class="json mb-0" id="metaPre"></pre></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const metaModal=document.getElementById('metaModal');
  metaModal.addEventListener('show.bs.modal',e=>{const raw=e.relatedTarget?.getAttribute('data-meta')||'';let pretty=raw;try{pretty=JSON.stringify(JSON.parse(raw),null,2);}catch{}document.getElementById('metaPre').textContent=pretty;});
</script>
</body>
</html>
