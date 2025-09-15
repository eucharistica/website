<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

if ($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); exit; }
$raw=file_get_contents('php://input'); $js=json_decode($raw,true);
if(!is_array($js) || !csrf_validate($js['csrf'] ?? '')){ http_response_code(400); echo 'bad csrf'; exit; }
$ids = array_map('intval', $js['ids'] ?? []);
if(!$ids){ echo 'ok'; exit; }

$pdo = dbx();
try{
  $pdo->beginTransaction();
  $i=1; $st=$pdo->prepare("UPDATE carousel SET sort_order=:o WHERE id=:id");
  foreach($ids as $id){ $st->execute([':o'=>$i++,':id'=>$id]); }
  $pdo->commit();
  audit_log('carousel.sort','carousel',0,['order'=>$ids]);
  echo 'ok';
}catch(Throwable $e){
  $pdo->rollBack(); http_response_code(500); echo 'err';
}
