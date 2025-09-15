<?php
require_once __DIR__.'/inc/app.php';
header('Content-Type: application/xml; charset=utf-8');

$pdo  = dbx();
$host = $_SERVER['HTTP_HOST'] ?? 'website.rsudmatraman.my.id';
$scheme = 'https';
$base = $scheme.'://'.$host;

$urls = [];

// Halaman utama
$urls[] = ['loc'=>"$base/",        'pri'=>'1.0', 'freq'=>'daily'];
$urls[] = ['loc'=>"$base/berita",  'pri'=>'0.8', 'freq'=>'hourly'];
$urls[] = ['loc'=>"$base/layanan", 'pri'=>'0.8', 'freq'=>'daily'];

// Posts (published & sudah tayang)
try{
  $st=$pdo->query("
    SELECT slug,
           GREATEST(COALESCE(updated_at, '1970-01-01'), COALESCE(published_at, '1970-01-01')) AS lastmod
    FROM posts
    WHERE status='published' AND COALESCE(published_at, NOW()) <= NOW()
    ORDER BY published_at DESC, id DESC
    LIMIT 2000
  ");
  foreach($st->fetchAll() as $r){
    $urls[] = ['loc'=>"$base/berita/".rawurlencode($r['slug']), 'pri'=>'0.7', 'freq'=>'daily', 'lastmod'=>$r['lastmod']];
  }
}catch(Throwable $e){}

# Services aktif
try{
  $st=$pdo->query("SELECT slug, updated_at FROM services WHERE status=1 ORDER BY sort_order ASC, id DESC LIMIT 2000");
  foreach($st->fetchAll() as $r){
    $urls[] = ['loc'=>"$base/layanan/".rawurlencode($r['slug']), 'pri'=>'0.6', 'freq'=>'weekly', 'lastmod'=>$r['updated_at']];
  }
}catch(Throwable $e){}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach($urls as $u): ?>
  <url>
    <loc><?= htmlspecialchars($u['loc'], ENT_XML1) ?></loc>
    <?php if(!empty($u['lastmod'])): ?><lastmod><?= date('c', strtotime($u['lastmod'])) ?></lastmod><?php endif; ?>
    <?php if(!empty($u['freq'])): ?><changefreq><?= $u['freq'] ?></changefreq><?php endif; ?>
    <?php if(!empty($u['pri'])): ?><priority><?= $u['pri'] ?></priority><?php endif; ?>
  </url>
<?php endforeach; ?>
</urlset>
