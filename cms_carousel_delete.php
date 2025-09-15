<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

$id = (int)($_GET['id'] ?? 0);
if ($id<=0 || !csrf_validate($_GET['csrf'] ?? '')){ flash_set('err','Permintaan tidak valid'); header('Location:/cms_carousel.php'); exit; }

$pdo = dbx();
try{
  $st=$pdo->prepare("SELECT path FROM carousel WHERE id=:id");
  $st->execute([':id'=>$id]); $p=$st->fetchColumn();
  $pdo->prepare("DELETE FROM carousel WHERE id=:id")->execute([':id'=>$id]);
  if ($p && is_file(__DIR__.'/'.$p)) @unlink(__DIR__.'/'.$p);
  audit_log('carousel.delete','carousel',$id,['path'=>$p]);
  flash_set('ok','Item dihapus.');
}catch(Throwable $e){
  error_log('carousel_del: '.$e->getMessage());
  flash_set('err','Gagal menghapus: '.$e->getMessage());
}
header('Location:/cms_carousel.php'); exit;
