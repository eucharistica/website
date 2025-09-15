<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

$id = (int)($_GET['id'] ?? 0);
if ($id<=0 || !csrf_validate($_GET['csrf'] ?? '')) { flash_set('err','Permintaan tidak valid'); header('Location:/cms_categories.php'); exit; }

$pdo = dbx();

try{
  $pdo->beginTransaction();

  $st=$pdo->prepare("SELECT name FROM categories WHERE id=:id");
  $st->execute([':id'=>$id]);
  $name=$st->fetchColumn();

  $pdo->prepare("DELETE FROM post_categories WHERE category_id=:id")->execute([':id'=>$id]);
  $del=$pdo->prepare("DELETE FROM categories WHERE id=:id");
  $del->execute([':id'=>$id]);

  $pdo->commit();
  audit_log('category.delete','category',$id,['name'=>$name]);
  flash_set('ok','Kategori dihapus.');
}catch(Throwable $e){
  $pdo->rollBack(); error_log('cat_delete: '.$e->getMessage());
  flash_set('err','Gagal menghapus: '.$e->getMessage());
}
header('Location:/cms_categories.php'); exit;
