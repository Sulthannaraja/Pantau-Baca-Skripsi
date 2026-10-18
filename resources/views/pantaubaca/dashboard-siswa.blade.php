<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Siswa — PantauBaca</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('pantaubaca/css/style.css') }}?v={{ file_exists(public_path('pantaubaca/css/style.css')) ? filemtime(public_path('pantaubaca/css/style.css')) : time() }}" />
  <style>
    body { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
    
    .welcome-banner {
      background: linear-gradient(135deg, #4361EE 0%, #3B82F6 50%, #7209B7 100%);
      border-radius: var(--radius-xl);
      padding: 32px 36px;
      color: #FFFFFF;
      margin-bottom: 28px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      overflow: hidden;
      box-shadow: 0 12px 30px -8px rgba(67, 97, 238, 0.35);
    }
    .welcome-banner::after {
      content: '';
      position: absolute;
      width: 220px;
      height: 220px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.08);
      right: -40px;
      bottom: -40px;
      pointer-events: none;
    }
    .resume-aspect-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 14px;
      margin: 18px 0;
    }
    .resume-aspect-card {
      background: #F8FAFC;
      border: 1.5px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 14px 16px;
      transition: var(--transition-fast);
    }
    .resume-aspect-card:hover {
      background: #FFFFFF;
      border-color: #C7D2FE;
      box-shadow: var(--shadow-sm);
    }
    .resume-aspect-card strong {
      font-size: 0.8rem;
      color: var(--primary);
      display: block;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 4px;
      font-weight: 700;
    }
    .resume-aspect-card span {
      font-size: 0.88rem;
      color: var(--text-primary);
      line-height: 1.45;
      display: block;
      font-weight: 500;
    }
    .study-sheet-card {
      background: #FFFFFF;
      border: 1.5px solid #E2E8F0;
      border-radius: 18px;
      box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.06);
      overflow: hidden;
      margin-top: 20px;
    }
    .study-sheet-header {
      background: linear-gradient(135deg, #F8FAFC 0%, #EEF2FF 100%);
      border-bottom: 1px solid #E2E8F0;
      padding: 14px 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }
    .study-sheet-meta {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
    }
    .study-tag {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 14px;
      border-radius: 999px;
      font-weight: 700;
      font-size: 0.8rem;
      background: #DBEAFE;
      color: #1D4ED8;
    }
    .study-read-time, .study-author {
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-secondary);
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .study-sheet-tools {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .font-resizer {
      display: flex;
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      overflow: hidden;
    }
    .btn-resizer {
      border: none;
      background: transparent;
      padding: 5px 11px;
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--text-secondary);
      cursor: pointer;
      transition: background 0.15s;
    }
    .btn-resizer:hover {
      background: #F1F5F9;
      color: var(--primary);
    }
    .btn-copy-resume {
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 5px 14px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-secondary);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s;
    }
    .btn-copy-resume:hover {
      background: #F8FAFC;
      border-color: #C7D2FE;
      color: var(--primary);
    }
    .study-sheet-body {
      padding: 30px 34px;
      max-width: 860px;
      margin: 0 auto;
      font-size: 1.02rem;
      line-height: 1.85;
      color: #1E293B;
      transition: font-size 0.2s ease;
    }
    .resume-main-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: #0F172A;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid #EEF2FF;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .resume-main-title::before {
      content: "";
      display: inline-block;
      width: 5px;
      height: 22px;
      background: linear-gradient(180deg, var(--primary), #8B5CF6);
      border-radius: 4px;
    }
    .resume-paragraph {
      margin-bottom: 18px;
      color: #334155;
      line-height: 1.88;
    }
    .resume-section-card {
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      border-radius: 12px;
      padding: 18px 22px;
      margin: 18px 0;
    }
    .resume-section-card:hover {
      background: #FFFFFF;
      border-color: #C7D2FE;
      box-shadow: 0 4px 14px rgba(67, 97, 238, 0.05);
    }
    .resume-section-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .resume-conclusion-card {
      background: linear-gradient(135deg, #EFF6FF 0%, #EEF2FF 100%);
      border: 1.5px solid #BFDBFE;
      border-radius: 14px;
      padding: 20px 24px;
      margin-top: 26px;
    }
    .resume-conclusion-title {
      font-size: 0.98rem;
      font-weight: 800;
      color: #1D4ED8;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Likert Survey Question Card - Sederhana, Ringkas & Nyaman Dibaca */
    .likert-card {
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 16px 20px;
      margin-bottom: 14px;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .likert-card:hover {
      border-color: #CBD5E1;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    .likert-question-title {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-weight: 600;
      font-size: 0.94rem;
      line-height: 1.5;
      color: var(--text-primary);
    }
    .likert-question-index {
      font-weight: 700;
      color: var(--primary);
      flex-shrink: 0;
    }
    .likert-options-simple {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 12px 28px;
      margin-top: 12px;
      padding-left: 22px;
    }
    .likert-simple-option {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      user-select: none;
      padding: 4px 6px;
      border-radius: var(--radius-sm);
      transition: all 0.15s ease;
    }
    .likert-simple-option input[type="radio"] {
      width: 16px;
      height: 16px;
      cursor: pointer;
      accent-color: var(--primary);
      margin: 0;
      flex-shrink: 0;
    }
    .likert-option-text {
      font-size: 0.88rem;
      font-weight: 500;
      color: var(--text-secondary);
      transition: color 0.15s ease, font-weight 0.15s ease;
    }
    .likert-simple-option:hover .likert-option-text {
      color: var(--primary);
    }
    .likert-simple-option.selected .likert-option-text,
    .likert-simple-option:has(input:checked) .likert-option-text {
      color: var(--primary);
      font-weight: 700;
    }

    /* Survey Modal */
    .survey-modal {
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
    .survey-modal.active {
      display: flex;
    }
    .survey-modal-card {
      background: #FFFFFF;
      width: 100%;
      max-width: 860px;
      max-height: 90vh;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      border: 1px solid var(--border-color);
    }
    .survey-modal-header {
      padding: 20px 28px;
      background: #F8FAFC;
      border-bottom: 1.5px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .survey-modal-body {
      padding: 24px 28px;
      overflow-y: auto;
      flex: 1;
    }

    @media (max-width: 640px) {
      .likert-card {
        padding: 14px 16px;
      }
      .likert-options-simple {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding-left: 6px;
      }
      .likert-simple-option {
        width: 100%;
        padding: 6px 0;
      }
    }
  </style>
</head>
<body class="dashboard-page">

  <!-- Sidebar Toggle (Mobile) -->
  <button class="sidebar-toggle" onclick="toggleMobileSidebar()" aria-label="Buka menu navigasi">☰</button>
  <div class="sidebar-overlay" onclick="closeMobileSidebar()"></div>

  <!-- Sidebar Navigation -->
  @include('pantaubaca.partials.sidebar', ['role' => 'siswa', 'active' => 'overviewSection'])

  <!-- Main -->
  <main class="dashboard-main">
    <!-- Header -->
    <header class="dashboard-header">
      <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
        <button type="button" class="dashboard-nav-toggle" aria-label="Buka menu navigasi" onclick="toggleMobileSidebar()">☰</button>
        <div class="dashboard-greeting">
          <h1>Dashboard Siswa 🎓</h1>
          <p>Pantau kemajuan literasi, capai target mingguan, dan asah pemahamanmu.</p>
        </div>
      </div>
      <div class="dashboard-header-actions">
        <button type="button" class="btn btn-outline btn-sm" onclick="openSurveyModal()">
          📝 Angket Literasi
        </button>
        <a href="katalog.html">Katalog Buku</a>
        <a href="#" onclick="logout()">Logout</a>
        <div class="header-profile">
          <div class="header-profile-avatar">S</div>
          <div class="header-profile-info">
            <span class="profile-name" id="profileName">Siswa</span>
            <span class="profile-role">Siswa</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Content -->
    <div class="dashboard-content">

      <!-- FEATURE: Dashboard Overview -->
      <div class="feature-section active" id="overviewSection">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
          <div>
            <span style="background:rgba(255,255,255,0.2);padding:4px 12px;border-radius:var(--radius-full);font-size:0.8rem;font-weight:700;">
              🔥 Evaluasi Kemampuan Literasi Digital
            </span>
            <h2 style="font-size:1.6rem;font-weight:800;color:#FFFFFF;margin-top:10px;">
              Ketahui & Tingkatkan Level Literasimu!
            </h2>
            <p style="font-size:0.92rem;opacity:0.92;margin-top:4px;max-width:560px;">
              Isi angket 10 butir standar literasi digital untuk mengevaluasi tingkat kemampuan membaca Anda dan membuka resume materi yang sesuai.
            </p>
          </div>
          <button type="button" class="btn" onclick="openSurveyModal()" style="background:#FFFFFF;color:var(--primary);font-weight:800;padding:12px 22px;border-radius:var(--radius-md);box-shadow:0 4px 14px rgba(0,0,0,0.15);">
            📝 Isi Angket Literasi →
          </button>
        </div>

        <!-- Stat Cards -->
        <div class="stat-grid">
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('riwayatSection', event)" title="Klik untuk lihat riwayat membaca">
            <div class="stat-icon blue">⏱️</div>
            <div class="stat-info">
              <h4>Total Waktu Baca</h4>
              <div class="stat-value" id="totalDuration">0 menit</div>
              <div class="stat-desc">Waktu aktif membaca buku (Klik lihat riwayat)</div>
            </div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('riwayatSection', event)" title="Klik untuk lihat buku dibaca">
            <div class="stat-icon green">📗</div>
            <div class="stat-info">
              <h4>Total Buku Dibaca</h4>
              <div class="stat-value" id="totalBooks">0 buku</div>
              <div class="stat-desc">Koleksi buku yang dibuka</div>
            </div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('progressSection', event)" title="Klik untuk lihat target literasi">
            <div class="stat-icon purple">🎯</div>
            <div class="stat-info">
              <h4>Target Literasi</h4>
              <div class="stat-value" id="targetInfo">0/10</div>
              <div class="stat-desc">Target buku minggu ini</div>
            </div>
          </div>
          <div class="stat-card" id="levelCard" onclick="openSurveyModal()" title="Klik untuk evaluasi ulang angket"
            style="cursor:pointer;border:2px solid #FCD34D;background:linear-gradient(135deg,#FFFBEB,#FEF3C7);">
            <div class="stat-icon" id="levelIcon" style="background:#FDE68A;color:#D97706;font-size:1.5rem;">📊</div>
            <div class="stat-info">
              <h4 style="font-size:0.8rem;text-transform:uppercase;letter-spacing:1px;color:#92400E;">Tingkat Kemampuan Literasi</h4>
              <div style="margin-top:8px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span class="level-badge level-rendah" id="levelBadge" style="font-size:1rem;padding:6px 16px;">Rendah</span>
                <span id="surveyScorePill" style="font-size:0.8rem;font-weight:700;color:#92400E;background:#FDE68A;padding:3px 10px;border-radius:20px;">Skor: -/40</span>
              </div>
              <div class="stat-desc" id="levelDesc" style="margin-top:6px;font-weight:600;color:#92400E;">📝 Klik untuk isi angket literasi!</div>
            </div>
          </div>
        </div>

        <!-- Quick Access Feature Navigation -->
        <div style="margin-top:32px;">
          <h3 style="font-size:1.15rem;font-weight:800;color:var(--text-primary);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <span>🚀</span> Akses Cepat Fitur Siswa
          </h3>
          <div class="feature-nav-grid">
            <div class="feature-nav-card" onclick="switchFeature('resumeSection', event)">
              <div>
                <div class="feat-icon" style="background:#EEF2FF;color:var(--primary);">📖</div>
                <h4 style="margin-top:12px;">Resume & Nilai Saya</h4>
                <p>Lihat resume buku yang kamu buat di katalog serta nilai yang diberikan oleh gurumu.</p>
              </div>
              <span class="feat-link">Buka Resume & Nilai →</span>
            </div>

            <div class="feature-nav-card" onclick="switchFeature('riwayatSection', event)">
              <div>
                <div class="feat-icon" style="background:#ECFDF5;color:#059669;">📋</div>
                <h4 style="margin-top:12px;">Riwayat Membaca</h4>
                <p>Pantau catatan durasi baca dan halaman terakhir yang Anda selesaikan.</p>
              </div>
              <span class="feat-link">Lihat Riwayat →</span>
            </div>

            <div class="feature-nav-card" onclick="switchFeature('progressSection', event)">
              <div>
                <div class="feat-icon" style="background:#FFFBEB;color:#D97706;">🎯</div>
                <h4 style="margin-top:12px;">Target Literasi</h4>
                <p>Cek persentase pencapaian target buku mingguan dan bulanan.</p>
              </div>
              <span class="feat-link">Cek Target →</span>
            </div>

            <div class="feature-nav-card" onclick="switchFeature('quizSection', event)">
              <div>
                <div class="feat-icon" style="background:#F5F3FF;color:#7209B7;">✍️</div>
                <h4 style="margin-top:12px;">Kuis Pemahaman</h4>
                <p>Uji daya serap bacaanmu melalui kuis interaktif setelah tamat membaca.</p>
              </div>
              <span class="feat-link">Mulai Kuis →</span>
            </div>
          </div>
        </div>
      </div>

      <!-- FEATURE: Kelas Saya -->
      <div class="feature-section" id="kelasSection">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">🏫</span> Kelas Saya</h2>
              <p>Gabung ke kelas guru Anda menggunakan kode kelas, atau lihat tugas yang diberikan.</p>
            </div>
            <button class="btn btn-primary" onclick="promptJoinClass()">+ Gabung Kelas</button>
          </div>
          <div id="studentClassList" style="margin-top: 16px;">
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Anda belum bergabung ke kelas manapun.</p>
          </div>
        </div>
      </div>

      <!-- FEATURE: Resume & Nilai Saya -->
      <div class="feature-section" id="resumeSection">
        <div class="panel">
          <div class="panel-header" style="flex-wrap:wrap;gap:12px;">
            <div>
              <h2><span class="panel-icon">📖</span> Resume & Nilai Saya</h2>
              <p>Daftar resume buku yang telah kamu buat di katalog beserta nilai dan catatan bimbingan dari gurumu.</p>
            </div>
            <a href="katalog.html" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
              📚 + Tulis Resume Buku di Katalog
            </a>
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="width:50px;">No</th>
                  <th>Judul Buku</th>
                  <th>Waktu Kirim</th>
                  <th>Ringkasan Resume</th>
                  <th>Nilai Guru</th>
                  <th>Catatan Guru</th>
                  <th style="width:130px;">Aksi</th>
                </tr>
              </thead>
              <tbody id="studentResumesListBody">
                <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted);">Memuat resume dan nilai Anda...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- FEATURE: Latihan Membaca (Sesuai Tingkat Literasi Digital Siswa) -->
      <div class="feature-section" id="latihanSection">
        <div class="panel" style="margin-bottom:24px;">
          <div class="panel-header" style="flex-direction:column;align-items:flex-start;gap:12px;">
            <div style="display:flex;justify-content:space-between;align-items:center;width:100%;flex-wrap:wrap;gap:12px;">
              <div>
                <h2><span class="panel-icon">📖</span> Latihan Membaca Digital</h2>
                <p>Koleksi buku kurasi yang disesuaikan secara otomatis berdasarkan tingkat kemampuan literasi digital Anda.</p>
              </div>
              <div style="display:flex;align-items:center;gap:10px;">
                <span id="latihanLevelBadge" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:var(--radius-full);font-size:0.85rem;font-weight:700;background:#EEF2FF;color:var(--primary);border:1px solid #C7D2FE;">
                  🔍 Memeriksa Level...
                </span>
                <button type="button" class="btn btn-outline btn-sm" onclick="openSurveyModal()">
                  📝 Perbarui Hasil Angket
                </button>
              </div>
            </div>

            <!-- Personalized Description Banner -->
            <div id="latihanLevelBanner" style="width:100%;padding:16px 20px;border-radius:var(--radius-lg);background:#F8FAFC;border:1.5px solid #E2E8F0;display:flex;align-items:flex-start;gap:14px;">
              <span id="latihanLevelIcon" style="font-size:1.8rem;line-height:1;">💡</span>
              <div style="flex:1;">
                <h4 id="latihanLevelTitle" style="font-size:0.98rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">
                  Rekomendasi Bahan Bacaan Sesuai Kemampuan Anda
                </h4>
                <p id="latihanLevelDesc" style="font-size:0.88rem;color:var(--text-secondary);line-height:1.5;">
                  Sistem sedang memuat rekomendasi buku digital Anda...
                </p>
              </div>
            </div>
          </div>

          <!-- Book Grid for Latihan Membaca -->
          <div id="latihanBooksGrid" class="book-grid" style="margin-top:20px;">
            <div style="grid-column:1/-1;text-align:center;padding:40px 16px;color:var(--text-muted);">
              <div style="font-size:2rem;margin-bottom:8px;">⏳</div>
              <p>Memuat buku latihan membaca yang sesuai...</p>
            </div>
          </div>
        </div>
      </div>

      <!-- FEATURE: Riwayat Membaca -->
      <div class="feature-section" id="riwayatSection">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">📋</span> Riwayat Membaca Terakhir</h2>
              <p>Catatan durasi dan capaian halaman buku yang sedang atau telah Anda baca.</p>
            </div>
          </div>
          <div class="table-responsive">
            <table>
              <thead><tr><th>Buku</th><th>Durasi</th><th>Progress</th><th>Halaman Terakhir</th><th>Terakhir Dibaca</th><th>Aksi</th></tr></thead>
              <tbody id="historyBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- FEATURE: Target Literasi Mingguan -->
      <div class="feature-section" id="progressSection">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">📈</span> Target Literasi Mingguan</h2>
              <p>Pantau ketercapaian target buku dan konsistensi belajarmu.</p>
            </div>
            <select id="progressPeriod" class="input" style="width:auto;min-width:140px;">
              <option value="week">Minggu Ini</option>
              <option value="month">Bulan Ini</option>
            </select>
          </div>
          <div style="display:flex;align-items:center;gap:28px;flex-wrap:wrap;padding:12px 0;">
            <div class="progress-ring-wrap">
              <div class="progress-ring">
                <svg width="120" height="120" viewBox="0 0 120 120">
                  <circle class="ring-bg" cx="60" cy="60" r="50"/>
                  <circle class="ring-fill" id="progressRing" cx="60" cy="60" r="50"
                    stroke-dasharray="314.16" stroke-dashoffset="314.16"/>
                </svg>
              </div>
              <div class="ring-text">
                <span id="progressPercent">0%</span>
                <small>Target Capai</small>
              </div>
            </div>
            <div>
              <p style="font-weight:700;font-size:1.05rem;margin-bottom:4px;">Pencapaian Target</p>
              <p id="weeklyTargetText" style="font-size:0.92rem;color:var(--text-secondary);">
                <strong>0 dari 10 buku</strong><br>
                Masih 10 buku lagi untuk mencapai targetmu!
              </p>
              <div style="margin-top:14px;padding:12px 16px;border-radius:var(--radius-md);background:var(--accent-amber-bg);border:1px solid var(--accent-amber-border);display:flex;align-items:center;gap:10px;">
                <span style="font-size:1.2rem;">⭐</span>
                <span style="font-size:0.86rem;color:#92400E;font-weight:500;">Semangat! Setiap halaman membawamu lebih dekat pada versi terbaik dirimu.</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FEATURE: Kuis Pemahaman -->
      <div class="feature-section" id="quizSection">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">📝</span> Kuis Pemahaman Tersedia</h2>
              <p>Kuis otomatis aktif setelah progres membaca buku mencapai 100%.</p>
            </div>
          </div>
          <div id="availableQuizList" class="quiz-list"></div>
          <div id="quizAttemptPanel" class="quiz-attempt hidden"></div>
        </div>
      </div>

    </div>
  </main>

  <!-- MODAL: Angket Literasi Digital 10 Soal (Likert 1-4) -->
  <div id="surveyModal" class="survey-modal" onclick="if(event.target === this) closeSurveyModal()">
    <div class="survey-modal-card">
      <div class="survey-modal-header">
        <div>
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:1.4rem;">📝</span>
            <h3 style="font-size:1.2rem;font-weight:800;color:var(--text-primary);">Angket Standar Literasi Digital</h3>
          </div>
          <p style="font-size:0.84rem;color:var(--text-secondary);margin-top:2px;">
            Instrumen 10 butir pertanyaan skala Likert (1–4) untuk menentukan tingkat kemampuan baca Anda.
          </p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeSurveyModal()" style="font-size:1.1rem;padding:4px 12px;">✕</button>
      </div>

      <div class="survey-modal-body">
        <form id="literacySurveyForm" onsubmit="handleSurveySubmit(event)">
          <!-- 10 Questions Container populated via JS -->
          <div id="surveyQuestionsContainer"></div>

          <div style="margin-top:24px;border-top:1.5px solid var(--border-color);padding-top:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
            <div style="font-size:0.86rem;color:var(--text-secondary);">
              Pastikan semua 10 pernyataan telah dijawab.
            </div>
            <div style="display:flex;gap:12px;">
              <button type="button" class="btn btn-outline" onclick="closeSurveyModal()">Batal</button>
              <button type="submit" class="btn btn-primary" id="submitSurveyBtn">
                🚀 Kirim & Evaluasi Tingkat Kemampuan
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Selesaikan Kuis Siswa -->
  <div id="quizSubmitConfirmModal" class="quiz-custom-modal" onclick="if(event.target === this) closeQuizConfirmModal()">
    <div class="quiz-custom-modal-card" style="max-width:520px;">
      <div class="quiz-custom-modal-header" style="background:linear-gradient(135deg, #EEF2FF 0%, #FAF5FF 100%);">
        <div>
          <span style="font-size:0.75rem;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:1px;">Konfirmasi Selesaikan Kuis</span>
          <h3 style="margin:2px 0 0 0;font-size:1.15rem;font-weight:800;color:var(--text-primary);">Kirimkan Jawaban Anda?</h3>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeQuizConfirmModal()" style="border-radius:50%;width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;">✕</button>
      </div>

      <div class="quiz-custom-modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
          <div style="background:#F8FAFC;padding:12px;border-radius:12px;border:1px solid #E2E8F0;text-align:center;">
            <div style="font-size:0.78rem;font-weight:700;color:#64748B;">TERJAWAB</div>
            <div id="confirmAnsweredCount" style="font-size:1.4rem;font-weight:800;color:#10B981;">0</div>
          </div>
          <div style="background:#F8FAFC;padding:12px;border-radius:12px;border:1px solid #E2E8F0;text-align:center;">
            <div style="font-size:0.78rem;font-weight:700;color:#64748B;">BELUM DIJAWAB</div>
            <div id="confirmUnansweredCount" style="font-size:1.4rem;font-weight:800;color:#EF4444;">0</div>
          </div>
        </div>

        <div id="confirmWarningBox" style="display:none;background:#FEF2F2;border:1.5px solid #FCA5A5;border-radius:12px;padding:14px;color:#991B1B;font-size:0.9rem;margin-bottom:16px;">
          ⚠️ <strong>Perhatian:</strong> Masih terdapat <span id="confirmWarningUnansweredText">0</span> soal yang belum kamu jawab. Soal yang belum dijawab akan bernilai 0 poin.
        </div>

        <div id="confirmReadyBox" style="display:none;background:#ECFDF5;border:1.5px solid #A7F3D0;border-radius:12px;padding:14px;color:#065F46;font-size:0.9rem;margin-bottom:16px;">
          🎉 <strong>Hebat!</strong> Semua soal telah kamu jawab. Jawabanmu akan langsung dinilai oleh server.
        </div>
      </div>

      <div class="quiz-custom-modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeQuizConfirmModal()">Periksa Lagi</button>
        <button type="button" class="btn btn-primary" id="confirmSubmitQuizBtn" onclick="executeQuizSubmit()">🚀 Ya, Kirim Sekarang</button>
      </div>
    </div>
  </div>

  <script src="{{ asset('pantaubaca/js/app.js') }}?v={{ file_exists(public_path('pantaubaca/js/app.js')) ? filemtime(public_path('pantaubaca/js/app.js')) : time() }}"></script>
  <script src="{{ asset('pantaubaca/js/dashboard-siswa.js') }}?v={{ file_exists(public_path('pantaubaca/js/dashboard-siswa.js')) ? filemtime(public_path('pantaubaca/js/dashboard-siswa.js')) : time() }}"></script>
  <script>
    async function promptJoinClass() {
      let code = await window.appPrompt("Masukkan kode kelas dari guru", "Contoh: X7Y8Z9");
      if (code) {
        try {
          const res = await apiPost('/classrooms/join', { code });
          showToast(res.message || "Berhasil bergabung ke kelas", "success");
          loadMyClasses();
        } catch(e) {
          showToast(e.message, "error");
        }
      }
    }

    async function loadMyClasses() {
      try {
        const classes = await apiGet('/classrooms');
        const container = document.getElementById('studentClassList');
        if (!classes || classes.length === 0) {
          container.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem;">Anda belum bergabung ke kelas manapun.</p>';
          return;
        }
        container.innerHTML = classes.map(c => {
          const teacherName = c.teacher ? c.teacher.name : 'Guru';
          const memberCount = c.users_count || (c.users ? c.users.length : 1);
          return `
            <div style="padding:18px; border:1.5px solid #e2e8f0; border-radius:14px; margin-bottom:12px; background:#F8FAFC; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
              <div>
                <h3 style="font-size:1.15rem;font-weight:800;margin:0 0 4px 0;color:var(--primary);">${escapeHtml(c.name)}</h3>
                <p style="margin:0; font-size:0.86rem; color:#64748b;">
                  🧑‍🏫 Pengajar: <strong>${escapeHtml(teacherName)}</strong> • 👥 ${memberCount} Siswa
                </p>
              </div>
              <div>
                <span style="display:inline-block;padding:6px 14px;border-radius:20px;font-size:0.8rem;font-weight:700;background:#DCFCE7;color:#15803D;">
                  ✅ Terdaftar
                </span>
              </div>
            </div>
          `;
        }).join("");
      } catch(e) {
        console.error(e);
      }
    }

    window.addEventListener('DOMContentLoaded', loadMyClasses);
  </script>
</body>
</html>
