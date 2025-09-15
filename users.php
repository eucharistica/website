<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin']);

$pdo = dbx();
$q   = input('q') ?? '';
$role= input('role') ?? '';
$act = input('active') ?? ''; // '1'/'0'/''
$page = max(1, (int)(input('page') ?? 1));
$per  = min(100, max(10, (int)(input('per_page') ?? 20)));
$off  = ($page-1)*$per;

$where = []; $bind = [];
if ($q !== '') { $where[] = '(username LIKE :q OR full_name LIKE :q OR email LIKE :q)'; $bind[':q'] = '%'.$q.'%'; }
if ($role !== '' && in_array($role,['admin','editor','operator','patient'],true)) { $where[]='role=:r'; $bind[':r']=$role; }
if ($act==='0' || $act==='1') { $where[]='is_active=:a'; $bind[':a']=(int)$act; }
$wsql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM admin_users $wsql")->execute($bind) ?: 0;
$stc = $pdo->prepare("SELECT COUNT(*) FROM admin_users $wsql");
$stc->execute($bind); $total = (int)$stc->fetchColumn();

$sql = "SELECT id,username,full_name,email,role,is_active,last_login,created_at
        FROM admin_users $wsql
        ORDER BY id DESC
        LIMIT :lim OFFSET :off";
$st = $pdo->prepare($sql);
foreach ($bind as $k=>$v) $st->bindValue($k,$v);
$st->bindValue(':lim',$per,PDO::PARAM_INT); $st->bindValue(':off',$off,PDO::PARAM_INT);
$st->execute(); $rows = $st->fetchAll();

$pages = max(1, (int)ceil($total / $per));
$roleOptions = [''=>'(Semua)','admin'=>'Admin','editor'=>'Editor','operator'=>'Operator','patient'=>'Pasien'];
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kelola Pengguna • RSUD Matraman</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>.table thead th{white-space:nowrap}.role-select{min-width:150px}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="/dashboard.php"><i class="bi bi-people"></i><span class="fw-semibold">Kelola Pengguna</span></a>
    <div class="ms-auto"><a class="btn btn-outline-secondary btn-sm" href="/dashboard.php"><i class="bi bi-arrow-left"></i> Dashboard</a></div>
  </div>
</nav>

<div class="container py-4">
  <?php if ($ok=flash_get('ok')): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if ($err=flash_get('err')): ?><div class="alert alert-danger  py-2 small"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- Filter & search -->
  <form class="row g-2 align-items-end mb-3" method="get">
    <div class="col-12 col-md-4">
      <label class="form-label small text-secondary">Cari</label>
      <input name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="username / nama / email">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small text-secondary">Role</label>
      <select name="role" class="form-select">
        <?php foreach($roleOptions as $val=>$lab): ?>
          <option value="<?= $val ?>" <?= $val===$role?'selected':'' ?>><?= $lab ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small text-secondary">Status</label>
      <select name="active" class="form-select">
        <option value="">(Semua)</option>
        <option value="1" <?= $act==='1'?'selected':'' ?>>Aktif</option>
        <option value="0" <?= $act==='0'?'selected':'' ?>>Nonaktif</option>
      </select>
    </div>
    <div class="col-6 col-md-2 d-grid">
      <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Cari</button>
    </div>
  </form>

  <form method="post" action="/users_save.php">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr>
          <th>ID</th><th>Username</th><th>Nama</th><th>Email</th><th>Role</th><th>Aktif</th><th>Login Terakhir</th><th>Dibuat</th>
        </tr></thead>
        <tbody>
          <?php foreach($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?><input type="hidden" name="rows[<?= (int)$r['id'] ?>][id]" value="<?= (int)$r['id'] ?>"></td>
            <td><?= htmlspecialchars($r['username']) ?></td>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
            <td>
              <select class="form-select form-select-sm role-select" name="rows[<?= (int)$r['id'] ?>][role]">
                <?php foreach (['admin','editor','operator','patient'] as $opt): ?>
                  <option value="<?= $opt ?>" <?= $opt===$r['role']?'selected':'' ?>><?= ucfirst($opt) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="rows[<?= (int)$r['id'] ?>][is_active]" value="1" <?= ((int)$r['is_active']===1?'checked':'') ?>></div></td>
            <td class="small text-secondary"><?= htmlspecialchars($r['last_login'] ?? '-') ?></td>
            <td class="small text-secondary"><?= htmlspecialchars($r['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="small text-secondary">Menampilkan <?= count($rows) ?> dari <?= $total ?> data • Halaman <?= $page ?>/<?= $pages ?></div>
      <div class="d-flex gap-2">
        <?php
          $base = function($p) use($q,$role,$act,$per){ return '?q='.urlencode($q).'&role='.urlencode($role).'&active='.urlencode($act).'&per_page='.$per.'&page='.$p; };
        ?>
        <a class="btn btn-outline-secondary btn-sm <?= $page<=1?'disabled':'' ?>" href="<?= $page<=1?'#':$base($page-1) ?>">&laquo; Prev</a>
        <a class="btn btn-outline-secondary btn-sm <?= $page>=$pages?'disabled':'' ?>" href="<?= $page>=$pages?'#':$base($page+1) ?>">Next &raquo;</a>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
      </div>
    </div>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
