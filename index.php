<?php require_once __DIR__.'/inc/app.php'; ?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Favicon -->
  <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="/assets/favicon.ico" type="image/x-icon">
  <title>RSUD Matraman</title>
  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="RSUD Matraman">
  <meta property="og:title" content="RSUD Matraman">
  <meta property="og:description" content="Pelayanan kesehatan cepat, tepat, dan ramah.">
  <meta property="og:url" content="https://website.rsudmatraman.my.id/">
  <meta property="og:image" content="https://website.rsudmatraman.my.id/assets/hero-rsud.jpg">
  
  <link rel="manifest" href="/manifest.webmanifest">
  <meta name="theme-color" content="#0ea5e9">
  
  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="RSUD Matraman">
  <meta name="twitter:description" content="Pelayanan kesehatan cepat, tepat, dan ramah.">
  <meta name="twitter:image" content="https://website.rsudmatraman.my.id/assets/hero-rsud.jpg">

  <meta name="description" content="RSUD Matraman – Pelayanan kesehatan cepat, tepat, dan ramah." />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  
  <style>
    :root { --brand:#0ea5e9; --brand-dark:#0284c7; }
    body { font-optical-sizing:auto; }
    /* Navbar */
    .navbar { box-shadow:0 2px 20px rgba(0,0,0,.06); }
    .navbar .btn-login { border-radius:999px; padding:.45rem .9rem; }
    .navbar .dropdown-menu { --bs-dropdown-bg:#fff; --bs-dropdown-link-color:#1f2937; --bs-dropdown-link-hover-bg:#f1f5f9; }
    /* Hero */
    .hero { background:
              radial-gradient(1100px 600px at 10% -10%, rgba(14,165,233,.15), transparent),
              radial-gradient(1000px 400px at 110% 10%, rgba(14,165,233,.12), transparent),
              linear-gradient(180deg, #fff, #f8fafc 60%, #f1f5f9);
            padding:4rem 0 2rem; }
    .hero h1 { letter-spacing:.2px; }
    .hero-cta .btn { border-radius:.9rem; }
    .stat-badge { border:1px solid #e5e7eb; border-radius:1rem; padding:.5rem 1rem; background:#fff; }
    /* Cards */
    .card { border:1px solid #e5e7eb; }
    /* JANGAN paksa semua card img jadi 16:9 cover — biarkan spesifik per komponen */
    .card img { display:block; max-width:100%; height:auto; }
    .service-icon { width:48px; height:48px; display:grid; place-items:center; border-radius:12px; background:#e0f2fe; color:#0369a1; }
    /* Footer */
    footer a { text-decoration:none; }
    .footer-logos .logo { height:28px; width:auto; object-fit:contain; }
    @media (min-width:992px){ .footer-logos .logo { height:32px; } }
    /* Utilities */
    .rounded-2xl { border-radius:1rem; }
    .shadow-soft { box-shadow:0 10px 30px rgba(2,8,23,.06); }
    .bg-brand { background:var(--brand); }
    .text-brand { color:var(--brand-dark); }
    /* Offcanvas search */
    .doctor-item { border-bottom:1px dashed #e5e7eb; padding:.75rem 0; }
    /* Brand logo sizing */
    .navbar-brand .brand-logo { height:36px; width:auto; }
    @media (min-width:992px){ .navbar-brand .brand-logo { height:40px; } }
    .brand-logo-sm { height:28px; width:auto; }

    /* News card images — tampil utuh (contain) dalam persegi */
    .ratio-card { overflow:hidden; border-top-left-radius:.375rem; border-top-right-radius:.375rem; background:#fff; }
    .ratio .img-cover{width:100%;height:100%;object-fit:cover;display:block}
    .img-news  { width:100%; height:100%; object-fit:contain !important; background:#fff; display:block; }
    
    /* Hero carousel */
    #heroCarousel .carousel-item { aspect-ratio: 16/9; }
    #heroCarousel .carousel-item img { width:100%; height:100%; object-fit:cover; display:block; }
    
    /* Footer: sosial media */
    .footer-social { display:flex; gap:.5rem; justify-content:flex-start; }
    @media (min-width: 992px){ .footer-social { justify-content:flex-end; } }
    .footer-social a {
      display:inline-flex; align-items:center; justify-content:center;
      width:36px; height:36px; border-radius:999px;
      background:#f1f5f9; color:#1f2937; border:1px solid #e5e7eb;
      text-decoration:none;
    }
    .footer-social a:hover { background: var(--brand); color:#fff; border-color: var(--brand); }
    .footer-social .bi { font-size:1.1rem; line-height:1; }


  </style>
</head>
<body class="d-flex flex-column min-vh-100">
  <!-- NAVBAR (mobile-friendly + badge role + profil) -->
  <nav class="navbar navbar-expand-lg bg-white sticky-top">
    <div class="container">
      <?php $u = $_SESSION['user'] ?? null; ?>
      <a class="navbar-brand d-flex align-items-center gap-2" href="/">
        <img src="assets/logo-rsud.png" alt="RSUD Matraman" class="brand-logo" />
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

          <?php if (is_logged_in()): ?>
            <!-- MOBILE-ONLY -->
            <li class="nav-item d-lg-none">
              <span class="nav-link disabled small">
                Anda login sebagai <strong><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></strong>
              </span>
            </li>
            <li class="nav-item d-lg-none"><a class="nav-link" href="<?= is_staff_role($u['role']) ? 'dashboard.php' : 'portal.php' ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
            <li class="nav-item d-lg-none"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> Keluar</a></li>
          <?php else: ?>
            <li class="nav-item d-lg-none"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a></li>
          <?php endif; ?>
        </ul>

        <!-- DESKTOP-ONLY -->
        <div class="ms-lg-3 d-none d-lg-flex align-items-center gap-2">
          <?php if (is_logged_in()): ?>
            <div class="dropdown">
              <a class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-display="static" aria-expanded="false">
                <?php if (!empty($u['avatar_url'])): ?>
                  <img src="<?= htmlspecialchars($u['avatar_url']) ?>" alt="" class="rounded-circle" style="width:22px;height:22px;object-fit:cover">
                <?php else: ?>
                  <i class="bi bi-person-circle"></i>
                <?php endif; ?>
                <span><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= is_staff_role($u['role']) ? 'dashboard.php' : 'portal.php' ?>"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
              </ul>
            </div>
          <?php else: ?>
            <a href="login.php" class="btn btn-outline-primary btn-login"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <div class="stat-badge d-inline-flex align-items-center gap-2 mb-3">
            <i class="bi bi-shield-check"></i> Akreditasi Paripurna • IGD 24 Jam • BPJS
          </div>
          <h1 class="display-5 fw-bold mb-3">Pelayanan cepat, tepat, dan ramah di <span class="text-brand">RSUD Matraman</span></h1>
          <p class="lead text-secondary">Akses informasi layanan, daftar jadwal dokter, dan berita terbaru rumah sakit.</p>
          <div class="hero-cta d-flex gap-2 mt-3">
            <a class="btn btn-primary btn-lg" data-bs-toggle="offcanvas" href="#offcanvasJadwal"><i class="bi bi-calendar-week me-1"></i>Cek Jadwal Dokter</a>
            <a class="btn btn-outline-secondary btn-lg" href="#layanan"><i class="bi bi-hospital me-1"></i>Lihat Layanan</a>
          </div>
          <div class="d-flex gap-3 mt-4 flex-wrap">
            <div class="d-flex align-items-center gap-2"><i class="bi bi-telephone-outbound"></i> <a href="tel:021555555" class="link-dark">021-8581957</a></div>
            <div class="d-flex align-items-center gap-2"><i class="bi bi-whatsapp"></i> <a href="https://wa.me/6281234567890" target="_blank" class="link-dark">WhatsApp</a></div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="p-2 p-lg-4">
            <!-- HERO CAROUSEL -->
            <div id="heroCarousel" class="carousel slide rounded-2xl shadow-soft overflow-hidden">
              <div class="carousel-inner" id="heroCarouselInner">
                <!-- diisi via JS -->
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Sebelumnya</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Berikutnya</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- HIGHLIGHT LAYANAN -->
  <section id="layanan" class="py-5">
    <div class="container">
      <div class="d-flex align-items-end justify-content-between mb-3 mb-lg-4">
        <div>
          <h2 class="h3 mb-1">Layanan Unggulan</h2>
          <p class="text-secondary mb-0">Beberapa layanan yang banyak digunakan masyarakat.</p>
        </div>
        <a href="/layanan" class="btn btn-sm btn-outline-secondary">Semua layanan <i class="bi bi-arrow-right-short"></i></a>
      </div>
      <div id="layananGrid" class="row g-3 g-lg-4"><!-- populated via JS --></div>
    </div>
  </section>

  <!-- CTA STRIP -->
  <section class="py-4" id="jadwal">
    <div class="container">
      <div class="bg-brand text-white rounded-2xl p-4 p-lg-5 d-flex flex-column flex-lg-row align-items-center justify-content-between">
        <div class="mb-3 mb-lg-0">
          <h3 class="h4 mb-1">Ingin bertemu dokter tertentu?</h3>
          <p class="mb-0">Cari jadwal praktek dan kuota online.</p>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-light btn-lg" data-bs-toggle="offcanvas" href="#offcanvasJadwal"><i class="bi bi-search me-1"></i> Cari Jadwal</a>
          <a class="btn btn-outline-light btn-lg" href="/pendaftaran"><i class="bi bi-pencil-square me-1"></i> Daftar Online</a>
        </div>
      </div>
    </div>
  </section>

  <!-- BERITA -->
  <section id="berita" class="py-5">
    <div class="container">
      <div class="d-flex align-items-end justify-content-between mb-3 mb-lg-4">
        <div>
          <h2 class="h3 mb-1">Berita Terbaru</h2>
          <p class="text-secondary mb-0">Informasi, pengumuman, dan edukasi kesehatan.</p>
        </div>
        <a href="/berita" class="btn btn-sm btn-outline-secondary">Arsip Berita <i class="bi bi-journal-text ms-1"></i></a>
      </div>
      <div id="newsGrid" class="row g-3 g-lg-4"><!-- populated via JS --></div>
      <div class="text-center mt-3">
        <button id="loadMoreBtn" class="btn btn-outline-primary">Muat Lebih Banyak</button>
      </div>
    </div>
  </section>
  
  <section id="socmed" class="py-5">
    <div class="container">
        <div>
          <h2 class="h3 mb-1">Konten Instagram</h2>
        </div>
        <!--<div class="embedsocial-hashtag" data-ref="e2cc47671d47142f01e8bbfe30c603538f2b6a97"></div> -->
    </div>						
  </section>

  <!-- FOOTER -->
    <footer class="py-4 border-top" id="kontak">
      <div class="container">
        <div class="row g-3 align-items-center">
          <div class="col-lg-6">
            <div class="d-flex align-items-center gap-3 flex-wrap footer-logos">
              <img src="/assets/logo-footer.png" alt="RSUD Matraman" class="logo" />
              <img src="/assets/logo-bpjs.png" alt="BPJS Kesehatan" class="logo" />
              <img src="/assets/logo-snars.png" alt="SNARS" class="logo" />
              <span class="visually-hidden">RSUD Matraman • BPJS Kesehatan • SNARS</span>
            </div>
            <address class="mt-2 mb-0 text-secondary small">Jl. Kebon Kelapa Raya No.29, Jakarta Timur • Telp: 021-8581957</address>
          </div>
    
          <div class="col-lg-6 text-lg-end small">
            <!-- Ikon medsos -->
            <div class="footer-social mb-2">
              <!-- Ganti href="#" dengan URL akun resmi -->
              <a href="https://instagram.com/rsudmatraman" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
              <a href="https://facebook.com/rsudmatraman" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
              <a href="https://www.tiktok.com/@rsudmatraman" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
              <a href="https://youtube.com/@rsudmatraman" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
              <a href="https://twitter.com/rsudmatraman" target="_blank" rel="noopener" aria-label="Twitter / X"><i class="bi bi-twitter-x"></i></a>
            </div>
    
            <div>
              <a class="me-3" href="/kebijakan-privasi">Privasi</a>
              <a class="me-3" href="/syarat">Syarat</a>
              <span>© <span id="y"></span> RSUD Matraman</span>
            </div>
          </div>
        </div>
      </div>
    </footer>


  <!-- OFFCANVAS: Cek Jadwal Dokter -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasJadwal" aria-labelledby="offcanvasJadwalLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasJadwalLabel"><i class="bi bi-calendar2-check me-2"></i>Cek Jadwal Dokter</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body small">
      <div class="row g-2 mb-3">
        <div class="col-12">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input id="qDoctor" type="search" class="form-control" placeholder="Cari nama dokter / poli" />
          </div>
        </div>
        <div class="col-6">
          <select id="filterPoli" class="form-select" aria-label="Pilih poli">
            <option value="">Semua Poli</option>
          </select>
        </div>
        <div class="col-6">
          <select id="filterHari" class="form-select" aria-label="Pilih hari">
            <option value="">Semua Hari</option>
            <option value="SENIN">Senin</option><option value="SELASA">Selasa</option><option value="RABU">Rabu</option>
            <option value="KAMIS">Kamis</option><option value="JUMAT">Jumat</option><option value="SABTU">Sabtu</option><option value="MINGGU">Minggu</option>
          </select>
        </div>
      </div>
      <div id="doctorList"><div class="text-center text-secondary py-4">Ketik / pilih filter untuk mencari jadwal…</div></div>
      <div class="d-grid mt-3"><button id="btnMoreJadwal" class="btn btn-outline-primary d-none">Muat Lebih</button></div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script <?= csp_script_attr() ?>> (function(d, s, id) { var js; if (d.getElementById(id)) {return;} js = d.createElement(s); js.id = id; js.src = "https://embedsocial.com/cdn/ht.js"; d.getElementsByTagName("head")[0].appendChild(js); }(document, "script", "EmbedSocialHashtagScript")); 
  </script>
  <script src="https://website-widgets.pages.dev/dist/sienna.min.js" defer></script>
  <script <?= csp_script_attr() ?>>
    const API_BASE = "./api";
    const IMG_PLACEHOLDER = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='640' height='640'%3E%3Crect width='100%25' height='100%25' fill='%23f8fafc'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-family='Arial' font-size='20'%3EMemuat gambar…%3C/text%3E%3C/svg%3E";

    // Footer year
    document.getElementById('y').textContent = new Date().getFullYear();

    // ==== Layanan (grid 6) ====
    async function loadServices() {
      try {
        const res = await fetch(`/api/services?limit=6`, {cache:'no-store'});
        const data = await res.json();
        const grid = document.getElementById('layananGrid');
        grid.innerHTML = (data?.items || []).map(s => `
          <div class="col-6 col-md-4 col-lg-4">
            <article class="card h-100">
              <div class="ratio ratio-16x9">
                ${s.cover ? `<img src="${s.cover}" alt="${s.name}" class="img-cover" loading="lazy">` : `<div class="bg-light"></div>`}
              </div>
              <div class="card-body">
                <a href="/layanan/${s.slug}" class="h6 d-block mb-1 link-dark text-decoration-none">${s.name}</a>
                <p class="card-text small text-secondary mb-2">${s.excerpt ? s.excerpt : ''}</p>
                <a href="/layanan/${s.slug}" class="btn btn-sm btn-outline-primary">Lihat Detail</a>
              </div>
            </article>
          </div>`).join('');
      } catch (e) { console.error(e); }
    }

    // ==== Berita (lazy load + gambar utuh persegi) ====
    let newsPage = 1; let loadingNews = false; const pageSize = 6;
    async function loadNews(append=true) {
      if (loadingNews) return; loadingNews = true;
      try {
        const res = await fetch(`${API_BASE}/news?page=${newsPage}&pageSize=${pageSize}`, { cache: 'no-store' });
        const data = await res.json();
        const grid = document.getElementById('newsGrid');
        const cards = (data?.items || []).map(n => `
          <div class="col-md-6 col-lg-4">
            <article class="card h-100 card--news">
              <div class="ratio ratio-1x1 ratio-card">
                <img data-src="${n.image}" src="${IMG_PLACEHOLDER}" class="lazyimg img-news" alt="${n.title}" loading="lazy" />
              </div>
              <div class="card-body">
                <a href="/berita/${n.slug}" class="h6 d-block mb-1 link-dark text-decoration-none">${n.title}</a>
                <div class="text-secondary small mb-2"><i class="bi bi-calendar-event me-1"></i>${new Date(n.publishedAt).toLocaleDateString('id-ID',{ day:'2-digit', month:'short', year:'numeric'})}</div>
                <p class="card-text small text-secondary mb-2">${n.excerpt || ''}</p>
                <a class="btn btn-sm btn-outline-primary" href="/berita/${n.slug}">Baca</a>
              </div>
            </article>
          </div>`).join('');
        if (append) grid.insertAdjacentHTML('beforeend', cards); else grid.innerHTML = cards;
        observeLazyImages();
        if (!(data?.hasMore)) { document.getElementById('loadMoreBtn').classList.add('d-none'); }
        else { document.getElementById('loadMoreBtn').classList.remove('d-none'); }
      } catch(e){ console.error(e); }
      finally { loadingNews = false; }
    }
    document.getElementById('loadMoreBtn').addEventListener('click', ()=>{ newsPage++; loadNews(true); });

    function observeLazyImages(){
      const imgs = document.querySelectorAll('img.lazyimg');
      const io = new IntersectionObserver((entries, observer)=>{
        entries.forEach(entry=>{
          if(entry.isIntersecting){
            const img = entry.target; img.src = img.dataset.src; observer.unobserve(img);
          }
        });
      }, { rootMargin: '200px' });
      imgs.forEach(i=> io.observe(i));
    }

    // ==== Cek Jadwal Dokter (offcanvas search) ====
    const qDoctor = document.getElementById('qDoctor');
    const doctorList = document.getElementById('doctorList');
    const filterPoli = document.getElementById('filterPoli');
    const filterHari = document.getElementById('filterHari');
    const btnMoreJadwal = document.getElementById('btnMoreJadwal');

    let jadwalState = { page: 1, perPage: 50, q: '', kd_poli: '', hari: '' };
    let isLoadingJadwal = false;
    let poliLoaded = false;

    document.getElementById('offcanvasJadwal').addEventListener('shown.bs.offcanvas', async ()=>{
      if (!poliLoaded) { await loadPoli(); poliLoaded = true; }
      if (!doctorList.dataset.loaded) { searchJadwal(true); }
    });

    async function loadPoli(){
      try{
        const res = await fetch(`${API_BASE}/poliklinik`);
        const json = await res.json();
        if (json?.ok && Array.isArray(json.data)){
          for (const p of json.data){
            const opt = document.createElement('option');
            opt.value = p.kd_poli; opt.textContent = p.nm_poli; filterPoli.appendChild(opt);
          }
        }
      }catch(e){ console.error(e); }
    }

    let typingTimer;
    qDoctor.addEventListener('input', ()=>{ clearTimeout(typingTimer); typingTimer = setTimeout(()=>{ jadwalState.q = qDoctor.value.trim(); searchJadwal(true); }, 300); });
    qDoctor.addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); jadwalState.q = qDoctor.value.trim(); searchJadwal(true); }});
    filterPoli.addEventListener('change', ()=>{ jadwalState.kd_poli = filterPoli.value; searchJadwal(true); });
    filterHari.addEventListener('change', ()=>{ jadwalState.hari = filterHari.value; searchJadwal(true); });
    btnMoreJadwal.addEventListener('click', ()=>{ jadwalState.page++; searchJadwal(false); });

    async function searchJadwal(resetPage){
      if (isLoadingJadwal) return; isLoadingJadwal = true;
      if (resetPage){ jadwalState.page = 1; doctorList.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>'; }
      try{
        const params = new URLSearchParams();
        if (jadwalState.q) params.set('q', jadwalState.q);
        if (jadwalState.kd_poli) params.set('kd_poli', jadwalState.kd_poli);
        if (jadwalState.hari) params.set('hari', jadwalState.hari);
        params.set('page', String(jadwalState.page));
        params.set('per_page', String(jadwalState.perPage));

        const res = await fetch(`${API_BASE}/jadwal?${params.toString()}`);
        const json = await res.json();
        if (!json?.ok){ throw new Error(json?.error || 'Gagal mengambil data'); }

        const rows = Array.isArray(json.data) ? json.data : [];
        const html = rows.map(toJadwalItem).join('');
        if (resetPage){ doctorList.innerHTML = html || '<div class="text-center text-secondary py-4">Tidak ditemukan</div>'; doctorList.dataset.loaded = '1'; }
        else { doctorList.insertAdjacentHTML('beforeend', html); }

        const meta = json.meta || {}; const total = meta.total || 0; const shown = Math.min(jadwalState.page * jadwalState.perPage, total);
        if (shown < total) { btnMoreJadwal.classList.remove('d-none'); } else { btnMoreJadwal.classList.add('d-none'); }
      }catch(e){
        console.error(e);
        doctorList.innerHTML = '<div class="text-danger small">Gagal memuat jadwal.</div>';
        btnMoreJadwal.classList.add('d-none');
      }finally{ isLoadingJadwal = false; }
    }
    
    async function loadCarousel(){
      try{
        const res = await fetch('/api/carousel',{cache:'no-store'});
        const json = await res.json();
        const items = json?.items || [];
        const inner = document.getElementById('heroCarouselInner');
        if(!inner) return;
        if(!items.length){
          inner.innerHTML = `<div class="carousel-item active">
            <img src="assets/hero-rsud.jpg" alt="RSUD Matraman">
          </div>`;
          return;
        }
        inner.innerHTML = items.map((it,idx)=>{
          const img = `<img src="${it.img}" alt="Carousel ${idx+1}" loading="lazy">`;
          const wrap = it.url ? `<a href="${it.url}" target="_blank" rel="noopener">${img}</a>` : img;
          return `<div class="carousel-item ${idx===0?'active':''}">${wrap}</div>`;
        }).join('');
      }catch(e){ console.error(e); }
    }

    function toJadwalItem(r){
      const lower = (r.hari_kerja||'').toLowerCase();
      const dayCap = lower ? lower.charAt(0).toUpperCase()+lower.slice(1) : '';
      const jam = `${r.jam_mulai || ''}–${r.jam_selesai || ''}`;
      return `<div class="doctor-item">
        <div class="d-flex justify-content-between flex-wrap gap-2">
          <div>
            <strong>${r.nm_dokter || '-'}</strong>
            <div class="small text-secondary">${r.nm_poli || ''}</div>
          </div>
        </div>
        <div class="small mt-2">
          <span class="badge text-bg-light border me-1 mb-1"><i class="bi bi-calendar-week me-1"></i>${dayCap}</span>
          <span class="badge text-bg-light border me-1 mb-1"><i class="bi bi-clock me-1"></i>${jam}</span>
        </div>
      </div>`;
    }

    // Init
    loadServices();
    loadNews();
    loadCarousel();

  </script>
</body>
</html>
