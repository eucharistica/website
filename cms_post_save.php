<?php
require_once __DIR__ . '/inc/app.php';
require_role(['admin','editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location:/cms_posts.php'); exit; }
if (!csrf_validate($_POST['csrf'] ?? '')) { flash_set('err','Sesi kadaluarsa'); header('Location:/cms_posts.php'); exit; }

$pdo = dbx();

/* ---------- Helpers ---------- */
if (!function_exists('slugify')) {
  function slugify(string $text): string {
    $text = trim($text);
    if ($text === '') return '';
    // transliterate ke ASCII jika tersedia
    if (function_exists('iconv')) {
      $t = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$text);
      if ($t !== false) $text = $t;
    }
    $text = strtolower($text);
    $text = preg_replace('~[^a-z0-9]+~','-',$text);
    $text = trim($text,'-');
    return $text !== '' ? $text : substr(sha1((string)microtime(true)),0,8);
  }
}

/* ---------- Input ---------- */
$id       = (int)($_POST['id'] ?? 0);
$title    = trim((string)($_POST['title'] ?? ''));
$slugIn   = trim((string)($_POST['slug'] ?? ''));
$slug     = $slugIn !== '' ? slugify($slugIn) : slugify($title);
$excerpt  = trim((string)($_POST['excerpt'] ?? ''));
$body     = (string)($_POST['body'] ?? '');
$statusIn = (string)($_POST['status'] ?? '');
$status   = in_array($statusIn, ['draft','published'], true) ? $statusIn : 'draft';

$catIds   = array_map('intval', $_POST['cat'] ?? []);
$catIds   = array_values(array_unique(array_filter($catIds)));

/* ---------- Publish controls ---------- */
$publish_mode        = ((string)($_POST['publish_mode'] ?? 'now') === 'at') ? 'at' : 'now';
$published_at_input  = trim((string)($_POST['published_at'] ?? ''));

$pubParam = null;   // 'Y-m-d H:i:s' jika schedule
$useNow   = false;  // gunakan NOW() di SQL
if ($status === 'published') {
  if ($publish_mode === 'at' && $published_at_input !== '') {
    $tz = new DateTimeZone('Asia/Jakarta');
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $published_at_input, $tz);
    if ($dt instanceof DateTime) {
      $pubParam = $dt->format('Y-m-d H:i:00'); // detik dibulatkan 00
    } else {
      $useNow = true;
    }
  } else {
    $useNow = true;
  }
}

/* ---------- Validasi dasar ---------- */
if ($title === '') {
  flash_set('err','Judul wajib diisi.');
  header('Location: '.($id>0?'/cms_post_edit.php?id='.$id:'/cms_post_edit.php')); exit;
}

/* ---------- Cek cover existing (wajib cover untuk post baru / belum ada cover) ---------- */
$existingCover = null;
if ($id > 0) {
  $st0 = $pdo->prepare("SELECT cover_path FROM posts WHERE id=:id LIMIT 1");
  $st0->execute([':id'=>$id]);
  $existingCover = $st0->fetchColumn() ?: null;
}
$mustHaveCover = ($id === 0 || empty($existingCover));

/* ---------- Proses upload cover (opsional) ---------- */
$cover = null;
try {
  $cover = save_uploaded_image('cover', 'posts'); // null jika tidak upload baru
} catch (Throwable $e) {
  flash_set('err','Upload cover gagal: '.$e->getMessage());
  header('Location: '.($id>0?'/cms_post_edit.php?id='.$id:'/cms_post_edit.php')); exit;
}

// Wajib ada cover bila baru / belum punya sama sekali
if ($mustHaveCover && !$cover) {
  flash_set('err','Cover wajib diunggah untuk berita baru atau yang belum memiliki cover.');
  header('Location: '.($id>0?'/cms_post_edit.php?id='.$id:'/cms_post_edit.php')); exit;
}

