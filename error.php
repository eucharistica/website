<?php
// /error.php — satu file untuk 403 & 404
// Penting: JANGAN include/require yang berpotensi error lagi.
// File ini dibuat standalone agar aman.

$code = (int)($_SERVER['REDIRECT_STATUS'] ?? 0);
if (!in_array($code, [403,404], true)) {
  // fallback jika dipanggil langsung
  $code = 404;
}
http_response_code($code);

$uri  = $_SERVER['REDIRECT_URL'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
$ip   = $_SERVER['REMOTE_ADDR'] ?? '-';
$ua   = $_SERVER['HTTP_USER_AGENT'] ?? '-';

// log ringkas ke error_log (server)
error_log(sprintf('[ERR %d] %s | IP=%s | UA=%s', $code, $uri, $ip, $ua));

$title = ($code===403) ? 'Akses Ditolak (403)' : 'Halaman Tidak Ditemukan (404)';
$desc  = ($code===403)
  ? 'Anda tidak memiliki izin untuk mengakses halaman ini.'
  : 'Maaf, halaman yang Anda minta tidak ditemukan atau sudah dipindahkan.';
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title) ?> • RSUD Matraman</title>
  <meta name="robots" content="noindex, nofollow">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{min-height:100vh;display:grid;place-items:center;background:linear-gradient(180deg,#fff,#f8fafc)}
    .card{border:1px solid #e5e7eb}
  </style>
</head>
<body>
  <main class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
          <div class="card-body p-4 text-center">
            <div class="display-5 fw-bold mb-2"><?= (int)$code ?></div>
            <h1 class="h4 mb-2"><?= htmlspecialchars($title) ?></h1>
            <p class="text-secondary mb-3"><?= htmlspecialchars($desc) ?></p>
            <div class="small text-secondary mb-4">URL: <code><?= htmlspecialchars($uri) ?></code></div>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
              <a class="btn btn-primary" href="/"><i class="bi bi-house me-1"></i> Kembali ke Beranda</a>
              <a class="btn btn-outline-secondary" href="/berita"><i class="bi bi-journal-text me-1"></i> Lihat Berita</a>
              <a class="btn btn-outline-secondary" href="/layanan"><i class="bi bi-collection me-1"></i> Lihat Layanan</a>
            </div>
          </div>
          <div class="card-footer small text-secondary text-center">
            Jika menurut Anda ini kesalahan, silakan hubungi admin.
          </div>
        </div>
      </div>
    </div>
  </main>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
