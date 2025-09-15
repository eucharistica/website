<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

$id = (int)($_GET['id'] ?? 0);
if ($id<=0 || !csrf_validate($_GET['csrf'] ?? '')){ flash_set('err','Permintaan tidak valid'); header('Location:/cms_services.php'); exit; }

$pdo = dbx();

try{
  $pdo->beginTransaction();

  // ambil nama & file untuk dihapus
  $st=$pdo->prepare("SELECT name, cover_path FROM services WHERE id=:id");
  $st->execute([':id'=>$id]); $row=$st->fetch();
  $name=$row['name']??null; $cover=$row['cover_path']??null;

  // ambil images
  $imgs=$pdo->prepare("SELECT path FROM service_images WHERE service_id=:id");
  $imgs->execute([':id'=>$id]); $imgRows=$imgs->fetchAll();

  // delete from DB (cascade untuk service_images, tapi kita delete manual file fisiknya)
  $pdo->prepare("DELETE FROM services WHERE id=:id")->execute([':id'=>$id]);

  $pdo->commit();

  // hapus file fisik (best-effort)
  if ($cover && is_file(__DIR__.'/'.$cover)) @unlink(__DIR__.'/'.$cover);
  foreach($imgRows as $im){ $p=$im['path']??''; if($p && is_file(__DIR__.'/'.$p)) @unlink(__DIR__.'/'.$p); }

  audit_log('service.delete','service',$id,['name'=>$name]);
  flash_set('ok','Layanan dihapus.');
}catch(Throwable $e){
  $pdo->rollBack(); error_log('svc_del: '.$e->getMessage());
  flash_set('err','Gagal menghapus: '.$e->getMessage());
}
header('Location:/cms_services.php'); exit;
