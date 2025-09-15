<?php
// /berita/view.php — Detail berita + Meta Share
require_once __DIR__ . '/../inc/app.php';

$pdo = dbx();
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { http_response_code(404); include $_SERVER['DOCUMENT_ROOT'].'/error.php'; exit; }

// Ambil post (published & sudah tayang)
$st = $pdo->prepare("
  SELECT id, title, slug, excerpt, body, cover_path, status,
         published_at, updated_at, created_at
  FROM posts
  WHERE slug = :slug
    AND status = 'published'
    AND COALESCE(published_at, NOW()) <= NOW()
  LIMIT 1
");
$st->execute([':slug'=>$slug]);
$post = $st->fetch();

if (!$post) {
  http_response_code(404);
  include $_SERVER['DOCUMENT_ROOT'].'/error.php'; exit;
}

// Ambil kategori (opsional)
$cats = [];
try{
  $stc = $pdo->prepare("
    SELECT c.name, c.slug
    FROM post_categories pc
    JOIN categories c ON c.id = pc.category_id
    WHERE pc.post_id = :id
    ORDER BY c.name
  ");
  $stc->execute([':id'=>$post['id']]);
  $cats = $stc->fetchAll();
}catch(Throwable $e){}

// Helper absolut URL
$host = $_SERVER['HTTP_HOST'] ?? 'website.rsudmatraman.my.id';
$scheme = 'https';
$base = $scheme.'://'.$host;
$canon = $base.'/berita/'.rawurlencode($post['slug']);

function abs_url($base, $path) {
  if (!$path) return null;
  if (preg_match('~^https?://~i', $path)) return $path;
  if ($path[0] !== '/') $path = '/'.$path;
  return $base.$path;
}

$coverAbs = abs_url($base, $post['cover_path'] ?: '/assets/hero-rsud.jpg');
$desc = $post['excerpt'] ?: mb_substr(strip_tags($post['body']), 0, 160);
$published = $post['published_at'] ?: $post['created_at'];
$updated   = $post['updated_at'] ?: $published;
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($post['title']) ?> • RSUD Matraman</title>
  <meta name="description" content="<?= htmlspecialchars($desc) ?>">

  <!-- Canonical -->
  <link rel="canonical" href="<?= htmlspecialchars($canon) ?>">

  <!-- Favicon -->
  <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="/assets/favicon.ico" type="image/x-icon">

  <!-- Open Graph -->
  <meta property="og:type" content="article">
  <meta property="og:site_name" content="RSUD Matraman">
  <meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($canon) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($coverAbs) ?>">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($post['title']) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($coverAbs) ?>">

  <!-- JSON-LD NewsArticle -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "NewsArticle",
    "headline": <?= json_encode($post['title'], JSON_UNESCAPED_UNICODE) ?>,
    "datePublished": "<?= date('c', strtotime($published)) ?>",
    "dateModified": "<?= date('c', strtotime($updated)) ?>",
    "image": [<?= json_encode($coverAbs, JSON_UNESCAPED_SLASHES) ?>],
    "author": { "@type": "Organization", "name": "RSUD Matraman" },
    "publisher": {
      "@type": "Organization",
      "name": "RSUD Matraman",
      "logo": { "@type": "ImageObject", "url": <?= json_encode(abs_url($base,'/assets/logo-rsud.png')) ?> }
    },
    "mainEntityOfPage": <?= json_encode($canon, JSON_UNESCAPED_SLASHES) ?>
  }
  </script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .navbar { box-shadow: 0 2px 20px rgba(0,0,0,.06); }
    .cover-wrap { overflow:hidden; border-bottom:1px solid #e5e7eb; background:#000; }
    .cover { width:100%; max-height:60vh; object-fit:cover; object-position:center; display:block; }
    .content img{ max-width:100%; height:auto; }
    .content figure{ max-width:100%; }
  </style>
</head>
<body class="d-flex flex-column min-vh-100">

<?php include $_SERVER['DOCUMENT_ROOT'].'/inc/header.php'; ?>

<?php if ($post['cover_path']): ?>
  <div class="cover-wrap">
    <?= picture_tag(ltrim($post['cover_path'],'/'), $post['title'], 'cover', '100vw') ?>
  </div>
<?php endif; ?>

<main class="flex-fill py-4">
  <div class="container">
    <header class="mb-3">
      <h1 class="h3 mb-2"><?= htmlspecialchars($post['title']) ?></h1>
      <div class="text-secondary small d-flex flex-wrap align-items-center gap-2">
        <span><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($published)) ?></span>
        <?php if ($cats): ?><span>•</span>
          <span>
            <?php foreach($cats as $i=>$c): ?>
              <a class="badge text-bg-light border me-1" href="/berita?category=<?= htmlspecialchars($c['slug']) ?>">
                <i class="bi bi-tag"></i> <?= htmlspecialchars($c['name']) ?>
              </a>
            <?php endforeach; ?>
          </span>
        <?php endif; ?>
      </div>
    </header>

    <article class="content">
      <?= $post['body'] /* diasumsikan dari editor yang sudah disanitasi */ ?>
    </article>
  </div>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'].'/inc/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
