<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

if($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/cms_carousel.php'); exit; }
if(!csrf_validate($_POST['csrf']??'')){ flash_set('err','Sesi kadaluarsa'); header('Location:/cms_carousel.php'); exit; }

$pdo = dbx();

$id     = (int)($_POST['id']??0);
$url = trim($_POST['url'] ?? '');
if ($url !== '') {
  if (!preg_match('~^(https?://|/)~i', $url)) { $url = ''; }
  if (stripos($url, 'javascript:') === 0) { $url = ''; }
}

$status = (int)($_POST['status']??1);

// cek path lama
$oldPath = null;
if ($id>0){
  $st=$pdo->prepare("SELECT path FROM carousel WHERE id=:id");
  $st->execute([':id'=>$id]); 
  $oldPath=$st->fetchColumn() ?: null;
}
$needPhoto = ($id===0 || empty($oldPath));

// upload photo
try { $photo = save_uploaded_image('photo','carousel'); }
catch(Throwable $e){ 
  flash_set('err','Upload foto gagal: '.$e->getMessage()); 
  header('Location:'.($id>0?'/cms_carousel_edit.php?id='.$id:'/cms_carousel_edit.php')); 
  exit; 
}
if ($needPhoto && !$photo){ 
  flash_set('err','Foto wajib diunggah.'); 
  header('Location:'.($id>0?'/cms_carousel_edit.php?id='.$id:'/cms_carousel_edit.php')); 
  exit; 
}

try{
  $pdo->beginTransaction();

  if ($id>0) {
    $sets=["url=:u","status=:st","updated_at=NOW()"];
    $params=[':u'=>$url?:null,':st'=>$status,':id'=>$id];
    if ($photo){ $sets[]="path=:p"; $params[':p']=$photo; }
    $sql="UPDATE carousel SET ".implode(',',$sets)." WHERE id=:id";
    $pdo->prepare($sql)->execute($params);
    audit_log('carousel.update','carousel',$id,['url'=>$url?:null,'status'=>$status,'changed_photo'=>(bool)$photo]);
  } else {
    // ❗ Hindari subquery ke tabel yang sama saat INSERT (menghindari error 1093)
    $nextOrder = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM carousel")->fetchColumn();

    $sql="INSERT INTO carousel (path,url,status,sort_order,created_at,updated_at)
          VALUES (:p,:u,:st,:so,NOW(),NOW())";
    $pdo->prepare($sql)->execute([
      ':p'=>$photo,
      ':u'=>$url?:null,
      ':st'=>$status,
      ':so'=>$nextOrder,
    ]);
    $id=(int)$pdo->lastInsertId();
    audit_log('carousel.create','carousel',$id,['url'=>$url?:null,'status'=>$status]);
  }

  $pdo->commit();
  flash_set('ok','Carousel disimpan.');
  header('Location:/cms_carousel_edit.php?id='.$id); exit;

}catch(Throwable $e){
  $pdo->rollBack(); 
  error_log('carousel_save: '.$e->getMessage());
  flash_set('err','Gagal menyimpan: '.$e->getMessage());
  header('Location:/cms_carousel.php'); exit;
}
