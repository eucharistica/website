<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

if($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/cms_categories.php'); exit; }
if(!csrf_validate($_POST['csrf']??'')){ flash_set('err','Sesi kadaluarsa'); header('Location:/cms_categories.php'); exit; }

$pdo = dbx();

$id    = (int)($_POST['id']??0);
$name  = trim($_POST['name']??'');
$slugI = trim($_POST['slug']??'');
$slug  = $slugI!=='' ? slugify($slugI) : slugify($name);
$desc  = trim($_POST['description']??'');
$stIn  = isset($_POST['status']) ? (int)$_POST['status'] : 1;

/* cek kolom */
$hasStatus=false; $hasDesc=false;
try{$hasStatus=(bool)$pdo->query("SHOW COLUMNS FROM categories LIKE 'status'")->fetch();}catch(Throwable $e){}
try{$hasDesc=(bool)$pdo->query("SHOW COLUMNS FROM categories LIKE 'description'")->fetch();}catch(Throwable $e){}

if ($name===''){ flash_set('err','Nama kategori wajib diisi'); header('Location:'.($id>0?'/cms_category_edit.php?id='.$id:'/cms_category_edit.php')); exit; }

try{
  $pdo->beginTransaction();

  // pastikan slug unik
  $chk = $pdo->prepare("SELECT id FROM categories WHERE slug=:s".($id>0?" AND id<>$id":'')." LIMIT 1");
  $chk->execute([':s'=>$slug]);
  if($chk->fetch()){ $slug .= '-'.substr(sha1((string)microtime(true)),0,6); }

  if ($id>0) {
    $sets = ["name=:n","slug=:s"];
    $params = [':n'=>$name,':s'=>$slug,':id'=>$id];
    if($hasDesc){ $sets[]="description=:d"; $params[':d']=$desc; }
    if($hasStatus){ $sets[]="status=:st"; $params[':st']=$stIn; }
    $sets[]="updated_at=NOW()";
    $sql="UPDATE categories SET ".implode(',',$sets)." WHERE id=:id";
    $st=$pdo->prepare($sql); $st->execute($params);
    audit_log('category.update','category',$id,['name'=>$name,'status'=>$hasStatus?$stIn:null]);
  } else {
    $cols = ["name","slug"];
    $vals = [":n",":s"];
    $params=[":n"=>$name,":s"=>$slug];
    if($hasDesc){ $cols[]="description"; $vals[]=":d"; $params[':d']=$desc; }
    if($hasStatus){ $cols[]="status"; $vals[]=":st"; $params[':st']=$stIn; }
    $cols[]="created_at"; $vals[]="NOW()";
    $cols[]="updated_at"; $vals[]="NOW()";
    $sql="INSERT INTO categories (".implode(',',$cols).") VALUES (".implode(',',$vals).")";
    $st=$pdo->prepare($sql); $st->execute($params);
    $id=(int)$pdo->lastInsertId();
    audit_log('category.create','category',$id,['name'=>$name,'status'=>$hasStatus?$stIn:null]);
  }

  $pdo->commit();
  flash_set('ok','Kategori disimpan.');
  header('Location:/cms_category_edit.php?id='.$id); exit;

}catch(Throwable $e){
  $pdo->rollBack(); error_log('cat_save: '.$e->getMessage());
  flash_set('err','Gagal menyimpan: '.$e->getMessage());
  header('Location:/cms_categories.php'); exit;
}
