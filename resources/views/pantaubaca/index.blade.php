<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PantauBaca — Platform Literasi & Pemantauan Membaca Digital</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('pantaubaca/css/style.css') }}?v={{ file_exists(public_path('pantaubaca/css/style.css')) ? filemtime(public_path('pantaubaca/css/style.css')) : time() }}" />
  <style>
    body {
      font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif;
    }
    .landing-nav {
      position: sticky;
      top: 0;
      z-index: 100;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
      box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
    }
    .landing-nav-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 74px;
    }
    .landing-nav-links {
      display: flex;
      align-items: center;
      gap: 32px;
    }
    .landing-nav-links a {
      color: var(--text-secondary);
      text-decoration: none;
      font-weight: 600;
      font-size: 0.92rem;
      transition: var(--transition-fast);
      position: relative;
    }
    .landing-nav-links a:hover {
      color: var(--primary);
    }
    .hero-wrapper {
      padding: 80px 0 72px;
      background: 
        radial-gradient(circle at 85% 15%, rgba(67, 97, 238, 0.12) 0%, transparent 45%),
        radial-gradient(circle at 15% 85%, rgba(114, 9, 183, 0.08) 0%, transparent 45%),
        linear-gradient(180deg, #FFFFFF 0%, #F4F6FC 100%);
      position: relative;
      overflow: hidden;
    }
    .hero-container {
      display: grid;
      grid-template-columns: 1.15fr 0.85fr;
      align-items: center;
      gap: 52px;
    }
    .hero-badge-pill {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 8px 18px;
      border-radius: var(--radius-full);
      background: #EEF2FF;
      color: var(--primary);
      border: 1px solid #C7D2FE;
      font-size: 0.86rem;
      font-weight: 700;
      margin-bottom: 24px;
      box-shadow: var(--shadow-xs);
    }
    .hero-title {
      font-size: 3.1rem;
      font-weight: 800;
      line-height: 1.18;
      color: var(--text-primary);
      margin-bottom: 20px;
      letter-spacing: -0.025em;
    }
    .hero-title span {
      background: linear-gradient(135deg, #4361EE 0%, #3B82F6 40%, #7209B7 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .hero-subtitle {
      font-size: 1.12rem;
      color: var(--text-secondary);
      line-height: 1.7;
      margin-bottom: 34px;
      max-width: 560px;
    }
    .hero-btn-group {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }
    .hero-card-preview {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(16px);
      border-radius: var(--radius-xl);
      padding: 30px;
      box-shadow: var(--shadow-xl);
      border: 1px solid rgba(226, 232, 240, 0.9);
      position: relative;
    }
    .features-section {
      padding: 90px 0;
      background: #FFFFFF;
      border-top: 1px solid var(--border-color);
      border-bottom: 1px solid var(--border-color);
    }
    .feature-card-modern {
      background: #F8FAFC;
      border-radius: var(--radius-xl);
      padding: 32px 26px;
      border: 1px solid var(--border-color);
      transition: var(--transition-normal);
    }
    .feature-card-modern:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-lg);
      border-color: #C7D2FE;
      background: #FFFFFF;
    }
    .feature-icon-box {
      width: 54px;
      height: 54px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      margin-bottom: 22px;
      box-shadow: var(--shadow-xs);
    }

    /* PDF Guide Section Styles */
    .guide-section {
      padding: 90px 0;
      background: #FAFBFF;
    }
    .guide-pdf-box {
      background: linear-gradient(135deg, #FFFFFF 0%, #EEF2FF 100%);
      border: 1.5px solid #C7D2FE;
      border-radius: var(--radius-xl);
      padding: 40px 48px;
      display: grid;
      grid-template-columns: 210px 1fr;
      gap: 40px;
      align-items: center;
      box-shadow: var(--shadow-lg);
      margin-bottom: 52px;
    }
    .guide-book-mockup {
      width: 100%;
      height: 270px;
      background: linear-gradient(145deg, #4361EE 0%, #3B82F6 60%, #7209B7 100%);
      border-radius: 12px 20px 20px 12px;
      box-shadow: -10px 14px 28px rgba(67, 97, 238, 0.28), inset 6px 0 12px rgba(255,255,255,0.25);
      padding: 26px 20px;
      color: #FFFFFF;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      border-left: 7px solid #1E293B;
      transform: rotate(-2.5deg);
      transition: var(--transition-normal);
    }
    .guide-book-mockup:hover {
      transform: rotate(0deg) translateY(-6px);
    }
    .guide-tab-nav {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-bottom: 32px;
      flex-wrap: wrap;
    }
    .guide-tab-btn {
      padding: 11px 24px;
      border-radius: var(--radius-full);
      border: 1.5px solid var(--border-color);
      background: #FFFFFF;
      font-size: 0.92rem;
      font-weight: 600;
      color: var(--text-secondary);
      cursor: pointer;
      transition: var(--transition-fast);
      display: flex;
      align-items: center;
      gap: 8px;
      box-shadow: var(--shadow-xs);
    }
    .guide-tab-btn:hover {
      border-color: var(--primary);
      color: var(--primary);
    }
    .guide-tab-btn.active {
      background: var(--primary);
      color: #FFFFFF;
      border-color: var(--primary);
      box-shadow: 0 4px 16px rgba(67, 97, 238, 0.35);
    }
    .guide-pane {
      display: none;
      animation: fadeIn 0.35s ease;
    }
    .guide-pane.active {
      display: block;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .guide-steps-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 22px;
    }
    .guide-step-card {
      background: #FFFFFF;
      border: 1.5px solid var(--border-color);
      border-radius: var(--radius-xl);
      padding: 26px;
      transition: var(--transition-normal);
      box-shadow: var(--shadow-xs);
    }
    .guide-step-card:hover {
      border-color: #C7D2FE;
      box-shadow: var(--shadow-md);
      transform: translateY(-4px);
    }
    .guide-step-badge {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: var(--primary);
      color: #FFFFFF;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      margin-bottom: 18px;
      box-shadow: 0 4px 12px var(--primary-glow);
    }

    /* Modal PDF Viewer */
    .pdf-modal {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(8px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .pdf-modal.active {
      display: flex;
    }
    .pdf-modal-card {
      background: #FFFFFF;
      width: 100%;
      max-width: 960px;
      height: 88vh;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      border: 1px solid var(--border-color);
    }
    .pdf-modal-header {
      padding: 18px 26px;
      background: #F8FAFC;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .landing-mobile-menu-btn {
      display: none;
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: #F1F5F9;
      border: 1px solid var(--border-color);
      color: var(--text-primary);
      font-size: 1.25rem;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }

    @media (max-width: 1023.98px) {
      .landing-mobile-menu-btn {
        display: inline-flex;
      }
      .landing-nav-inner {
        padding: 10px 16px;
      }
      .landing-nav-links {
        display: none;
        position: absolute;
        top: 74px;
        left: 0;
        right: 0;
        background: #FFFFFF;
        border-bottom: 1px solid var(--border-color);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.1);
        padding: 20px 24px;
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
      }
      .landing-nav-links.active {
        display: flex;
      }
      .hero-container { grid-template-columns: 1fr; text-align: center; }
      .hero-subtitle { margin: 0 auto 32px; }
      .hero-btn-group { justify-content: center; }
      .hero-title { font-size: clamp(1.8rem, 6vw, 2.4rem); }
      .guide-pdf-box { grid-template-columns: 1fr; text-align: center; padding: 28px; }
      .guide-book-mockup { max-width: 200px; margin: 0 auto; }
    }

    /* Desktop Guard (>= 1024px) */
    @media (min-width: 1024px) {
      .landing-mobile-menu-btn {
        display: none !important;
      }
      .landing-nav-links {
        display: flex !important;
      }
    }
  </style>
</head>
<body class="home-page">

  <!-- Sticky Navbar -->
  <header class="landing-nav">
    <div class="container landing-nav-inner">
      <a href="/" class="brand home-brand" style="text-decoration:none;display:flex;align-items:center;gap:12px;">
        <span class="logo-mark" aria-hidden="true" style="width:40px;height:40px;">
          <svg viewBox="0 0 200 170" role="img" style="width:100%;height:100%;">
            <path d="M36 60h18l10 56-19 4z" fill="#4361EE"/>
            <path d="M164 60h-18l-10 56 19 4z" fill="#4361EE"/>
            <path d="M44 55c23-4 44-3 61 12 17-15 38-16 61-12l-12 48c-18-4-34-3-49 9-15-12-31-13-49-9z" fill="#EEF1FF" stroke="#C7D2FE" stroke-width="7" stroke-linejoin="round"/>
            <path d="M100 95l34-27-22 37-12 16-12-16-22-37z" fill="#F59E0B"/>
            <path d="M84 32c10-8 22-12 36-12 15 0 28 5 39 14" fill="none" stroke="#4361EE" stroke-width="8" stroke-linecap="round"/>
            <path d="M94 45c7-5 15-8 25-8 10 0 19 3 26 9" fill="none" stroke="#4361EE" stroke-width="7" stroke-linecap="round"/>
            <circle cx="119" cy="58" r="6" fill="#4361EE"/>
          </svg>
        </span>
        <div class="sidebar-brand-text" style="display:flex;flex-direction:column;">
          <strong style="font-size:1.3rem;color:var(--text-primary);">Pantau<span style="color:var(--primary);">Baca</span></strong>
          <small style="font-size:0.75rem;color:var(--text-muted);font-weight:600;">Platform Literasi Digital</small>
        </div>
      </a>

      <nav class="landing-nav-links">
        <a href="#beranda">Beranda</a>
        <a href="#fitur">Fitur Unggulan</a>
        <a href="#panduan">📖 Buku Panduan PDF</a>
        <a href="#koleksi">Koleksi Buku</a>
        <a href="katalog.html">Katalog</a>
      </nav>

      <div style="display:flex;align-items:center;gap:10px;">
        <a href="login.html" class="btn btn-outline btn-sm">Masuk</a>
        <a href="register.html" class="btn btn-primary btn-sm">Daftar Akun</a>
        <button type="button" class="landing-mobile-menu-btn" onclick="toggleLandingNav()" aria-label="Menu navigasi">☰</button>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section id="beranda" class="hero-wrapper">
    <div class="container hero-container">
      <div>
        <div class="hero-badge-pill">
          <span>✨</span> Platform Monitoring Literasi & Evaluasi Membaca Siswa
        </div>
        <h1 class="hero-title">
          Tumbuhkan Minat Baca & Pantau Kemampuan Siswa dengan <span>PantauBaca</span>
        </h1>
        <p class="hero-subtitle">
          Akses ratusan koleksi buku digital, pantau tingkat kemampuan membaca (Rendah / Tinggi), pelajari resume materi berdiferensiasi, dan ikuti kuis pemahaman bacaan interaktif.
        </p>
        <div class="hero-btn-group">
          <a href="register.html" class="btn btn-primary" style="padding:14px 30px;font-size:1rem;">
            Mulai Belajar Sekarang →
          </a>
          <a href="#panduan" class="btn btn-outline" style="padding:14px 26px;font-size:1rem;">
            📖 Buku Panduan PDF
          </a>
        </div>
        <div style="display:flex;align-items:center;gap:36px;margin-top:40px;border-top:1.5px solid var(--border-color);padding-top:28px;">
          <div>
            <div style="font-size:1.7rem;font-weight:800;color:var(--primary);" id="heroBookCount">100+</div>
            <div style="font-size:0.82rem;color:var(--text-secondary);font-weight:600;">Koleksi Buku</div>
          </div>
          <div>
            <div style="font-size:1.7rem;font-weight:800;color:var(--accent-emerald);">2 Tingkat</div>
            <div style="font-size:0.82rem;color:var(--text-secondary);font-weight:600;">Evaluasi Baca</div>
          </div>
          <div>
            <div style="font-size:1.7rem;font-weight:800;color:var(--accent-amber);">100%</div>
            <div style="font-size:0.82rem;color:var(--text-secondary);font-weight:600;">Online & Interaktif</div>
          </div>
        </div>
      </div>

      <!-- Hero Visual Card -->
      <div class="hero-card-preview">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:44px;height:44px;border-radius:12px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;">
              S
            </div>
            <div>
              <div style="font-weight:700;font-size:1rem;">Ahmad Siswa</div>
              <div style="font-size:0.78rem;color:var(--text-muted);font-weight:500;">Siswa Aktif Kelas 8</div>
            </div>
          </div>
          <span class="level-badge level-tinggi">🌟 Tingkat Tinggi</span>
        </div>

        <div style="background:#F8FAFC;border-radius:var(--radius-lg);padding:18px;margin-bottom:16px;border:1px solid var(--border-color);">
          <div style="display:flex;justify-content:space-between;font-size:0.86rem;margin-bottom:8px;">
            <span style="font-weight:700;">Target Membaca Mingguan</span>
            <span style="color:var(--primary);font-weight:800;">8/10 Buku (80%)</span>
          </div>
          <div class="progress-wrap" style="height:10px;">
            <div class="progress-bar" style="width:80%;"></div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div style="padding:14px;border-radius:var(--radius-lg);background:#F8FAFC;border:1px solid var(--border-color);text-align:center;">
            <div style="font-size:1.35rem;font-weight:800;color:var(--primary);">320 menit</div>
            <div style="font-size:0.76rem;color:var(--text-secondary);font-weight:600;">Total Waktu Baca</div>
          </div>
          <div style="padding:14px;border-radius:var(--radius-lg);background:#F8FAFC;border:1px solid var(--border-color);text-align:center;">
            <div style="font-size:1.35rem;font-weight:800;color:var(--accent-emerald);">95 / 100</div>
            <div style="font-size:0.76rem;color:var(--text-secondary);font-weight:600;">Skor Kuis Rata-rata</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="fitur" class="features-section">
    <div class="container">
      <div style="text-align:center;max-width:680px;margin:0 auto 52px;">
        <span style="font-size:0.85rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">Fitur Utama</span>
        <h2 style="font-size:2.2rem;font-weight:800;color:var(--text-primary);margin-top:8px;">Ekosistem Literasi Digital Sekolah Lengkap</h2>
        <p style="font-size:0.96rem;color:var(--text-secondary);margin-top:8px;">
          Didesain dengan pendekatan berdiferensiasi agar setiap siswa belajar sesuai kecepatan dan kapasitasnya masing-masing.
        </p>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:24px;">
        
        <div class="feature-card-modern">
          <div class="feature-icon-box" style="background:#EEF2FF;color:#4361EE;">📊</div>
          <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:8px;">Evaluasi Kemampuan Baca</h3>
          <p style="font-size:0.9rem;color:var(--text-secondary);line-height:1.6;">
            Sistem otomatis mendeteksi tingkatan membaca siswa (<strong>Rendah / Tinggi</strong>) dan memperbarui levelnya berdasarkan konsistensi membaca dan skor kuis.
          </p>
        </div>

        <div class="feature-card-modern">
          <div class="feature-icon-box" style="background:#ECFDF5;color:#059669;">✍️</div>
          <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:8px;">Resume Berdiferensiasi</h3>
          <p style="font-size:0.9rem;color:var(--text-secondary);line-height:1.6;">
            Guru dapat menulis resume buku kategori <strong>Dasar</strong> dan <strong>Lanjutan</strong> yang otomatis disajikan sesuai tingkat kemampuan baca siswa.
          </p>
        </div>

        <div class="feature-card-modern">
          <div class="feature-icon-box" style="background:#F5F3FF;color:#7209B7;">📝</div>
          <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:8px;">Kuis Pemahaman Interaktif</h3>
          <p style="font-size:0.9rem;color:var(--text-secondary);line-height:1.6;">
            Kuis pilihan ganda dan esai terbuka otomatis saat siswa menyelesaikan bacaan 100% untuk menguji daya serap materi secara langsung.
          </p>
        </div>

        <div class="feature-card-modern">
          <div class="feature-icon-box" style="background:#FFFBEB;color:#D97706;">👨‍🏫</div>
          <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:8px;">Dashboard Guru & Monitoring</h3>
          <p style="font-size:0.9rem;color:var(--text-secondary);line-height:1.6;">
            Pantau grafik keaktifan membaca kelas, durasi baca siswa, nilai evaluasi pemahaman, serta perkembangan siswa secara transparan.
          </p>
        </div>

      </div>
    </div>
  </section>

  <!-- Buku Panduan Penggunaan Sistem Section -->
  <section id="panduan" class="guide-section">
    <div class="container">
      <div style="text-align:center;max-width:680px;margin:0 auto 40px;">
        <span style="font-size:0.85rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">📘 Dokumentasi Resmi</span>
        <h2 style="font-size:2.2rem;font-weight:800;color:var(--text-primary);margin-top:8px;">Buku Panduan Penggunaan Sistem</h2>
        <p style="font-size:0.96rem;color:var(--text-secondary);margin-top:8px;">
          Buku panduan lengkap dalam format PDF untuk membantu Siswa, Guru, dan Administrator memaksimalkan seluruh fitur PantauBaca.
        </p>
      </div>

      <!-- Featured PDF Guide Card -->
      <div class="guide-pdf-box">
        <!-- 3D Book Mockup -->
        <div class="guide-book-mockup">
          <div>
            <div style="display:inline-block;padding:4px 10px;background:rgba(255,255,255,0.25);border-radius:6px;font-size:0.72rem;font-weight:800;letter-spacing:0.06em;">
              PANDUAN SISTEM
            </div>
            <h3 style="font-size:1.2rem;font-weight:800;margin-top:12px;line-height:1.3;color:#FFFFFF;">
              Buku Panduan PantauBaca
            </h3>
            <p style="font-size:0.78rem;opacity:0.9;margin-top:4px;">Edisi Lengkap v1.0</p>
          </div>
          <div style="border-top:1px solid rgba(255,255,255,0.25);padding-top:10px;font-size:0.75rem;display:flex;justify-content:space-between;align-items:center;">
            <strong>PantauBaca</strong>
            <span>PDF File</span>
          </div>
        </div>

        <!-- Details & Actions -->
        <div>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap;">
            <span style="background:#DCFCE7;color:#15803D;font-weight:700;font-size:0.78rem;padding:4px 12px;border-radius:var(--radius-full);border:1px solid #86EFAC;">
              ✓ File PDF Tersedia
            </span>
            <span style="color:var(--text-muted);font-size:0.84rem;font-weight:500;">Format: Dokumen PDF Digital</span>
          </div>
          
          <h3 style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:10px;">
            Buku Panduan Pengoperasian Platform PantauBaca
          </h3>
          <p style="font-size:0.94rem;color:var(--text-secondary);line-height:1.65;margin-bottom:22px;">
            Memuat panduan bergambar lengkap mulai dari pendaftaran akun siswa/guru, navigasi e-reader, mekanisme evaluasi tingkat kemampuan baca, penulisan resume berdiferensiasi, kuis interaktif, hingga manajemen admin.
          </p>

          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <!-- Baca PDF Langsung -->
            <button type="button" class="btn btn-primary" onclick="openPdfModal('{{ asset('pantaubaca/docs/panduan-sistem.pdf') }}')" style="padding:13px 24px;">
              👁️ Baca Buku Panduan (PDF)
            </button>
            
            <!-- Download PDF -->
            <a href="{{ asset('pantaubaca/docs/panduan-sistem.pdf') }}" download="Buku_Panduan_PantauBaca.pdf" class="btn btn-outline" style="padding:13px 22px;">
              ⬇️ Unduh File PDF
            </a>

            <a href="{{ asset('pantaubaca/docs/panduan-sistem.pdf') }}" target="_blank" style="font-size:0.9rem;color:var(--primary);text-decoration:none;font-weight:700;margin-left:4px;">
              Buka di Tab Baru ↗
            </a>
          </div>
        </div>
      </div>

      <!-- Quick Step Tabs for Preview -->
      <div style="text-align:center;margin-bottom:24px;">
        <h4 style="font-size:1.2rem;font-weight:800;color:var(--text-primary);">Ringkasan Cepat Berdasarkan Peran</h4>
      </div>

      <!-- Tab Buttons -->
      <div class="guide-tab-nav">
        <button class="guide-tab-btn active" onclick="switchGuideTab('siswa', this)">
          <span>🎓</span> Ringkasan Siswa
        </button>
        <button class="guide-tab-btn" onclick="switchGuideTab('guru', this)">
          <span>👨‍🏫</span> Ringkasan Guru
        </button>
        <button class="guide-tab-btn" onclick="switchGuideTab('admin', this)">
          <span>🛡️</span> Ringkasan Admin
        </button>
      </div>

      <!-- Pane 1: Siswa -->
      <div id="guide-siswa" class="guide-pane active">
        <div class="guide-steps-grid">
          <div class="guide-step-card">
            <div class="guide-step-badge">1</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Daftar & Masuk Akun</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Pilih menu <strong>Daftar Akun</strong> dan pilih peran <em>Siswa</em>. Masukkan nama, email, dan kata sandi Anda untuk mulai menggunakan sistem.
            </p>
          </div>
          <div class="guide-step-card">
            <div class="guide-step-badge">2</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Pilih & Baca Buku</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Buka menu <strong>Katalog Buku</strong>, pilih buku yang disukai, lalu klik tombol <em>Baca Sekarang</em> untuk membaca melalui e-reader terintegrasi.
            </p>
          </div>
          <div class="guide-step-card">
            <div class="guide-step-badge">3</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Pantau Kemampuan Baca</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Tingkat kemampuan baca Anda (<strong>Rendah / Tinggi</strong>) akan dihitung otomatis seiring bertambahnya buku yang selesai dibaca dan skor kuis yang dikerjakan.
            </p>
          </div>
          <div class="guide-step-card">
            <div class="guide-step-badge">4</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Pelajari Resume & Kuis</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Baca resume materi berdiferensiasi dari guru dan selesaikan kuis pemahaman setelah membaca 100% untuk meningkatkan level kemampuan bacamu.
            </p>
          </div>
        </div>
      </div>

      <!-- Pane 2: Guru -->
      <div id="guide-guru" class="guide-pane">
        <div class="guide-steps-grid">
          <div class="guide-step-card">
            <div class="guide-step-badge" style="background:var(--accent-amber);">1</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Akses Dashboard Guru</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Masuk dengan akun Guru untuk mengakses panel pemantauan kelas, penulisan resume materi, dan manajemen soal kuis bacaan.
            </p>
          </div>
          <div class="guide-step-card">
            <div class="guide-step-badge" style="background:var(--accent-amber);">2</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Tulis Resume Berdiferensiasi</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Tulis rangkuman buku kategori <strong>Dasar</strong> (untuk siswa level rendah) dan <strong>Lanjutan</strong> (untuk siswa level tinggi) sesuai pedoman aspek.
            </p>
          </div>
          <div class="guide-step-card">
            <div class="guide-step-badge" style="background:var(--accent-amber);">3</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Buat Kuis Interaktif</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Susun pertanyaan pilihan ganda dan esai untuk menguji pemahaman siswa setelah membaca buku.
            </p>
          </div>
          <div class="guide-step-card">
            <div class="guide-step-badge" style="background:var(--accent-amber);">4</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Monitoring & Analitik</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Pantau progres membaca, durasi belajar harian, dan grafik tren literasi kelas secara terperinci.
            </p>
          </div>
        </div>
      </div>

      <!-- Pane 3: Admin -->
      <div id="guide-admin" class="guide-pane">
        <div class="guide-steps-grid">
          <div class="guide-step-card">
            <div class="guide-step-badge" style="background:var(--accent-purple);">1</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Akses Administrator</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Masuk menggunakan akun Administrator (<code>admin</code> / <code>admin123</code>) untuk membuka panel kontrol sistem utama.
            </p>
          </div>
          <div class="guide-step-card">
            <div class="guide-step-badge" style="background:var(--accent-purple);">2</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Upload Buku Digital</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Unggah file PDF buku baru, lengkapi informasi judul, penulis, kategori (Novel, Cerpen, Pembelajaran), dan URL cover buku.
            </p>
          </div>
          <div class="guide-step-card">
            <div class="guide-step-badge" style="background:var(--accent-purple);">3</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Manajemen Koleksi</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Kelola buku yang telah diunggah dengan opsi sunting data (*edit*) atau hapus buku (*delete*) yang tidak lagi relevan.
            </p>
          </div>
          <div class="guide-step-card">
            <div class="guide-step-badge" style="background:var(--accent-purple);">4</div>
            <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">Manajemen Pengguna</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;">
              Pantau seluruh akun terdaftar di sistem, kelola hak akses peran (Siswa, Guru, Admin), dan lakukan pemeliharaan akun.
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- PDF Reader Modal -->
  <div id="pdfModal" class="pdf-modal" onclick="if(event.target === this) closePdfModal()">
    <div class="pdf-modal-card">
      <div class="pdf-modal-header">
        <div style="display:flex;align-items:center;gap:12px;">
          <span style="font-size:1.4rem;">📘</span>
          <strong style="font-size:1.1rem;color:var(--text-primary);">Buku Panduan Penggunaan Sistem PantauBaca</strong>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
          <a id="modalDownloadBtn" href="{{ asset('pantaubaca/docs/panduan-sistem.pdf') }}" download="Buku_Panduan_PantauBaca.pdf" class="btn btn-outline btn-sm">⬇️ Download PDF</a>
          <button type="button" class="btn btn-outline btn-sm" onclick="closePdfModal()" style="font-size:1.1rem;padding:4px 12px;">✕</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="" style="width:100%;height:100%;border:none;background:#f1f5f9;"></iframe>
    </div>
  </div>

  <!-- Koleksi Buku Preview -->
  <section id="koleksi" style="padding:90px 0;background:#FFFFFF;border-top:1.5px solid var(--border-color);">
    <div class="container">
      <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:40px;flex-wrap:wrap;gap:16px;">
        <div>
          <span style="font-size:0.85rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">Koleksi Digital</span>
          <h2 style="font-size:2.2rem;font-weight:800;color:var(--text-primary);margin-top:6px;">Buku Populer & Rekomendasi</h2>
        </div>
        <a href="katalog.html" class="btn btn-outline btn-sm">Lihat Semua Koleksi →</a>
      </div>

      <div id="popularBooks" class="grid book-grid home-book-grid"></div>
    </div>
  </section>

  <!-- CTA Band -->
  <section style="background:linear-gradient(135deg, #4361EE 0%, #3B82F6 60%, #7209B7 100%);color:#FFFFFF;padding:72px 0;text-align:center;">
    <div class="container" style="max-width:720px;">
      <h2 style="font-size:2.4rem;font-weight:800;margin-bottom:14px;color:#FFFFFF;letter-spacing:-0.02em;">Siap Memulai Kebiasaan Membaca Baru?</h2>
      <p style="font-size:1.05rem;opacity:0.92;line-height:1.65;margin-bottom:32px;">
        Daftar sekarang dan nikmati kemudahan akses bahan bacaan digital berkualitas langsung di sekolah maupun di rumah.
      </p>
      <div style="display:flex;justify-content:center;gap:16px;flex-wrap:wrap;">
        <a href="register.html" class="btn" style="background:#FFFFFF;color:var(--primary);font-weight:800;padding:15px 32px;box-shadow:0 8px 24px rgba(0,0,0,0.15);">Daftar Akun Gratis</a>
        <a href="login.html" class="btn btn-outline" style="border-color:rgba(255,255,255,0.4);color:#FFFFFF;background:rgba(255,255,255,0.1);padding:15px 26px;">Masuk ke Akun</a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer style="background:#0F172A;color:#94A3B8;padding:52px 0 28px;border-top:1px solid #1E293B;">
    <div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;">
      <div style="display:flex;align-items:center;gap:12px;">
        <strong style="color:#FFFFFF;font-size:1.2rem;">Pantau<span style="color:#60A5FA;">Baca</span></strong>
        <span style="font-size:0.86rem;color:#64748B;">— Platform Monitoring Literasi Siswa</span>
      </div>
      <div style="font-size:0.88rem;color:#64748B;">
        &copy; 2026 PantauBaca. Seluruh hak cipta dilindungi.
      </div>
    </div>
  </footer>

  <script>
    function switchGuideTab(tabName, btn) {
      document.querySelectorAll('.guide-pane').forEach(p => p.classList.remove('active'));
      document.querySelectorAll('.guide-tab-btn').forEach(b => b.classList.remove('active'));
      const target = document.getElementById('guide-' + tabName);
      if (target) target.classList.add('active');
      if (btn) btn.classList.add('active');
    }

    function openPdfModal(url) {
      const modal = document.getElementById('pdfModal');
      const frame = document.getElementById('pdfFrame');
      frame.src = url;
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closePdfModal() {
      const modal = document.getElementById('pdfModal');
      const frame = document.getElementById('pdfFrame');
      frame.src = '';
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }

    function toggleLandingNav() {
      const navLinks = document.querySelector('.landing-nav-links');
      if (navLinks) navLinks.classList.toggle('active');
    }

    document.querySelectorAll('.landing-nav-links a').forEach(a => {
      a.addEventListener('click', () => {
        const navLinks = document.querySelector('.landing-nav-links');
        if (navLinks) navLinks.classList.remove('active');
      });
    });
  </script>
  <script src="{{ asset('pantaubaca/js/app.js') }}"></script>
  <script src="{{ asset('pantaubaca/js/index.js') }}"></script>
</body>
</html>
