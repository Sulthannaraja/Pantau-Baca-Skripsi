<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Guru — PantauBaca</title>
  <meta name="description" content="Dashboard guru PantauBaca untuk memantau aktivitas literasi siswa, mengelola kelas, menilai resume buku siswa, dan memvalidasi jurnal membaca." />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('pantaubaca/css/style.css') }}?v={{ file_exists(public_path('pantaubaca/css/style.css')) ? filemtime(public_path('pantaubaca/css/style.css')) : time() }}" />
  <style>
    body { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
    .badge-graded { display:inline-block; padding:4px 12px; border-radius:20px; font-size:0.82rem; font-weight:800; background:#DCFCE7; color:#15803D; }
    .badge-pending { display:inline-block; padding:4px 12px; border-radius:20px; font-size:0.82rem; font-weight:800; background:#FEF3C7; color:#92400E; }
    .class-card { padding:16px; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; background:#F8FAFC; transition: box-shadow 0.2s; }
    .class-card:hover { box-shadow: 0 4px 12px rgba(67,97,238,0.1); }
    .class-badge { background:#EEF2FF; color:#4361EE; font-weight:bold; padding:8px 16px; border-radius:8px; }
  </style>
</head>
<body class="dashboard-page">

  <button class="sidebar-toggle" onclick="toggleMobileSidebar()" aria-label="Buka menu navigasi">☰</button>
  <div class="sidebar-overlay" onclick="closeMobileSidebar()"></div>

  <!-- Sidebar Navigation -->
  @include('pantaubaca.partials.sidebar', ['role' => 'guru', 'active' => 'overviewSection'])

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
        <button type="button" class="dashboard-nav-toggle" aria-label="Buka menu navigasi" onclick="toggleMobileSidebar()">☰</button>
        <div class="dashboard-greeting">
          <h1>Dashboard Guru 🧑‍🏫</h1>
          <p>Pantau perkembangan literasi siswa, periksa dan beri nilai resume buku mereka.</p>
        </div>
      </div>
      <div class="dashboard-header-actions">
        <a href="katalog.html">Lihat Katalog Buku</a>
        <a href="#" onclick="logout()">Logout</a>
        <div class="header-profile">
          <div class="header-profile-avatar" style="background:var(--primary);color:#fff;">G</div>
          <div class="header-profile-info">
            <span class="profile-name" id="profileName">Guru</span>
            <span class="profile-role">Guru Kelas</span>
          </div>
        </div>
      </div>
    </header>

    <div class="dashboard-content">

      <!-- FEATURE: Beranda Guru -->
      <div class="feature-section active" id="overviewSection">
        <div class="stat-grid">
          <div class="stat-card">
            <div class="stat-icon blue">🏫</div>
            <div class="stat-info"><h4>Total Kelas</h4><div class="stat-value" id="totalClasses">0</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon purple">👥</div>
            <div class="stat-info"><h4>Total Siswa</h4><div class="stat-value" id="totalStudents">0</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green">📚</div>
            <div class="stat-info"><h4>Total Buku</h4><div class="stat-value" id="bookReads">0</div></div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('penilaianResumeSection', event)">
            <div class="stat-icon orange">⏳</div>
            <div class="stat-info"><h4>Resume Menunggu Nilai</h4><div class="stat-value" id="pendingResumesCount" style="color:#D97706;">0</div></div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('penilaianResumeSection', event)">
            <div class="stat-icon green">⭐</div>
            <div class="stat-info"><h4>Resume Telah Dinilai</h4><div class="stat-value" id="gradedResumesCount" style="color:#059669;">0</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon blue">⏱</div>
            <div class="stat-info"><h4>Rata-rata Durasi</h4><div class="stat-value" id="avgDuration">0 menit</div></div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('surveyResultSection', event)">
            <div class="stat-icon" style="background:#FDE68A;color:#D97706;">📘</div>
            <div class="stat-info"><h4>Siswa Kategori Dasar</h4><div class="stat-value" id="overviewDasarCount" style="color:#D97706;">0</div></div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('surveyResultSection', event)">
            <div class="stat-icon" style="background:#A7F3D0;color:#065F46;">📗</div>
            <div class="stat-info"><h4>Siswa Kategori Lanjutan</h4><div class="stat-value" id="overviewLanjutanCount" style="color:#065F46;">0</div></div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('quizGradesSection', event)" title="Klik untuk lihat rekap nilai kuis siswa">
            <div class="stat-icon purple">📊</div>
            <div class="stat-info"><h4>Rata-rata Nilai Kuis</h4><div class="stat-value" id="overviewQuizAvgScore" style="color:var(--primary);">0</div></div>
          </div>
        </div>
      </div>

      <!-- FEATURE: Kelola Kelas -->
      <div class="feature-section" id="manageKelasSection">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">🏫</span> Kelola Kelas</h2>
              <p>Daftar kelas yang Anda ajar. Berikan kode kelas ini agar siswa bisa bergabung.</p>
            </div>
            <button class="btn btn-primary" onclick="createClass()">+ Buat Kelas Baru</button>
          </div>
          <div id="teacherClassList" style="margin-top:16px;">
            <p style="color:var(--text-secondary);font-size:0.9rem;">Belum ada kelas yang Anda kelola.</p>
          </div>
        </div>
      </div>

      <!-- FEATURE: Kelola & Input Buku -->
      <div class="feature-section" id="bukuSection">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">📤</span> Input / Upload Buku PDF Baru</h2>
              <p>Guru dapat mengunggah buku bacaan digital baru ke repositori PantauBaca untuk dibaca oleh siswa.</p>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;margin-top:16px;">
            <div>
              <label class="form-label">Judul Buku <span style="color:#DC2626;">*</span></label>
              <input id="teacherBookTitle" class="form-input" placeholder="Masukkan judul buku..." />
            </div>
            <div>
              <label class="form-label">Penulis <span style="color:#DC2626;">*</span></label>
              <input id="teacherBookAuthor" class="form-input" placeholder="Nama penulis / pengarang..." />
            </div>
            <div>
              <label class="form-label">Tahun Terbit</label>
              <input id="teacherBookYear" type="number" min="1000" max="2100" class="form-input" placeholder="Contoh: 2024" />
            </div>
            <div>
              <label class="form-label">Kategori / Genre</label>
              <select id="teacherBookCategory" class="form-select">
                <option value="Novel">Novel</option>
                <option value="Cerpen">Cerpen</option>
                <option value="Buku Pembelajaran">Buku Pembelajaran</option>
                <option value="Sains & Teknologi">Sains & Teknologi</option>
                <option value="Sejarah & Budaya">Sejarah & Budaya</option>
                <option value="Fiksi">Fiksi</option>
                <option value="Non-Fiksi">Non-Fiksi</option>
              </select>
            </div>
            <div>
              <label class="form-label">Kategori Kemampuan Literasi <span style="color:#DC2626;">*</span></label>
              <select id="teacherBookLiteracyLevel" class="form-select" required>
                <option value="">-- Pilih Kategori Literasi --</option>
                <option value="RENDAH">Buku Kemampuan Rendah</option>
                <option value="TINGGI">Buku Kemampuan Lanjutan</option>
              </select>
            </div>
            <div>
              <label class="form-label">URL Cover (Opsional)</label>
              <input id="teacherBookCover" class="form-input" placeholder="https://example.com/cover.jpg" />
            </div>
            <div style="grid-column:1/-1;">
              <label class="form-label">File Buku PDF <span style="color:#DC2626;">*</span> (Maksimal 20MB)</label>
              <input id="teacherBookFile" type="file" accept="application/pdf" class="form-input" style="padding:10px;" />
            </div>
          </div>
          <div style="margin-top:18px;">
            <button id="teacherUploadBtn" class="btn btn-primary" onclick="teacherUploadBook()">🚀 Simpan & Upload Buku</button>
          </div>
        </div>

        <div class="panel" style="margin-top:20px;">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">📚</span> Daftar Koleksi Buku Tersedia</h2>
              <p>Daftar buku yang sudah dapat diakses siswa di katalog untuk dibaca dan dibuat resume.</p>
            </div>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="width:50px;">No</th>
                  <th>Judul Buku</th>
                  <th>Penulis</th>
                  <th style="width:85px;">Tahun</th>
                  <th>Kategori / Genre</th>
                  <th>Kemampuan Literasi</th>
                  <th style="width:120px;">Aksi</th>
                </tr>
              </thead>
              <tbody id="teacherBookTableBody">
                <tr><td colspan="7" style="text-align:center;padding:16px;color:var(--text-muted);">Memuat koleksi buku...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- FEATURE: Penilaian Resume Siswa -->
      <div class="feature-section" id="penilaianResumeSection">
        <div class="panel">
          <div class="panel-header" style="flex-wrap:wrap;gap:12px;">
            <div>
              <h2><span class="panel-icon">📝</span> Penilaian Resume Siswa</h2>
              <p>Periksa hasil ringkasan/resume buku yang dikirimkan oleh siswa dan berikan penilaian serta catatan feedback.</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
              <label style="font-size:0.86rem;font-weight:700;">Filter Status:</label>
              <select id="resumeGradeStatusFilter" class="form-select" style="width:auto;min-width:180px;" onchange="filterStudentResumes()">
                <option value="ALL">Semua Resume</option>
                <option value="PENDING">⏳ Menunggu Nilai</option>
                <option value="GRADED">⭐ Sudah Dinilai</option>
              </select>
            </div>
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="width:50px;">No</th>
                  <th>Nama Siswa</th>
                  <th>Judul Buku</th>
                  <th>Waktu Kirim</th>
                  <th>Ringkasan Resume</th>
                  <th>Nilai</th>
                  <th>Catatan Guru</th>
                  <th style="width:130px;">Aksi</th>
                </tr>
              </thead>
              <tbody id="studentResumesTableBody">
                <tr><td colspan="8" style="text-align:center;padding:16px;color:var(--text-muted);">Memuat resume siswa...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- FEATURE: Aktivitas Siswa -->
      <div class="feature-section" id="activitySection">
        <div class="panel">
          <div class="panel-header">
            <h2><span class="panel-icon">📊</span> Aktivitas Membaca Siswa</h2>
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr><th>Nama Siswa</th><th>Buku</th><th>Durasi</th><th>Progres</th><th>Kategori</th></tr>
              </thead>
              <tbody id="teacherTableBody">
                <tr><td colspan="5" style="text-align:center;padding:16px;">Memuat data...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- FEATURE: Kelola Kuis -->
      <div class="feature-section" id="quizSection">
        <div class="panel">
          <div class="panel-header" style="flex-wrap:wrap;gap:12px;align-items:center;">
            <div>
              <h2><span class="panel-icon">🧪</span> Pengelolaan Kuis Pemahaman Buku</h2>
              <p>Kelola soal pilihan ganda (A–E) dan esai, tentukan kunci jawaban, ubah urutan, pratinjau tampilan siswa, dan publikasikan kuis.</p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
              <button type="button" class="btn btn-outline" id="previewQuizBtn" onclick="openQuizPreviewModal()">
                👁️ Pratinjau Kuis
              </button>
              <button type="button" class="btn btn-primary" id="saveQuizBtn">
                💾 Simpan Kuis & Perubahan
              </button>
            </div>
          </div>

          <!-- Indikator Status & Ringkasan Kuis -->
          <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;margin:18px 0;background:#F8FAFC;padding:16px;border-radius:14px;border:1.5px solid #E2E8F0;">
            <div>
              <label class="form-label" style="font-weight:700;margin-bottom:4px;">Pilih Buku</label>
              <select id="quizBook" class="form-select" onchange="onQuizBookSelected()"></select>
            </div>
            <div>
              <label class="form-label" style="font-weight:700;margin-bottom:4px;">Status Publikasi</label>
              <select id="quizActiveStatus" class="form-select">
                <option value="1">🟢 Aktif (Tampil untuk Siswa)</option>
                <option value="0">🟡 Draf (Belum Terbit)</option>
              </select>
            </div>
            <div style="display:flex;flex-direction:column;justify-content:center;">
              <span style="font-size:0.75rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;">Indikator Status</span>
              <div style="margin-top:4px;">
                <span id="quizStatusBadge" class="quiz-badge quiz-badge-notstarted">Pilih Buku</span>
              </div>
            </div>
            <div style="display:flex;flex-direction:column;justify-content:center;">
              <span style="font-size:0.75rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;">Ringkasan Soal</span>
              <div style="margin-top:4px;font-size:1rem;font-weight:800;color:var(--primary);">
                <span id="quizTotalQuestionsCount">0</span> Soal &bull; <span id="quizTotalPointsCount">0</span> Poin
              </div>
            </div>
          </div>

          <!-- Metadata Kuis -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
              <label class="form-label" style="font-weight:700;">Judul Kuis <span style="color:#DC2626;">*</span></label>
              <input type="text" id="quizTitle" class="form-input" placeholder="Contoh: Kuis Pemahaman Bab 1" />
            </div>
            <div>
              <label class="form-label" style="font-weight:700;">Deskripsi Singkat (Opsional)</label>
              <input type="text" id="quizDescription" class="form-input" placeholder="Contoh: Jawab pertanyaan berikut setelah menyelesaikan buku." />
            </div>
          </div>

          <!-- Form Tambah Soal Baru -->
          <div class="panel" style="background:#FFFFFF;border:1.5px solid #CBD5E1;border-radius:16px;padding:20px;margin:20px 0;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
              <h3 style="margin:0;font-size:1.05rem;font-weight:800;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
                <span>➕</span> Tambah Soal Baru
              </h3>
              <span style="font-size:0.82rem;color:var(--text-secondary);">Isi seluruh bidang secara lengkap</span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:12px;">
              <div>
                <label class="form-label" style="font-weight:700;">Tipe Soal</label>
                <select id="questionType" class="form-select">
                  <option value="MCQ">Pilihan Ganda (A s/d E)</option>
                  <option value="ESSAY">Esai Singkat</option>
                </select>
              </div>
              <div>
                <label class="form-label" style="font-weight:700;">Bobot Poin <span style="color:#DC2626;">*</span></label>
                <input type="number" id="questionPoints" class="form-input" value="20" min="1" max="100" />
              </div>
            </div>

            <div style="margin-bottom:14px;">
              <label class="form-label" style="font-weight:700;">Pertanyaan <span style="color:#DC2626;">*</span></label>
              <textarea id="questionText" class="form-input" rows="2" placeholder="Tuliskan pertanyaan kuis di sini..." style="min-height:75px;"></textarea>
            </div>

            <!-- Bagian Pilihan Jawaban A sampai E (Khusus MCQ) -->
            <div id="mcqOptionsBlock">
              <label class="form-label" style="font-weight:700;margin-bottom:8px;">Pilihan Jawaban (A sampai E) <span style="color:#DC2626;">*</span></label>
              <div style="display:grid;grid-template-columns:1fr;gap:10px;background:#F8FAFC;padding:16px;border-radius:12px;border:1.5px solid #E2E8F0;margin-bottom:14px;">
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">A</span>
                  <input type="text" id="optionA" class="form-input" placeholder="Teks pilihan A..." />
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">B</span>
                  <input type="text" id="optionB" class="form-input" placeholder="Teks pilihan B..." />
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">C</span>
                  <input type="text" id="optionC" class="form-input" placeholder="Teks pilihan C..." />
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">D</span>
                  <input type="text" id="optionD" class="form-input" placeholder="Teks pilihan D..." />
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">E</span>
                  <input type="text" id="optionE" class="form-input" placeholder="Teks pilihan E..." />
                </div>
              </div>

              <div style="margin-bottom:14px;">
                <label class="form-label" style="font-weight:700;">Kunci Jawaban Benar <span style="color:#DC2626;">*</span></label>
                <select id="questionKeySelect" class="form-select">
                  <option value="">-- Pilih Kunci Jawaban yang Benar --</option>
                  <option value="A">Pilihan A</option>
                  <option value="B">Pilihan B</option>
                  <option value="C">Pilihan C</option>
                  <option value="D">Pilihan D</option>
                  <option value="E">Pilihan E</option>
                </select>
                <small style="color:var(--text-secondary);font-size:0.82rem;margin-top:4px;display:block;">Sistem akan memastikan teks pilihan yang dipilih otomatis menjadi kunci jawaban server.</small>
              </div>
            </div>

            <!-- Bagian Jawaban Esai -->
            <div id="essayOptionsBlock" style="display:none;margin-bottom:14px;">
              <label class="form-label" style="font-weight:700;">Panduan / Kunci Jawaban Esai (Opsional)</label>
              <textarea id="essayKeyInput" class="form-input" rows="2" placeholder="Kata kunci atau pedoman penilaian esai..." style="min-height:75px;"></textarea>
            </div>

            <!-- Pembahasan / Penjelasan Soal -->
            <div style="margin-bottom:16px;">
              <label class="form-label" style="font-weight:700;">Pembahasan Soal (Opsional - Tampil setelah Siswa Selesai)</label>
              <textarea id="questionExplanation" class="form-input" rows="2" placeholder="Tuliskan alasan atau kutipan teks dari buku yang mendasari jawaban benar..." style="min-height:65px;"></textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;">
              <button type="button" class="btn btn-outline" id="addQuestionBtn">
                ➕ Tambahkan Soal ke Daftar
              </button>
            </div>
          </div>

          <!-- Daftar Soal Kuis -->
          <div style="margin-top:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
              <h3 style="margin:0;font-size:1.05rem;font-weight:800;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
                <span>📋</span> Daftar Soal Kuis (<span id="tableQuestionCounter">0</span> Soal)
              </h3>
              <small style="color:var(--text-secondary);font-size:0.84rem;">Gunakan tombol ▲ dan ▼ untuk mengatur urutan soal</small>
            </div>

            <div class="table-responsive">
              <table class="data-table" style="margin-top:6px;width:100%;">
                <thead>
                  <tr>
                    <th style="width:80px;text-align:center;">Urutan</th>
                    <th>Pertanyaan & Opsi Jawaban</th>
                    <th style="width:130px;text-align:center;">Tipe</th>
                    <th style="width:90px;text-align:center;">Poin</th>
                    <th style="width:160px;text-align:center;">Aksi</th>
                  </tr>
                </thead>
                <tbody id="quizQuestionBody">
                  <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">Belum ada soal pada kuis ini. Tambahkan soal pertama di atas.</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div style="margin-top:24px;padding-top:18px;border-top:1.5px solid #E2E8F0;display:flex;justify-content:flex-end;gap:12px;">
            <button type="button" class="btn btn-outline" onclick="openQuizPreviewModal()">👁️ Pratinjau Tampilan Siswa</button>
            <button type="button" class="btn btn-primary" id="saveQuizBottomBtn" onclick="saveQuiz()">💾 Simpan Kuis & Perubahan</button>
          </div>
        </div>
      </div>

      <!-- FEATURE: Rekap Nilai Kuis -->
      <div class="feature-section" id="quizGradesSection">
        <div class="panel">
          <div class="panel-header" style="flex-wrap:wrap;gap:12px;align-items:center;">
            <div>
              <h2><span class="panel-icon">📊</span> Rekap Nilai Kuis Pemahaman Siswa</h2>
              <p>Rekapitulasi lengkap hasil pengerjaan, nilai akhir, dan rincian jawaban kuis siswa yang Anda ajar.</p>
            </div>
            <div style="display:flex;gap:10px;">
              <button type="button" class="btn btn-outline btn-sm" onclick="loadQuizGradesRecap()" style="display:inline-flex;align-items:center;gap:6px;">
                🔄 Segarkan Data
              </button>
            </div>
          </div>

          <!-- Ringkasan Nilai Kuis (Stat Cards) -->
          <div class="quiz-stat-grid">
            <div class="stat-card" style="border:1.5px solid #E2E8F0;">
              <div class="stat-icon blue">👥</div>
              <div class="stat-info">
                <h4>Total Siswa</h4>
                <div class="stat-value" id="recapTotalStudents">0</div>
                <div class="stat-desc">Siswa dalam kelas binaan</div>
              </div>
            </div>
            <div class="stat-card" style="border:1.5px solid #6EE7B7;background:linear-gradient(135deg,#ECFDF5,#D1FAE5);">
              <div class="stat-icon" style="background:#A7F3D0;color:#065F46;">✅</div>
              <div class="stat-info">
                <h4 style="color:#065F46;">Sudah Menyelesaikan</h4>
                <div class="stat-value" id="recapCompletedCount" style="color:#065F46;">0</div>
                <div class="stat-desc" style="color:#047857;">Siswa telah mengerjakan kuis</div>
              </div>
            </div>
            <div class="stat-card" style="border:1.5px solid #FCD34D;background:linear-gradient(135deg,#FFFBEB,#FEF3C7);">
              <div class="stat-icon" style="background:#FDE68A;color:#D97706;">⏳</div>
              <div class="stat-info">
                <h4 style="color:#92400E;">Belum Menyelesaikan</h4>
                <div class="stat-value" id="recapPendingCount" style="color:#92400E;">0</div>
                <div class="stat-desc" style="color:#B45309;">Belum memiliki riwayat kuis</div>
              </div>
            </div>
            <div class="stat-card" style="border:1.5px solid #C7D2FE;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);">
              <div class="stat-icon" style="background:#C7D2FE;color:#4338CA;">📈</div>
              <div class="stat-info">
                <h4 style="color:#3730A3;">Rata-rata Nilai Kuis</h4>
                <div class="stat-value" id="recapAverageScore" style="color:#3730A3;">0</div>
                <div class="stat-desc" style="color:#4338CA;">Dari siswa yang selesai</div>
              </div>
            </div>
          </div>

          <!-- Fitur Pengelolaan Data: Toolbar Filter, Pencarian & Pengurutan -->
          <div class="recap-filter-toolbar">
            <!-- Pencarian Siswa -->
            <div class="filter-field-item filter-search">
              <label for="recapSearchInput">Pencarian Siswa</label>
              <input type="text" id="recapSearchInput" class="form-input" placeholder="🔍 Cari nama siswa..." oninput="applyRecapFilters()" />
            </div>
            <!-- Filter Kelas -->
            <div class="filter-field-item filter-class">
              <label for="recapClassFilter">Kelas</label>
              <select id="recapClassFilter" class="form-select" onchange="applyRecapFilters()">
                <option value="ALL">Semua Kelas</option>
              </select>
            </div>
            <!-- Filter Judul Kuis -->
            <div class="filter-field-item filter-quiz">
              <label for="recapQuizFilter">Judul Kuis</label>
              <select id="recapQuizFilter" class="form-select" onchange="applyRecapFilters()">
                <option value="ALL">Semua Judul Kuis</option>
              </select>
            </div>
            <!-- Filter Status Pengerjaan -->
            <div class="filter-field-item filter-status">
              <label for="recapStatusFilter">Status</label>
              <select id="recapStatusFilter" class="form-select" onchange="applyRecapFilters()">
                <option value="ALL">Semua Status</option>
                <option value="COMPLETED">Selesai Dikerjakan</option>
                <option value="NOT_STARTED">Belum Mengerjakan</option>
              </select>
            </div>
            <!-- Pengurutan Nilai -->
            <div class="filter-field-item filter-sort">
              <label for="recapSortOrder">Urutkan</label>
              <select id="recapSortOrder" class="form-select" onchange="applyRecapFilters()">
                <option value="score_desc">Nilai Tertinggi ke Terendah ↓</option>
                <option value="score_asc">Nilai Terendah ke Tertinggi ↑</option>
                <option value="name_asc">Nama Siswa (A - Z)</option>
                <option value="date_desc">Waktu Pengerjaan Terbaru</option>
              </select>
            </div>
          </div>

          <!-- Tabel Rekap Nilai Kuis Siswa -->
          <div class="table-responsive recap-table-responsive">
            <table class="data-table" id="quizGradesTable">
              <thead>
                <tr>
                  <th style="width:44px;text-align:center;">No</th>
                  <th style="min-width:150px;">Nama Siswa</th>
                  <th style="min-width:85px;">Kelas</th>
                  <th style="min-width:160px;">Judul Kuis</th>
                  <th style="text-align:center;min-width:85px;">Jumlah Soal</th>
                  <th style="text-align:center;min-width:95px;">Jawaban Benar</th>
                  <th style="text-align:center;min-width:85px;">Nilai Akhir</th>
                  <th style="text-align:center;min-width:130px;">Status Pengerjaan</th>
                  <th style="min-width:140px;">Waktu Penyelesaian</th>
                  <th style="text-align:center;width:80px;">Aksi</th>
                </tr>
              </thead>
              <tbody id="quizGradesTableBody">
                <tr>
                  <td colspan="10" style="text-align:center;padding:24px;color:var(--text-muted);">
                    Memuat rekap nilai kuis...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- FEATURE: Hasil Angket Literasi Digital Siswa -->
      <div class="feature-section" id="surveyResultSection">
        <div class="stat-grid" style="margin-bottom:20px;">
          <div class="stat-card" style="border:2px solid #E2E8F0;">
            <div class="stat-icon blue">📝</div>
            <div class="stat-info">
              <h4>Total Responden Siswa</h4>
              <div class="stat-value" id="surveyTotalStudents">0</div>
              <div class="stat-desc">Siswa telah mengisi angket</div>
            </div>
          </div>
          <div class="stat-card" style="border:2px solid #FCD34D;background:linear-gradient(135deg,#FFFBEB,#FEF3C7);">
            <div class="stat-icon" style="background:#FDE68A;color:#D97706;">📘</div>
            <div class="stat-info">
              <h4 style="color:#92400E;">Tingkat Rendah (Dasar)</h4>
              <div class="stat-value" id="surveyDasarCount" style="color:#92400E;">0</div>
              <div class="stat-desc" style="color:#B45309;">Skor &lt; 28</div>
            </div>
          </div>
          <div class="stat-card" style="border:2px solid #6EE7B7;background:linear-gradient(135deg,#ECFDF5,#D1FAE5);">
            <div class="stat-icon" style="background:#A7F3D0;color:#065F46;">📗</div>
            <div class="stat-info">
              <h4 style="color:#065F46;">Tingkat Tinggi (Lanjutan)</h4>
              <div class="stat-value" id="surveyLanjutanCount" style="color:#065F46;">0</div>
              <div class="stat-desc" style="color:#047857;">Skor ≥ 28</div>
            </div>
          </div>
          <div class="stat-card" style="border:2px solid #C7D2FE;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);">
            <div class="stat-icon" style="background:#C7D2FE;color:#4338CA;">📊</div>
            <div class="stat-info">
              <h4 style="color:#3730A3;">Rata-rata Skor</h4>
              <div class="stat-value" id="surveyAvgScore" style="color:#3730A3;">0/40</div>
              <div class="stat-desc" style="color:#4338CA;">Skala Likert 10 butir instrumen</div>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header" style="flex-wrap:wrap;gap:12px;">
            <div>
              <h2><span class="panel-icon">📊</span> Hasil Angket Literasi Digital Siswa</h2>
              <p>Rekapitulasi tingkat kemampuan literasi digital siswa.</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <label style="font-size:0.86rem;font-weight:700;">Filter Tingkat:</label>
              <select id="surveyFilterCategory" class="form-select" style="width:auto;min-width:180px;" onchange="filterSurveyTable()">
                <option value="ALL">Semua Tingkat</option>
                <option value="RENDAH">Tingkat Rendah</option>
                <option value="TINGGI">Tingkat Tinggi</option>
              </select>
            </div>
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="width:50px;">No</th>
                  <th>Nama Siswa</th>
                  <th>Email / Akun</th>
                  <th>Skor Angket</th>
                  <th>Persentase</th>
                  <th>Tingkat Literasi</th>
                  <th>Waktu Pengisian</th>
                </tr>
              </thead>
              <tbody id="surveyTableBody">
                <tr><td colspan="7" style="text-align:center;padding:16px;color:var(--text-muted);">Memuat data angket literasi...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Modal Beri Nilai Resume Siswa -->
  <div id="gradeModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.7);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#FFFFFF;width:100%;max-width:640px;max-height:90vh;border-radius:16px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);display:flex;flex-direction:column;overflow:hidden;border:1px solid #E2E8F0;">
      <div style="padding:18px 24px;background:#F8FAFC;border-bottom:1.5px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;">
        <div>
          <span style="font-size:0.75rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;">Formulir Penilaian Guru</span>
          <h3 style="margin:2px 0 0 0;font-size:1.2rem;font-weight:800;color:var(--text-primary);" id="gradeModalTitle">Beri Nilai Resume Siswa</h3>
          <p style="margin:0;font-size:0.84rem;color:var(--text-secondary);" id="gradeModalSubtitle">-</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeGradeModal()" style="border-radius:50%;width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;">✕</button>
      </div>
      <div style="padding:22px 24px;overflow-y:auto;flex:1;">
        <label style="font-weight:700;font-size:0.86rem;color:#475569;display:block;margin-bottom:6px;">Teks Resume Siswa:</label>
        <div id="gradeModalContent" style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:10px;padding:14px 16px;max-height:220px;overflow-y:auto;font-size:0.92rem;line-height:1.65;color:#1E293B;white-space:pre-wrap;margin-bottom:18px;"></div>
        
        <div style="margin-bottom:16px;">
          <label class="form-label" style="font-weight:700;">Nilai Siswa (Skala 0 - 100) <span style="color:#DC2626;">*</span></label>
          <input type="number" id="gradeScoreInput" class="form-input" min="0" max="100" placeholder="Contoh: 85" style="width:160px;font-size:1.1rem;font-weight:700;padding:8px 12px;" />
        </div>
        <div>
          <label class="form-label" style="font-weight:700;">Catatan / Komentar Guru (Opsional)</label>
          <textarea id="gradeFeedbackInput" class="form-input" rows="3" placeholder="Tuliskan apresiasi, masukan, atau saran untuk siswa..."></textarea>
        </div>
      </div>
      <div style="padding:16px 24px;background:#F8FAFC;border-top:1.5px solid #E2E8F0;display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-outline" onclick="closeGradeModal()">Batal</button>
        <button type="button" class="btn btn-primary" id="saveGradeBtn" onclick="submitGradeResume()">💾 Simpan Nilai</button>
      </div>
    </div>
  </div>

  <!-- Modal Edit Soal Kuis Guru -->
  <div id="editQuestionModal" class="quiz-custom-modal" onclick="if(event.target === this) closeEditQuestionModal()">
    <div class="quiz-custom-modal-card">
      <div class="quiz-custom-modal-header">
        <div>
          <span style="font-size:0.75rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;">Pengelolaan Soal</span>
          <h3 style="margin:2px 0 0 0;font-size:1.15rem;font-weight:800;color:var(--text-primary);" id="editQuestionModalTitle">✏️ Edit Soal Kuis</h3>
          <p style="margin:0;font-size:0.84rem;color:var(--text-secondary);">Perbaiki pertanyaan, seluruh pilihan jawaban A sampai E, kunci jawaban, dan poin.</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeEditQuestionModal()" style="border-radius:50%;width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;">✕</button>
      </div>

      <div class="quiz-custom-modal-body">
        <input type="hidden" id="editQuestionIndex" />

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:12px;">
          <div>
            <label class="form-label" style="font-weight:700;">Tipe Soal</label>
            <select id="editQuestionType" class="form-select" onchange="syncEditQuestionType()">
              <option value="MCQ">Pilihan Ganda (A s/d E)</option>
              <option value="ESSAY">Esai Singkat</option>
            </select>
          </div>
          <div>
            <label class="form-label" style="font-weight:700;">Bobot Poin <span style="color:#DC2626;">*</span></label>
            <input type="number" id="editQuestionPoints" class="form-input" min="1" max="100" />
          </div>
        </div>

        <div style="margin-bottom:14px;">
          <label class="form-label" style="font-weight:700;">Pertanyaan <span style="color:#DC2626;">*</span></label>
          <textarea id="editQuestionText" class="form-input" rows="3" placeholder="Tuliskan pertanyaan kuis..."></textarea>
        </div>

        <!-- Pilihan Jawaban A s/d E untuk Edit -->
        <div id="editMcqBlock">
          <label class="form-label" style="font-weight:700;margin-bottom:8px;">Pilihan Jawaban (A sampai E) <span style="color:#DC2626;">*</span></label>
          <div style="display:grid;grid-template-columns:1fr;gap:10px;background:#F8FAFC;padding:16px;border-radius:12px;border:1.5px solid #E2E8F0;margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">A</span>
              <input type="text" id="editOptionA" class="form-input" placeholder="Pilihan A..." />
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">B</span>
              <input type="text" id="editOptionB" class="form-input" placeholder="Pilihan B..." />
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">C</span>
              <input type="text" id="editOptionC" class="form-input" placeholder="Pilihan C..." />
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">D</span>
              <input type="text" id="editOptionD" class="form-input" placeholder="Pilihan D..." />
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="width:32px;height:32px;border-radius:50%;background:#EEF2FF;color:#4361EE;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">E</span>
              <input type="text" id="editOptionE" class="form-input" placeholder="Pilihan E..." />
            </div>
          </div>

          <div style="margin-bottom:14px;">
            <label class="form-label" style="font-weight:700;">Kunci Jawaban Benar <span style="color:#DC2626;">*</span></label>
            <select id="editQuestionKeySelect" class="form-select">
              <option value="">-- Pilih Kunci Jawaban Benar --</option>
              <option value="A">Pilihan A</option>
              <option value="B">Pilihan B</option>
              <option value="C">Pilihan C</option>
              <option value="D">Pilihan D</option>
              <option value="E">Pilihan E</option>
            </select>
          </div>
        </div>

        <div id="editEssayBlock" style="display:none;margin-bottom:14px;">
          <label class="form-label" style="font-weight:700;">Panduan Jawaban Esai (Opsional)</label>
          <textarea id="editEssayKeyInput" class="form-input" rows="2" placeholder="Panduan penilaian esai..."></textarea>
        </div>

        <div style="margin-bottom:14px;">
          <label class="form-label" style="font-weight:700;">Pembahasan Soal (Opsional)</label>
          <textarea id="editQuestionExplanation" class="form-input" rows="2" placeholder="Kutipan atau penjelasan mengapa jawaban ini benar..."></textarea>
        </div>
      </div>

      <div class="quiz-custom-modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeEditQuestionModal()">Batal</button>
        <button type="button" class="btn btn-primary" onclick="submitEditQuestion()">💾 Simpan Perubahan Soal</button>
      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Hapus Soal -->
  <div id="deleteQuestionModal" class="quiz-custom-modal" onclick="if(event.target === this) closeDeleteQuestionModal()">
    <div class="quiz-custom-modal-card" style="max-width:520px;">
      <div class="quiz-custom-modal-header" style="background:#FEF2F2;border-color:#FCA5A5;">
        <div>
          <span style="font-size:0.75rem;font-weight:800;color:#DC2626;text-transform:uppercase;letter-spacing:1px;">Konfirmasi Hapus Soal</span>
          <h3 style="margin:2px 0 0 0;font-size:1.15rem;font-weight:800;color:#991B1B;">Hapus Soal dari Kuis?</h3>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeDeleteQuestionModal()" style="border-radius:50%;width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;">✕</button>
      </div>

      <div class="quiz-custom-modal-body">
        <input type="hidden" id="deleteQuestionIndex" />
        <p style="color:#475569;margin-bottom:12px;font-size:0.94rem;">
          Anda akan menghapus <strong id="deleteQuestionNumberBadge" style="color:#DC2626;">Soal #1</strong>:
        </p>
        <div id="deleteQuestionSummaryBox" style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:12px;padding:14px 16px;font-size:0.92rem;color:#1E293B;line-height:1.6;font-weight:600;margin-bottom:16px;">
          -
        </div>
        <p style="font-size:0.84rem;color:#64748B;margin:0;">
          Tindakan ini akan menghapus soal dari daftar kuis. Klik "Simpan Kuis & Perubahan" untuk menerapkan permanen ke database.
        </p>
      </div>

      <div class="quiz-custom-modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeDeleteQuestionModal()">Batal</button>
        <button type="button" class="btn btn-primary" onclick="confirmDeleteQuestion()" style="background:#DC2626;border-color:#DC2626;">🗑️ Ya, Hapus Soal</button>
      </div>
    </div>
  </div>

  <!-- Modal Pratinjau Kuis Siswa -->
  <div id="previewQuizModal" class="quiz-custom-modal" onclick="if(event.target === this) closeQuizPreviewModal()">
    <div class="quiz-custom-modal-card" style="max-width:860px;">
      <div class="quiz-custom-modal-header" style="background:linear-gradient(135deg, #EEF2FF 0%, #FAF5FF 100%);">
        <div>
          <span style="font-size:0.75rem;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:1px;">Mode Pratinjau Guru</span>
          <h3 style="margin:2px 0 0 0;font-size:1.2rem;font-weight:800;color:var(--text-primary);" id="previewModalQuizTitle">Pratinjau Kuis Pemahaman</h3>
          <p style="margin:0;font-size:0.84rem;color:var(--text-secondary);" id="previewModalQuizSubtitle">Tampilan ini persis sebagaimana dilihat oleh siswa saat mengerjakan kuis.</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeQuizPreviewModal()" style="border-radius:50%;width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;">✕</button>
      </div>

      <div class="quiz-custom-modal-body" id="previewQuizBody">
        <!-- Rendered dynamically via JS -->
      </div>

      <div class="quiz-custom-modal-footer">
        <button type="button" class="btn btn-primary" onclick="closeQuizPreviewModal()">Tutup Pratinjau</button>
      </div>
    </div>
  </div>

  <!-- Modal Detail Hasil Pengerjaan Kuis Siswa -->
  <div id="quizResultDetailModal" class="quiz-custom-modal" onclick="if(event.target === this) closeQuizResultDetailModal()">
    <div class="quiz-custom-modal-card" style="max-width:840px;">
      <div class="quiz-custom-modal-header" style="background:linear-gradient(135deg, #EEF2FF 0%, #FAF5FF 100%);">
        <div>
          <span style="font-size:0.75rem;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:1px;">Rincian Pengerjaan Kuis</span>
          <h3 style="margin:2px 0 0 0;font-size:1.25rem;font-weight:800;color:var(--text-primary);" id="detailModalStudentName">Nama Siswa</h3>
          <p style="margin:0;font-size:0.86rem;color:var(--text-secondary);" id="detailModalMetaInfo">Memuat detail pengerjaan...</p>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeQuizResultDetailModal()" style="border-radius:50%;width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;">✕</button>
      </div>

      <div class="quiz-custom-modal-body" id="detailModalBody" style="max-height:75vh;overflow-y:auto;padding:24px;">
        <!-- Rendered dynamically via JS -->
      </div>

      <div class="quiz-custom-modal-footer">
        <button type="button" class="btn btn-primary" onclick="closeQuizResultDetailModal()">Tutup Detail</button>
      </div>
    </div>
  </div>

  <script src="{{ asset('pantaubaca/js/pdf.min.js') }}"></script>
  <script src="{{ asset('pantaubaca/js/app.js') }}?v={{ file_exists(public_path('pantaubaca/js/app.js')) ? filemtime(public_path('pantaubaca/js/app.js')) : time() }}"></script>
  <script src="{{ asset('pantaubaca/js/dashboard-guru.js') }}?v={{ file_exists(public_path('pantaubaca/js/dashboard-guru.js')) ? filemtime(public_path('pantaubaca/js/dashboard-guru.js')) : time() }}"></script>
  <script>
    async function createClass() {
      let name = await window.appPrompt("Masukkan nama kelas", "contoh: Kelas 7A");
      if (name) {
        try {
          const res = await apiPost('/classrooms', { name });
          showToast(res.message || "Kelas berhasil dibuat", "success");
          loadTeacherClasses();
        } catch(e) {
          showToast(e.message, "error");
        }
      }
    }

    async function loadTeacherClasses() {
      try {
        const classes = await apiGet('/classrooms');
        const container = document.getElementById('teacherClassList');
        const totalClassesEl = document.getElementById('totalClasses');
        if (totalClassesEl) totalClassesEl.textContent = classes.length;
        let totalS = 0;
        classes.forEach(c => totalS += (c.users_count || 0));
        const totalStudentsEl = document.getElementById('totalStudents');
        if (totalStudentsEl) totalStudentsEl.textContent = totalS;
        if (classes.length === 0) {
          container.innerHTML = '<p style="color:var(--text-secondary);font-size:0.9rem;">Belum ada kelas yang Anda kelola.</p>';
          return;
        }
        container.innerHTML = classes.map(c => `
          <div class="class-card">
            <div>
              <h3 style="font-size:1.1rem;margin:0 0 6px 0;color:var(--primary);">${escapeHtml(c.name)}</h3>
              <p style="margin:0;font-size:0.9rem;color:#64748b;">Kode Kelas: <strong style="color:var(--primary);background:#EEF2FF;padding:2px 8px;border-radius:4px;letter-spacing:2px;">${escapeHtml(c.code)}</strong></p>
            </div>
            <div class="class-badge">${c.users_count || 0} Siswa</div>
          </div>
        `).join("");
      } catch(e) {
        console.error(e);
      }
    }

    window.addEventListener('DOMContentLoaded', loadTeacherClasses);
  </script>
</body>
</html>
