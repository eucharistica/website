<?php
require_once __DIR__ . '/inc/app.php';
if (is_logged_in()) { header('Location: /dashboard.php'); exit; }
$err = flash_get('err');
$next = $_GET['next'] ?? '';
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login • RSUD Matraman</title>
  <meta name="description" content="Masuk ke Dashboard Staf atau Portal Pasien RSUD Matraman." />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    :root { --brand: #0ea5e9; --brand-dark:#0284c7; }
    body { font-optical-sizing:auto; }
    /* Navbar */
    .navbar { box-shadow: 0 2px 20px rgba(0,0,0,.06); }
    .navbar .btn-login { border-radius: 999px; padding:.45rem .9rem; }
    .navbar .dropdown-menu { --bs-dropdown-bg:#ffffff; --bs-dropdown-link-color:#1f2937; --bs-dropdown-link-hover-bg:#f1f5f9; }
    /* Hero */
    .hero { background: radial-gradient(1100px 600px at 10% -10%, rgba(14,165,233,.15), transparent),
                     radial-gradient(1000px 400px at 110% 10%, rgba(14,165,233,.12), transparent),
                     linear-gradient(180deg, #fff, #f8fafc 60%, #f1f5f9); padding: 3rem 0; }
    .hero h1 { letter-spacing:.2px; }
    .rounded-2xl { border-radius:1rem; }
    .shadow-soft { box-shadow: 0 10px 30px rgba(2,8,23,.06); }
    /* Brand logo sizing */
    .navbar-brand .brand-logo { height: 36px; width: auto; }
    @media (min-width: 992px){ .navbar-brand .brand-logo { height: 40px; } }
    .brand-logo-sm { height: 28px; width: auto; }
    /* Login card */
    .card-login{border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 30px rgba(2,8,23,.06);} 
    .form-control:focus{box-shadow:0 0 0 .25rem rgba(13,110,253,.15)}
  </style>
</head>
<body class="d-flex flex-column min-vh-100">
  <!-- NAVBAR (dynamic: badge & profil saat login) -->
  <nav class="navbar navbar-expand-lg bg-white sticky-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="/">
        <img src="/assets/logo-rsud.png" alt="RSUD Matraman" class="brand-logo" />
        <span class="visually-hidden">RSUD Matraman</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div id="nav" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="/#layanan">Layanan</a></li>
          <li class="nav-item"><a class="nav-link" href="/#jadwal">Jadwal</a></li>
          <li class="nav-item"><a class="nav-link" href="/#berita">Berita</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Informasi</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="/#kontak">Kontak</a></li>
              <li><a class="dropdown-item" href="#">Tarif & Regulasi</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>


  <!-- SECTION: Login inside landing template -->
  <main class="flex-grow-1">
    <section class="hero">
      <div class="container">
        <div class="row justify-content-center g-4">
          <div class="col-lg-8 col-xl-6">
            <div class="text-center mb-3">
              <h1 class="h3 fw-bold mb-1">Portal Akun</h1>
              <p class="text-secondary mb-0">Gunakan akun staf (username & password) atau Google untuk staf/pasien.</p>
            </div>

            <div class="card card-login">
              <div class="card-body p-4 p-lg-4">
                <?php if($err): ?>
                  <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($err) ?></div>
                <?php endif; ?>
                <form method="post" action="/do_login.php" autocomplete="off" novalidate>
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
                  <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                  </div>
                  <div class="mb-3 position-relative">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                      <input id="pwd" type="password" class="form-control" name="password" required>
                      <button class="btn btn-outline-secondary" type="button" id="togglePwd" aria-label="Tampilkan/Sembunyikan">
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div class="d-grid">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk</button>
                  </div>
                </form>

                <div class="text-center text-secondary small my-3">atau</div>
                <div class="d-grid gap-2">
                  <a class="btn btn-outline-dark" href="/oauth_google_start.php?intent=staff">
                    <img alt="Google" src="https://img.icons8.com/color/28/000000/google-logo.png"> Masuk/Daftar dengan Google
                  </a>
                </div>
              </div>
            </div>

          </div> <!-- /col -->
        </div> <!-- /row -->
      </div> <!-- /container -->
    </section>
  </main>

  <!-- FOOTER -->
  <?php include $_SERVER['DOCUMENT_ROOT'].'/inc/footer.php'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
      // Footer year
      const yEl = document.getElementById('y'); if (yEl) yEl.textContent = new Date().getFullYear();
      // Toggle password
      const t = document.getElementById('togglePwd');
      if (t) t.addEventListener('click', function(){
        const p = document.getElementById('pwd');
        if(p.type==='password'){ p.type='text'; this.innerHTML='<i class="bi bi-eye-slash"></i>'; }
        else { p.type='password'; this.innerHTML='<i class="bi bi-eye"></i>'; }
      });
  </script>
</body>
</html>