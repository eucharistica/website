<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

if($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/cms_services.php'); exit; }
if(!csrf_validate($_POST['csrf']??'')){ flash_set('err','Sesi kadaluarsa'); header('Location:/cms_services.php'); exit; }

$pdo = dbx();

$id      = (int)($_POST['id']??0);
$name    = trim($_POST['name']??'');
$slugI   = trim($_POST['slug']??'');
$slug    = $slugI!=='' ? slugify($slugI) : slugify($name);
$excerpt = trim($_POST['excerpt']??'');
$body    = trim($_POST['body']??'');
$status  = (int)($_POST['status'] ?? 1);

if ($name===''){ flash_set('err','Nama layanan wajib diisi'); header('Location:'.($id>0?'/cms_service_edit.php?id='.$id:'/cms_service_edit.php')); exit; }

// ambil cover lama (cek wajib cover)
$existingCover = null;
if ($id>0) {
  $st0=$pdo->prepare("SELECT cover_path FROM services WHERE id=:id");
  $st0->execute([':id'=>$id]); $existingCover = $st0->fetchColumn() ?: null;
}
$mustHaveCover = ($id===0 || empty($existingCover));

// upload cover (optional jika sudah ada)
try { $cover = save_uploaded_image('cover','services'); } 
catch(Throwable $e){ flash_set('err','Upload cover gagal: '.$e->getMessage()); header('Location:'.($id>0?'/cms_service_edit.php?id='.$id:'/cms_service_edit.php')); exit; }

if ($mustHaveCover && !$cover) {
  flash_set('err','Cover wajib diunggah untuk layanan baru atau yang belum memiliki cover.');
  header('Location:'.($id>0?'/cms_service_edit.php?id='.$id:'/cms_service_edit.php')); exit;
}

try{
  $pdo->beginTransaction();

  // pastikan slug unik
  $chk=$pdo->prepare("SELECT id FROM services WHERE slug=:s".($id>0?" AND id<>$id":'')." LIMIT 1");
  $chk->execute([':s'=>$slug]);
  if($chk->fetch()){ $slug .= '-'.substr(sha1((string)microtime(true)),0,6); }

  if ($id>0) {
    $sets = ["name=:n","slug=:s","excerpt=:e","body=:b","status=:st","updated_at=NOW()"];
    $params = [':n'=>$name,':s'=>$slug,':e'=>$excerpt,':b'=>$body,':st'=>$status,':id'=>$id];
    if ($cover) { $sets[]="cover_path=:c"; $params[':c']=$cover; }
    $sql="UPDATE services SET ".implode(',',$sets)." WHERE id=:id";
    $pdo->prepare($sql)->execute($params);
    audit_log('service.update','service',$id,['name'=>$name,'slug'=>$slug,'status'=>$status]);
  } else {
    $sql="INSERT INTO services (name,slug,excerpt,body,cover_path,status,sort_order,created_at,updated_at)
          VALUES (:n,:s,:e,:b,:c,:st,(SELECT COALESCE(MAX(sort_order),0)+1 FROM services x),NOW(),NOW())";
    $pdo->prepare($sql)->execute([':n'=>$name,':s'=>$slug,':e'=>$excerpt,':b'=>$body,':c'=>$cover,':st'=>$status]);
    $id=(int)$pdo->lastInsertId();
    audit_log('service.create','service',$id,['name'=>$name,'slug'=>$slug,'status'=>$status]);
  }

  // update caption & order existing
  if (!empty($_POST['order']) && is_array($_POST['order'])) {
    $orderIds = array_map('intval', $_POST['order']);
    $i=1;
    $up = $pdo->prepare("UPDATE service_images SET sort_order=:o WHERE id=:id AND service_id=:sid");
    foreach($orderIds as $imgId){ $up->execute([':o'=>$i++,':id'=>$imgId,':sid'=>$id]); }
  }
  if (!empty($_POST['cap']) && is_array($_POST['cap'])) {
    $upc = $pdo->prepare("UPDATE service_images SET caption=:c WHERE id=:id AND service_id=:sid");
    foreach($_POST['cap'] as $imgId=>$cap){ $upc->execute([':c'=>trim((string)$cap), ':id'=>(int)$imgId, ':sid'=>$id]); }
  }

  // upload GALERI (multiple)
  if (!empty($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
    $count = count($_FILES['gallery']['name']);
    $ins = $pdo->prepare("INSERT INTO service_images (service_id, path, caption, sort_order, created_at) VALUES (:sid,:p,:c,:o,NOW())");
    $startOrder = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM service_images WHERE service_id=".$id)->fetchColumn();
    $added = 0;
    for($i=0;$i<$count;$i++){
      if (($_FILES['gallery']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
      $tmp = $_FILES['gallery']['tmp_name'][$i];
      $nm  = $_FILES['gallery']['name'][$i];

      // validasi gambar sederhana (fallback bila finfo tidak ada)
      $ok=false; $ext='jpg';
      $mime = null;
      if (function_exists('finfo_open')) {
        $finfo=finfo_open(FILEINFO_MIME_TYPE);
        $mime=@finfo_file($finfo,$tmp); @finfo_close($finfo);
      }
      if (!$mime && function_exists('mime_content_type')) { $mime=@mime_content_type($tmp); }
      $gi=@getimagesize($tmp);
      if ($gi && empty($mime) && !empty($gi['mime'])) $mime=$gi['mime'];

      $allow=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
      if ($mime && isset($allow[$mime])) { $ok=true; $ext=$allow[$mime]; }
      elseif($gi){ $ok=true; $ext='jpg'; } // fallback

      if (!$ok) continue;

      $dir = 'assets/uploads/services/'.date('Y/m');
      $abs = __DIR__ . '/' . $dir;
      if (!is_dir($abs)) { @mkdir($abs,0755,true); }
      $fname = 'gal_'.date('His').'_'.bin2hex(random_bytes(4)).'.'.$ext;
      $dest  = $abs.'/'.$fname;
      if (@move_uploaded_file($tmp,$dest)) {
        $rel = $dir.'/'.$fname;
        $startOrder++;
        $ins->execute([':sid'=>$id, ':p'=>$rel, ':c'=>null, ':o'=>$startOrder]);
        $added++;
      }
    }
    if ($added>0) audit_log('service.media_add','service',$id,['added'=>$added]);
  }

  $pdo->commit();
  flash_set('ok','Layanan disimpan.');
  header('Location:/cms_service_edit.php?id='.$id); exit;

}catch(Throwable $e){
  $pdo->rollBack();
  error_log('svc_save: '.$e->getMessage());
  flash_set('err','Gagal menyimpan: '.$e->getMessage());
  header('Location:/cms_services.php'); exit;
}
