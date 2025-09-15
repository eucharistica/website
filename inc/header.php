<nav class="navbar navbar-expand-lg bg-white sticky-top">
    <div class="container">
      <?php $u = $_SESSION['user'] ?? null; ?>
      <a class="navbar-brand d-flex align-items-center gap-2" href="/">
        <img src="/assets/logo-rsud.png" alt="RSUD Matraman" style="height:40px;width:auto">
        <span class="visually-hidden">RSUD Matraman</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div id="nav" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="#layanan">Layanan</a></li>
          <li class="nav-item"><a class="nav-link" href="#jadwal">Jadwal</a></li>
          <li class="nav-item"><a class="nav-link" href="#berita">Berita</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Informasi</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#kontak">Kontak</a></li>
              <li><a class="dropdown-item" href="#">Tarif & Regulasi</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>