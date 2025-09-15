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
        <address class="mt-2 mb-0 text-secondary small">
          Jl. Kebon Kelapa Raya No.29, Jakarta Timur • Telp: 021-8581957
        </address>
      </div>

      <div class="col-lg-6 text-lg-end small">
        <!-- Ikon medsos -->
        <div class="footer-social mb-2">
          <!-- Ganti href ke akun resmi -->
          <a href="https://instagram.com/rsudmatraman" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="https://facebook.com/rsudmatraman"  target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="https://www.tiktok.com/@rsudmatraman" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
          <a href="https://youtube.com/@rsudmatraman"   target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
          <a href="https://twitter.com/rsudmatraman"    target="_blank" rel="noopener" aria-label="Twitter / X"><i class="bi bi-twitter-x"></i></a>
        </div>

        <div>
          <a class="me-3" href="/kebijakan-privasi">Privasi</a>
          <a class="me-3" href="/syarat">Syarat</a>
          <span>© <?= date('Y') ?> RSUD Matraman</span>
        </div>
      </div>
    </div>
  </div>
</footer>

<!-- Style ringan untuk footer (dibawa serta agar seragam di semua halaman) -->
<style>
  footer a { text-decoration: none; }
  .footer-logos .logo { height: 28px; width: auto; object-fit: contain; }
  @media (min-width: 992px){ .footer-logos .logo { height: 32px; } }

  .footer-social { display:flex; gap:.5rem; justify-content:flex-start; }
  @media (min-width: 992px){ .footer-social { justify-content:flex-end; } }
  .footer-social a {
    display:inline-flex; align-items:center; justify-content:center;
    width:36px; height:36px; border-radius:999px;
    background:#f1f5f9; color:#1f2937; border:1px solid #e5e7eb;
  }
  .footer-social a:hover { background: var(--brand, #0ea5e9); color:#fff; border-color: var(--brand, #0ea5e9); }
  .footer-social .bi { font-size:1.1rem; line-height:1; }
</style>
