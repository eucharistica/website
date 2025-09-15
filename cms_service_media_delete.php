<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

$id = (int)($_GET['id'] ?? 0);
if ($id<=0 || !csrf_validate($_GET['csrf'] ?? '')){ flash_set('err','Permintaan tidak valid'); header('Location:/cms_services.php'); exit; }

$pdo = dbx();
try{
  $st=$pdo->prepare("SELECT service_id, path FROM service_images WHERE id=:id");
  $st->execute([':id'=>$id]); $r=$st->fetch();
  if(!$r){ flash_set('err','Foto tidak ditemukan'); header('Location:/cms_services.php'); exit; }

  $sid=(int)$r['service_id']; $path=$r['path'];

  $pdo->prepare("DELETE FROM service_images WHERE id=:id")->execute([':id'=>$id]);
  if($path && is_file(__DIR__.'/'.$path)) @unlink(__DIR__.'/'.$path);

  audit_log('service.media_delete','service',$sid,['image_id'=>$id,'path'=>$path]);
  flash_set('ok','Foto dihapus.');
  header('Location:/cms_service_edit.php?id='.$sid); exit;
}catch(Throwable $e){
  error_log('svc_media_del: '.$e->getMessage());
  flash_set('err','Gagal menghapus foto: '.$e->getMessage());
  header('Location:/cms_services.php'); exit;
}
