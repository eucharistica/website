<?php
require_once __DIR__ . '/../inc/app.php';
$pdo = dbx();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Arsip Berita • RSUD Matraman</title>
  <meta name="description" content="Arsip berita, pengumuman dan edukasi kesehatan RSUD Matraman.">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .card{border:1px solid #e5e7eb}
    .ratio-card{overflow:hidden;border-top-left-radius:.375rem;border-top-right-radius:.375rem}
    .img-cover{width:100%;height:100%;object-fit:cover;display:block}
  </style>
</head>
<body class="d-flex flex-column min-vh-100">

<?php include $_SERVER['DOCUMENT_ROOT'].'/inc/header.php'; ?>

<main class="flex-fill py-4">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
      <div>
        <h1 class="h4 mb-1">Arsip Berita</h1>
        <p class="text-secondary mb-0">Informasi, pengumuman, dan edukasi kesehatan.</p>
      </div>
      <form id="filterForm" class="row g-2">
        <div class="col-auto">
          <select id="category" class="form-select">
            <option value="">Semua Kategori</option>
          </select>
        </div>
        <div class="col-auto">
          <input id="q" class="form-control" type="search" placeholder="Cari berita…">
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search me-1"></i>Terapkan</button>
        </div>
      </form>
    </div>

    <div id="newsGrid" class="row g-3 g-lg-4"></div>
    <div class="text-center mt-3">
      <button id="btnMore" class="btn btn-outline-primary d-none">Muat Lebih</button>
    </div>
  </div>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'].'/inc/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script <?= csp_script_attr() ?>>
const API_BASE = "/api";
let page=1, pageSize=9, busy=false, hasMore=true;

// ===== Helpers =====
function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
function isAbsUrl(u){ return /^https?:\/\//i.test(u||''); }

/**
 * Gambar aman untuk lama/baru:
 * - Jika URL absolut: pakai <img> biasa.
 * - Jika relatif: coba varian -800w.webp dulu (lebih hemat), lalu fallback otomatis ke file asli jika 404.
 * - Tidak memakai <picture/srcset> di arsip agar tidak ada kasus browser memilih kandidat 404.
 */
function pictureHTML(imgPath, title){
  if(!imgPath) return '<div class="bg-light"></div>';

  let path = imgPath;
  if (!isAbsUrl(path)) {
    path = path.startsWith('/') ? path : `/${path}`;
  }

  const alt = escapeHtml(title||'');

  // Untuk URL absolut atau ekstensi tidak dikenal: langsung <img>
  const m = path.match(/\.([a-zA-Z0-9]+)$/);
  if (isAbsUrl(path) || !m) {
    return `<img src="${path}" alt="${alt}" class="img-cover" loading="lazy">`;
  }

  // Coba varian hemat: -800w.webp lalu fallback ke file asli jika gagal
  const ext = m[1].toLowerCase();
  if (['jpg','jpeg','png','webp'].includes(ext)) {
    const base = path.slice(0, -(ext.length+1));
    const candidate = `${base}-800w.webp`;
    return `<img src="${candidate}" alt="${alt}" class="img-cover" loading="lazy"
              onerror="this.onerror=null; this.removeAttribute('srcset'); this.removeAttribute('sizes'); this.src='${path}'">`;
  }

  // fallback umum
  return `<img src="${path}" alt="${alt}" class="img-cover" loading="lazy">`;
}

// ===== Categories =====
async function loadCategories(){
  const tryUrls = [`${API_BASE}/categories?type=post`, `${API_BASE}/categories`];
  for (const u of tryUrls){
    try{
      const r = await fetch(u, {cache:'no-store'}); if(!r.ok) continue;
      const j = await r.json(); const items = j.items || j.data || j;
      if(Array.isArray(items) && items.length){
        const sel=document.getElementById('category');
        for (const c of items){
          const opt=document.createElement('option');
          opt.value = c.slug || c.id || '';
          opt.textContent = c.name || c.title || '';
          sel.appendChild(opt);
        }
        break;
      }
    }catch(e){ /* ignore */ }
  }
}

// ===== Templating Kartu =====
function tplCard(n){
  const img = n.image || n.cover || n.cover_path || n.coverPath || null;
  const dateStr = n.publishedAt ? new Date(n.publishedAt).toLocaleDateString('id-ID',{ day:'2-digit', month:'short', year:'numeric'}) : '';
  return `
  <div class="col-md-6 col-lg-4">
    <article class="card h-100">
      <div class="ratio ratio-16x9 ratio-card">
        ${pictureHTML(img, n.title)}
      </div>
      <div class="card-body">
        <a href="/berita/${n.slug}" class="h6 d-block mb-1 link-dark text-decoration-none">${escapeHtml(n.title)}</a>
        <div class="text-secondary small mb-2"><i class="bi bi-calendar-event me-1"></i>${dateStr}</div>
        <p class="card-text small text-secondary mb-2">${escapeHtml(n.excerpt || '')}</p>
        <a class="btn btn-sm btn-outline-primary" href="/berita/${n.slug}">Lihat</a>
      </div>
    </article>
  </div>`;
}

// ===== Load News =====
async function loadNews(reset=false){
  if (busy || (!hasMore && !reset)) return; busy=true;
  try{
    if (reset){ page=1; hasMore=true; document.getElementById('newsGrid').innerHTML = ''; }
    const params = new URLSearchParams({ page:String(page), pageSize:String(pageSize) });
    const cat = document.getElementById('category').value.trim();
    const q   = document.getElementById('q').value.trim();
    if (cat) params.set('category', cat);
    if (q)   params.set('q', q);
    const res = await fetch(`${API_BASE}/news?`+params.toString(), {cache:'no-store'});
    const j = await res.json();
    const items = j.items || [];
    const grid = document.getElementById('newsGrid');
    grid.insertAdjacentHTML('beforeend', items.map(tplCard).join(''));
    hasMore = !!j.hasMore;
    document.getElementById('btnMore').classList.toggle('d-none', !hasMore);
    if (hasMore) page++;
  }catch(e){ console.error(e); }
  finally{ busy=false; }
}

document.getElementById('btnMore').addEventListener('click', ()=>loadNews(false));
document.getElementById('filterForm').addEventListener('submit', (e)=>{ e.preventDefault(); loadNews(true); });

loadCategories();
loadNews(true);
</script>
</body>
</html>
