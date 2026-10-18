<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Katalog Buku & Resume — PantauBaca</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('pantaubaca/css/style.css') }}?v={{ file_exists(public_path('pantaubaca/css/style.css')) ? filemtime(public_path('pantaubaca/css/style.css')) : time() }}" />
  <style>
    body { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; background:#F8FAFC; }
    .katalog-header-nav {
      background: #FFFFFF;
      border-bottom: 1.5px solid var(--border-color);
      box-shadow: var(--shadow-xs);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .katalog-header-inner {
      height: 74px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .book-card {
      background: #FFFFFF;
      border: 1.5px solid var(--border-color);
      border-radius: var(--radius-xl);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    }
    .book-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 16px 32px rgba(30, 41, 59, 0.12);
      border-color: #A5B4FC;
    }
    .book-cover-wrap {
      position: relative;
      width: 100%;
      aspect-ratio: 3 / 4;
      overflow: hidden;
      background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      border-top-left-radius: var(--radius-xl);
      border-top-right-radius: var(--radius-xl);
    }
    .book-cover-wrap::after {
      content: '';
      position: absolute;
      top: 0; left: 0; bottom: 0; width: 12px;
      background: linear-gradient(to right, rgba(0,0,0,0.2) 0%, rgba(255,255,255,0.12) 50%, rgba(0,0,0,0) 100%);
      pointer-events: none;
      z-index: 2;
    }
    .book-cover {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.35s ease;
    }
    .book-card:hover .book-cover {
      transform: scale(1.035);
    }
    .book-category-tag {
      position: absolute;
      top: 12px;
      left: 12px;
      background: rgba(15, 23, 42, 0.78);
      backdrop-filter: blur(4px);
      color: #FFFFFF;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 999px;
    }
    .resume-status-badge {
      position: absolute;
      bottom: 12px;
      left: 12px;
      right: 12px;
      font-size: 0.78rem;
      font-weight: 700;
      padding: 5px 10px;
      border-radius: 8px;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      backdrop-filter: blur(6px);
    }
    .badge-graded {
      background: rgba(22, 101, 52, 0.92);
      color: #FFFFFF;
    }
    .badge-pending {
      background: rgba(180, 83, 9, 0.92);
      color: #FFFFFF;
    }
    .book-meta {
      padding: 18px 20px 20px;
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    .book-title {
      font-size: 1.05rem;
      font-weight: 800;
      color: var(--text-primary);
      margin-bottom: 6px;
      line-height: 1.35;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      min-height: 2.7em;
    }
    .book-author {
      font-size: 0.85rem;
      color: var(--text-secondary);
      margin-bottom: 16px;
    }
    .book-actions {
      margin-top: auto;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .book-actions .btn {
      flex: 1;
      text-align: center;
      justify-content: center;
      font-size: 0.86rem;
      padding: 8px 12px;
    }

    @media (max-width: 768px) {
      .katalog-header-inner {
        height: auto;
        padding: 12px 0;
        flex-wrap: wrap;
        gap: 10px;
      }
      .katalog-filter-bar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
      }
      .katalog-filter-bar input,
      .katalog-filter-bar select {
        width: 100% !important;
        min-width: 0 !important;
      }
      .katalog-title-h1 {
        font-size: 1.45rem !important;
      }
    }

    /* Modal Resume */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(6px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-overlay.active {
      display: flex;
    }
    .modal-card {
      background: #FFFFFF;
      width: 100%;
      max-width: 680px;
      max-height: 90vh;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      animation: modalFadeIn 0.2s ease-out;
    }
    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.96); }
      to { opacity: 1; transform: scale(1); }
    }
    .modal-header {
      padding: 20px 24px;
      background: #F8FAFC;
      border-bottom: 1.5px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .modal-body {
      padding: 24px;
      overflow-y: auto;
      flex: 1;
    }
    .modal-footer {
      padding: 16px 24px;
      background: #F8FAFC;
      border-top: 1.5px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 12px;
    }
    .grade-feedback-box {
      background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
      border: 1.5px solid #6EE7B7;
      border-radius: 12px;
      padding: 16px 18px;
      margin-bottom: 20px;
    }
    .pending-status-box {
      background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
      border: 1.5px solid #FCD34D;
      border-radius: 12px;
      padding: 14px 18px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <!-- Header Nav -->
  <nav class="katalog-header-nav">
    <div class="container katalog-header-inner">
      <a class="brand" href="/" style="text-decoration:none;display:flex;align-items:center;gap:12px;">
        <span class="logo-mark" aria-hidden="true" style="width:38px;height:38px;display:inline-block;">
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
        <strong style="color:var(--text-primary);font-size:1.25rem;">Pantau<span style="color:var(--primary);">Baca</span></strong>
      </a>
      <div style="display:flex;align-items:center;gap:16px;">
        <a href="/" style="font-size:0.92rem;font-weight:600;color:var(--text-secondary);">Beranda</a>
        <a href="katalog.html" style="font-size:0.92rem;font-weight:700;color:var(--primary);">Katalog</a>
        <span id="userDashboardLinks" style="display:inline-flex;align-items:center;gap:12px;"></span>
        <a href="#" onclick="logout()" style="font-size:0.88rem;color:var(--text-muted);font-weight:600;">Logout</a>
      </div>
    </div>
  </nav>

  <main class="container" style="padding:40px 0 64px;">
    <!-- Banner Header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
      <div>
        <span style="font-size:0.85rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">Koleksi Digital & Resume</span>
        <h1 class="katalog-title-h1" style="font-size:2.2rem;font-weight:800;color:var(--text-primary);margin-top:4px;">📚 Katalog Buku & Resume Siswa</h1>
        <p style="color:var(--text-secondary);font-size:1rem;margin-top:6px;max-width:680px;">
          Pilih buku untuk dibaca langsung, lalu tulis <strong>resume bacaanmu</strong> di sini. Guru kelas akan meninjau dan memberikan nilai untuk resumemu!
        </p>
      </div>
      <div id="roleIndicatorBadge"></div>
    </div>

    <!-- Personalized Literacy Filter Banner (For Students) -->
    <div id="studentLiteracyBanner" style="display:none;background:linear-gradient(135deg,#EFF6FF 0%,#EEF2FF 100%);border:1.5px solid #BFDBFE;border-radius:var(--radius-xl);padding:18px 24px;margin-bottom:28px;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
      <div style="display:flex;align-items:center;gap:14px;">
        <span id="studentLiteracyIcon" style="font-size:2rem;line-height:1;">🎯</span>
        <div>
          <h4 id="studentLiteracyTitle" style="font-size:1.05rem;font-weight:800;color:var(--text-primary);margin-bottom:2px;">
            Katalog Disesuaikan Berdasarkan Hasil Asesmen Literasi
          </h4>
          <p id="studentLiteracyDesc" style="font-size:0.88rem;color:var(--text-secondary);margin:0;">
            Menampilkan buku yang paling cocok dengan kemampuan membaca Anda.
          </p>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <span id="studentLiteracyPill" style="padding:6px 14px;border-radius:20px;font-size:0.84rem;font-weight:800;background:#DBEAFE;color:#1D4ED8;">
          Memeriksa Asesmen...
        </span>
        <button id="toggleAllBooksBtn" type="button" class="btn btn-outline btn-sm" onclick="toggleStudentLiteracyFilter()" style="font-size:0.84rem;">
          Lihat Semua Buku
        </button>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="katalog-filter-bar" style="display:flex;gap:16px;margin-bottom:36px;flex-wrap:wrap;">
      <input id="search" class="input" placeholder="🔍 Cari judul buku atau penulis..." style="flex:1;min-width:240px;" />
      <select id="categoryFilter" class="input" style="width:auto;min-width:180px;">
        <option value="">Semua Kategori / Genre</option>
      </select>
      <select id="literacyFilter" class="input" style="width:auto;min-width:220px;">
        <option value="">Semua Tingkat Literasi</option>
        <option value="RENDAH">🌱 Buku Kemampuan Rendah</option>
        <option value="TINGGI">🌟 Buku Kemampuan Lanjutan</option>
      </select>
    </div>

    <!-- Book Grid -->
    <div id="bookGrid" class="grid book-grid"></div>
  </main>

  <!-- Modal Resume Siswa -->
  <div id="resumeModal" class="modal-overlay">
    <div class="modal-card">
      <div class="modal-header">
        <div>
          <span style="font-size:0.78rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;">Formulir Resume Siswa</span>
          <h3 style="font-size:1.25rem;font-weight:800;color:var(--text-primary);margin-top:2px;" id="modalBookTitle">Resume Buku</h3>
          <p style="font-size:0.84rem;color:var(--text-secondary);margin:0;" id="modalBookAuthor">-</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm icon-btn" onclick="closeResumeModal()" style="border-radius:50%;width:34px;height:34px;">✕</button>
      </div>

      <div class="modal-body">
        <!-- Banner Penilaian Guru (Jika sudah dinilai) -->
        <div id="modalGradeBanner" style="display:none;"></div>

        <!-- Info panduan menulis resume -->
        <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:0.84rem;color:#1E3A8A;line-height:1.5;">
          💡 <strong>Tips Membuat Resume yang Baik:</strong>
          Tuliskan ringkasan alur cerita/materi, tokoh utama, pesan moral atau intisari penting yang kamu pelajari dari buku ini.
        </div>

        <form id="resumeForm" onsubmit="submitResume(event)">
          <div style="margin-bottom:16px;">
            <label class="form-label" style="font-weight:700;color:var(--text-primary);">
              Isi Resume Buku <span style="color:#DC2626;">*</span>
            </label>
            <textarea id="resumeInputContent" class="form-input" rows="7" required
              placeholder="Tuliskan intisari, ringkasan, atau nilai penting dari buku yang telah kamu baca di sini..."
              style="width:100%;resize:vertical;line-height:1.6;font-size:0.95rem;padding:12px 14px;border:1.5px solid var(--border-color);border-radius:8px;"></textarea>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:0.8rem;color:var(--text-muted);">
              <span>Hanya siswa yang dapat mengirim resume untuk dinilai oleh guru.</span>
              <span id="charCount">0 karakter</span>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeResumeModal()">Batal</button>
        <button type="button" class="btn btn-primary" id="saveResumeSubmitBtn" onclick="submitResume(event)">
          🚀 Kirim Resume ke Guru
        </button>
      </div>
    </div>
  </div>

  <script src="{{ asset('pantaubaca/js/pdf.min.js') }}"></script>
  <script src="{{ asset('pantaubaca/js/app.js') }}"></script>
  <script src="{{ asset('pantaubaca/js/katalog.js') }}"></script>
</body>
</html>
