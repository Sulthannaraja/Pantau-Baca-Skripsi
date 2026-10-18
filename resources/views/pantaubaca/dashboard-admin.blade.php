<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Admin — PantauBaca</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('pantaubaca/css/style.css') }}?v={{ file_exists(public_path('pantaubaca/css/style.css')) ? filemtime(public_path('pantaubaca/css/style.css')) : time() }}" />
  <style>
    body { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
  </style>
</head>
<body class="dashboard-page">

  <!-- Sidebar Toggle (Mobile) -->
  <button class="sidebar-toggle" onclick="toggleMobileSidebar()" aria-label="Buka menu navigasi">☰</button>
  <div class="sidebar-overlay" onclick="closeMobileSidebar()"></div>

  <!-- Sidebar Navigation -->
  @include('pantaubaca.partials.sidebar', ['role' => 'admin', 'active' => 'overviewSection'])

  <!-- Main -->
  <main class="dashboard-main">
    <!-- Header -->
    <header class="dashboard-header">
      <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
        <button type="button" class="dashboard-nav-toggle" aria-label="Buka menu navigasi" onclick="toggleMobileSidebar()">☰</button>
        <div class="dashboard-greeting">
          <h1>Dashboard Administrator 🛡️</h1>
          <p>Kontrol penuh atas manajemen buku, akun pengguna, dan konten PantauBaca.</p>
        </div>
      </div>
      <div class="dashboard-header-actions">
        <a href="katalog.html">Katalog Buku</a>
        <a href="#" onclick="logout()">Logout</a>
        <div class="header-profile">
          <div class="header-profile-avatar" style="background:var(--accent-amber);color:#FFFFFF;">A</div>
          <div class="header-profile-info">
            <span class="profile-name">Administrator</span>
            <span class="profile-role">Admin</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Content -->
    <div class="dashboard-content">

      <!-- FEATURE: Admin Overview -->
      <div class="feature-section active" id="overviewSection">
        <!-- Stat Cards -->
        <div class="stat-grid">
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('bukuSection', event)" title="Klik untuk lihat manajemen buku">
            <div class="stat-icon blue">📚</div>
            <div class="stat-info">
              <h4>Koleksi Buku</h4>
              <div class="stat-value" id="adminBookCount">0</div>
              <div class="stat-desc">Buku terdaftar dalam sistem (Klik kelola)</div>
            </div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('userSection', event)" title="Klik untuk lihat manajemen pengguna">
            <div class="stat-icon green">👥</div>
            <div class="stat-info">
              <h4>Akun Pengguna</h4>
              <div class="stat-value" id="adminUserCount">0</div>
              <div class="stat-desc">Siswa, Guru, dan Admin (Klik kelola)</div>
            </div>
          </div>
          <div class="stat-card" style="cursor:pointer;" onclick="switchFeature('uploadSection', event)" title="Klik untuk upload buku">
            <div class="stat-icon purple">📤</div>
            <div class="stat-info">
              <h4>Upload PDF</h4>
              <div class="stat-value">+ Baru</div>
              <div class="stat-desc">Tambah bacaan digital baru</div>
            </div>
          </div>
        </div>

        <!-- Quick Navigation Cards -->
        <div style="margin-top:28px;">
          <h3 style="font-size:1.15rem;font-weight:800;color:var(--text-primary);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <span>⚡</span> Akses Cepat Menu Administrator
          </h3>
          <div class="feature-nav-grid">
            <div class="feature-nav-card" onclick="switchFeature('uploadSection', event)">
              <div>
                <div class="feat-icon" style="background:#EEF2FF;color:var(--primary);">📤</div>
                <h4 style="margin-top:12px;">Upload Buku PDF</h4>
                <p>Unggah file buku PDF baru beserta cover dan penentuan kategori materi.</p>
              </div>
              <span class="feat-link">Buka Upload →</span>
            </div>

            <div class="feature-nav-card" onclick="switchFeature('bukuSection', event)">
              <div>
                <div class="feat-icon" style="background:#ECFDF5;color:#059669;">📖</div>
                <h4 style="margin-top:12px;">Manajemen Buku</h4>
                <p>Kelola daftar buku, perbarui informasi judul/penulis, atau hapus buku.</p>
              </div>
              <span class="feat-link">Kelola Buku →</span>
            </div>

            <div class="feature-nav-card" onclick="switchFeature('userSection', event)">
              <div>
                <div class="feat-icon" style="background:#F5F3FF;color:#7209B7;">👥</div>
                <h4 style="margin-top:12px;">Manajemen Pengguna</h4>
                <p>Atur hak akses akun, perbarui profil guru/siswa, atau hapus pengguna.</p>
              </div>
              <span class="feat-link">Kelola User →</span>
            </div>
          </div>
        </div>
      </div>

      <!-- FEATURE: Upload Buku Section -->
      <div class="feature-section" id="uploadSection">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">📤</span> Upload Buku PDF Baru</h2>
              <p>Tambahkan materi bacaan digital dalam format PDF untuk siswa dan guru.</p>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;margin-top:10px;">
            <div>
              <label style="font-size:0.86rem;font-weight:700;margin-bottom:6px;display:block;">Judul Buku</label>
              <input id="title" class="input" placeholder="Masukkan judul buku..." />
            </div>
            <div>
              <label style="font-size:0.86rem;font-weight:700;margin-bottom:6px;display:block;">Penulis</label>
              <input id="author" class="input" placeholder="Nama penulis..." />
            </div>
            <div>
              <label style="font-size:0.86rem;font-weight:700;margin-bottom:6px;display:block;">Tahun Terbit</label>
              <input id="year" type="number" min="1000" max="2100" class="input" placeholder="Contoh: 2024" />
            </div>
            <div>
              <label style="font-size:0.86rem;font-weight:700;margin-bottom:6px;display:block;">Kategori</label>
              <select id="category" class="input" style="width:100%;">
                <option value="Novel">Novel</option>
                <option value="Cerpen">Cerpen</option>
                <option value="Buku Pembelajaran">Buku Pembelajaran</option>
                <option value="Sains & Teknologi">Sains & Teknologi</option>
                <option value="Sejarah & Budaya">Sejarah & Budaya</option>
              </select>
            </div>
            <div>
              <label style="font-size:0.86rem;font-weight:700;margin-bottom:6px;display:block;">URL Cover (Opsional)</label>
              <input id="cover" class="input" placeholder="https://..." />
            </div>
            <div style="grid-column:1/-1;">
              <label style="font-size:0.86rem;font-weight:700;margin-bottom:6px;display:block;">File PDF</label>
              <input id="file" type="file" accept="application/pdf" class="input" style="padding:10px;" />
            </div>
          </div>
          <div style="margin-top:18px;">
            <button id="uploadBtn" class="btn btn-primary">🚀 Upload Buku</button>
          </div>
        </div>
      </div>

      <!-- FEATURE: Tabel Manajemen Buku -->
      <div class="feature-section" id="bukuSection">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">📚</span> Manajemen Koleksi Buku</h2>
              <p>Daftar seluruh buku yang terdaftar di repositori PantauBaca.</p>
            </div>
            <button class="btn btn-primary btn-sm" onclick="switchFeature('uploadSection', event)">+ Upload Buku Baru</button>
          </div>
          <div class="table-responsive">
            <table>
              <thead><tr><th>Judul Buku</th><th>Penulis</th><th>Tahun</th><th>Kategori</th><th style="width:160px;">Aksi</th></tr></thead>
              <tbody id="adminBookBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- FEATURE: Tabel Manajemen User -->
      <div class="feature-section" id="userSection">
        <div class="panel">
          <div class="panel-header">
            <div>
              <h2><span class="panel-icon">👥</span> Manajemen Pengguna</h2>
              <p>Daftar akun terdaftar dalam sistem (Siswa, Guru, Admin).</p>
            </div>
          </div>
          <div class="table-responsive">
            <table>
              <thead><tr><th>Nama Pengguna</th><th>Email</th><th>Role</th><th style="width:160px;">Aksi</th></tr></thead>
              <tbody id="adminUserBody"></tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>

  <script src="{{ asset('pantaubaca/js/pdf.min.js') }}"></script>
  <script src="{{ asset('pantaubaca/js/app.js') }}?v={{ file_exists(public_path('pantaubaca/js/app.js')) ? filemtime(public_path('pantaubaca/js/app.js')) : time() }}"></script>
  <script src="{{ asset('pantaubaca/js/dashboard-admin.js') }}?v={{ file_exists(public_path('pantaubaca/js/dashboard-admin.js')) ? filemtime(public_path('pantaubaca/js/dashboard-admin.js')) : time() }}"></script>
</body>
</html>