try {
  $pdo->beginTransaction();

  /* ---------- Pastikan slug unik ---------- */
  $baseSlug = $slug;
  $try = 0;
  while (true) {
    if ($id > 0) {
      $chk = $pdo->prepare("SELECT 1 FROM posts WHERE slug = :s AND id <> :id LIMIT 1");
      $chk->execute([':s'=>$slug, ':id'=>$id]);
    } else {
      $chk = $pdo->prepare("SELECT 1 FROM posts WHERE slug = :s LIMIT 1");
      $chk->execute([':s'=>$slug]);
    }
    if (!$chk->fetch()) break; // unik
    $suffix = ($try < 5) ? '-'.($try+2) : '-'.substr(sha1((string)microtime(true)),0,6);
    $slug = $baseSlug.$suffix;
    $try++;
  }

  /* ---------- Insert / Update ---------- */
  if ($id > 0) {
    // UPDATE
    $sets   = ["title=:t","slug=:s","excerpt=:e","body=:b","status=:st","updated_at=NOW()"];
    $params = [':t'=>$title, ':s'=>$slug, ':e'=>$excerpt, ':b'=>$body, ':st'=>$status, ':id'=>$id];

    if ($cover) { $sets[] = "cover_path=:c"; $params[':c'] = $cover; }

    if ($status === 'draft') {
      $sets[] = "published_at=NULL";
    } else {
      if ($useNow) { $sets[] = "published_at=NOW()"; }
      else         { $sets[] = "published_at=:pub"; $params[':pub'] = $pubParam; }
    }

    $sql = "UPDATE posts SET ".implode(',', $sets)." WHERE id=:id";
    $st  = $pdo->prepare($sql);
    $st->execute($params);

    audit_log('post.update','post',$id,[
      'status'=>$status,'title'=>$title,'slug'=>$slug,'scheduled'=>(!$useNow && $pubParam)?$pubParam:null
    ]);

  } else {
    // INSERT
    $author_id = (int)($_SESSION['user']['id'] ?? 0);
    if ($status === 'published') {
      if ($useNow) {
        $sql = "INSERT INTO posts (slug,title,excerpt,body,cover_path,published_at,status,author_id,created_at,updated_at)
                VALUES (:s,:t,:e,:b,:c,NOW(),:st,:a,NOW(),NOW())";
        $params = [':s'=>$slug, ':t'=>$title, ':e'=>$excerpt, ':b'=>$body, ':c'=>$cover, ':st'=>$status, ':a'=>$author_id];
      } else {
        $sql = "INSERT INTO posts (slug,title,excerpt,body,cover_path,published_at,status,author_id,created_at,updated_at)
                VALUES (:s,:t,:e,:b,:c,:pub,:st,:a,NOW(),NOW())";
        $params = [':s'=>$slug, ':t'=>$title, ':e'=>$excerpt, ':b'=>$body, ':c'=>$cover, ':pub'=>$pubParam, ':st'=>$status, ':a'=>$author_id];
      }
    } else {
      $sql = "INSERT INTO posts (slug,title,excerpt,body,cover_path,published_at,status,author_id,created_at,updated_at)
              VALUES (:s,:t,:e,:b,:c,NULL,:st,:a,NOW(),NOW())";
      $params = [':s'=>$slug, ':t'=>$title, ':e'=>$excerpt, ':b'=>$body, ':c'=>$cover, ':st'=>$status, ':a'=>$author_id];
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $id = (int)$pdo->lastInsertId();

    audit_log('post.create','post',$id,[
      'status'=>$status,'title'=>$title,'slug'=>$slug,'scheduled'=>(!$useNow && $pubParam)?$pubParam:null
    ]);
  }

  /* ---------- Sinkron kategori ---------- */
  $pdo->prepare("DELETE FROM post_categories WHERE post_id=:id")->execute([':id'=>$id]);
  if (!empty($catIds)) {
    $ins = $pdo->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (:p,:c)");
    foreach ($catIds as $cid) { $ins->execute([':p'=>$id, ':c'=>$cid]); }
  }

  $pdo->commit();
  flash_set('ok','Berita disimpan.');
  header('Location: /cms_post_edit.php?id='.$id); exit;

} catch (Throwable $e) {
  $pdo->rollBack();
  error_log('post_save: '.$e->getMessage());
  flash_set('err','Gagal menyimpan: '.$e->getMessage());
  header('Location:/cms_posts.php'); exit;
}
