<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);
$id=(int)($_GET['id']??0);
if(!$id || !csrf_validate($_GET['csrf']??'')){ flash_set('err','Token tidak valid'); header('Location:/cms_posts.php'); exit; }
$pdo=dbx();
$pdo->prepare("DELETE FROM posts WHERE id=:id")->execute([':id'=>$id]);
flash_set('ok','Berita dihapus.'); header('Location:/cms_posts.php');
audit_log('post.delete','post',$id);
