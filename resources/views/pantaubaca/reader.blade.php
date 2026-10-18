<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Membaca Buku — PantauBaca</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('pantaubaca/css/style.css') }}?v={{ file_exists(public_path('pantaubaca/css/style.css')) ? filemtime(public_path('pantaubaca/css/style.css')) : time() }}" />
  <style>
    :root {
      --reader-bg: #F1F5F9;
      --card-bg: #FFFFFF;
      --card-border: #E2E8F0;
      --card-radius: 16px;
      --primary-accent: #4361EE;
      --purple-accent: #8B5CF6;
      --success-accent: #10B981;
    }

    body {
      background: var(--reader-bg);
      overflow-x: hidden;
      min-height: 100vh;
    }

    .reader-page-wrapper {
      display: grid;
      grid-template-columns: 240px minmax(0, 1fr);
      min-height: calc(100vh - 74px);
      background: var(--reader-bg);
      position: relative;
    }

    /* ---------- Sidebar Progres Membaca ---------- */
    .reader-sidebar {
      background: #FFFFFF;
      border-right: 1px solid var(--card-border);
      padding: 16px 14px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      height: calc(100vh - 74px);
      position: sticky;
      top: 74px;
      overflow-y: auto;
      box-shadow: 2px 0 12px rgba(15, 23, 42, 0.03);
      z-index: 30;
      transition: transform 0.3s ease;
      width: 240px;
      min-width: 240px;
      max-width: 240px;
      box-sizing: border-box;
      flex-shrink: 0;
    }

    .sidebar-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
    }

    .sidebar-title {
      font-size: 0.95rem;
      font-weight: 800;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .reader-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 8px;
      border-radius: 999px;
      font-size: 0.68rem;
      font-weight: 700;
      background: #EEF2FF;
      color: var(--primary-accent);
      border: 1px solid #C7D2FE;
    }

    .reader-badge-success {
      background: #ECFDF5;
      color: #059669;
      border-color: #A7F3D0;
    }

    .reader-card {
      background: #FFFFFF;
      border: 1px solid var(--card-border);
      border-radius: var(--card-radius);
      padding: 12px 14px;
      box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .reader-card:hover {
      box-shadow: 0 6px 18px rgba(15, 23, 42, 0.07);
    }

    /* Progres card styling */
    .progress-header-row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 6px;
    }

    .progress-label {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--text-secondary);
    }

    .progress-value {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--primary-accent);
      letter-spacing: -0.5px;
    }

    .progress-bar-track {
      height: 8px;
      border-radius: 999px;
      background: #E2E8F0;
      overflow: hidden;
      margin-bottom: 10px;
      position: relative;
    }

    .progress-bar-fill {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, #4361EE 0%, #3B82F6 60%, #8B5CF6 100%);
      border-radius: 999px;
      transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .progress-stats-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      padding-top: 8px;
      border-top: 1px dashed var(--card-border);
      font-size: 0.75rem;
    }

    .stat-item {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .stat-label {
      color: var(--text-muted);
      font-size: 0.68rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }

    .stat-value {
      color: var(--text-primary);
      font-weight: 700;
      font-size: 0.8rem;
    }

    /* Book info card */
    .book-info-card {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 12px 14px;
    }

    .book-cover-thumb {
      width: 42px;
      height: 56px;
      border-radius: 6px;
      background: linear-gradient(135deg, #4361EE, #8B5CF6);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFFFFF;
      font-size: 1.2rem;
      flex-shrink: 0;
      box-shadow: 0 4px 10px rgba(67, 97, 238, 0.25);
      object-fit: cover;
    }

    .book-meta {
      overflow: hidden;
      flex: 1;
      min-width: 0;
    }

    .book-meta-title {
      font-size: 0.84rem;
      font-weight: 700;
      color: var(--text-primary);
      line-height: 1.35;
      margin-bottom: 3px;
      word-break: break-word;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .book-meta-author {
      font-size: 0.72rem;
      color: var(--text-secondary);
      margin-bottom: 6px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .book-tag-row {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
    }

    .level-badge {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      padding: 2px 6px;
      border-radius: 6px;
      font-size: 0.68rem;
      font-weight: 700;
    }

    .level-rendah {
      background: #EFF6FF;
      color: #2563EB;
      border: 1px solid #BFDBFE;
    }

    .level-tinggi {
      background: #FAF5FF;
      color: #7C3AED;
      border: 1px solid #E9D5FF;
    }

    .category-badge {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      padding: 2px 6px;
      border-radius: 6px;
      font-size: 0.68rem;
      font-weight: 600;
      background: #F1F5F9;
      color: var(--text-secondary);
      border: 1px solid #E2E8F0;
    }

    /* Kuis Pemahaman Card */
    .quiz-unlock-card {
      background: linear-gradient(135deg, #F5F3FF 0%, #EEF2FF 100%);
      border: 1.5px solid #C7D2FE;
      border-radius: var(--card-radius);
      padding: 12px;
      box-shadow: 0 8px 20px -4px rgba(67, 97, 238, 0.15);
      animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes popIn {
      from { opacity: 0; transform: translateY(10px) scale(0.96); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .quiz-unlock-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 6px;
    }

    .quiz-unlock-icon {
      font-size: 1.3rem;
      line-height: 1;
    }

    .quiz-unlock-title {
      font-size: 0.86rem;
      font-weight: 800;
      color: #4338CA;
    }

    .quiz-unlock-desc {
      font-size: 0.72rem;
      color: #475569;
      line-height: 1.4;
      margin-bottom: 10px;
    }

    .btn-quiz {
      width: 100%;
      background: linear-gradient(135deg, #4361EE 0%, #7209B7 100%);
      color: #FFFFFF;
      font-weight: 700;
      font-size: 0.8rem;
      padding: 8px 12px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      box-shadow: 0 4px 14px rgba(67, 97, 238, 0.3);
      transition: all 0.2s ease;
    }

    .btn-quiz:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
    }

    /* Tips literasi */
    .tips-card {
      margin-top: auto;
      padding: 10px 12px;
      border-radius: 10px;
      background: #EFF6FF;
      border: 1px solid #BFDBFE;
      color: #1E40AF;
      font-size: 0.74rem;
      line-height: 1.45;
    }

    /* ---------- Main Reader Stage ---------- */
    .reader-main-stage {
      background: var(--reader-bg);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 8px 16px 16px;
      overflow-y: auto;
      min-height: calc(100vh - 74px);
      position: relative;
      min-width: 0;
    }

    .reader-toolbar {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      background: #FFFFFF;
      padding: 4px 12px;
      min-height: 38px;
      border-radius: 9999px;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.07);
      border: 1px solid var(--card-border);
      position: sticky;
      top: 8px;
      z-index: 25;
      margin-bottom: 10px;
      flex-wrap: wrap;
    }

    .reader-toolbar .toolbar-divider {
      width: 1px;
      height: 18px;
      background: var(--card-border);
      margin: 0 2px;
    }

    .reader-toolbar .page-indicator {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text-primary);
      padding: 0 6px;
      min-width: 86px;
      text-align: center;
    }

    .btn-control {
      background: #FFFFFF;
      border: 1px solid var(--card-border);
      border-radius: 999px;
      width: 30px;
      height: 30px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--text-primary);
      transition: all 0.15s ease;
      cursor: pointer;
    }

    .btn-control:hover:not(:disabled) {
      background: #F8FAFC;
      border-color: #CBD5E1;
      color: var(--primary-accent);
      transform: scale(1.05);
    }

    .btn-control:disabled {
      opacity: 0.35;
      cursor: not-allowed;
    }

    .btn-toolbar-action {
      background: #FFFFFF;
      border: 1px solid var(--card-border);
      border-radius: 999px;
      padding: 4px 10px;
      font-size: 0.76rem;
      font-weight: 600;
      color: var(--text-primary);
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
      cursor: pointer;
    }

    .btn-toolbar-action:hover {
      background: #F8FAFC;
      border-color: #CBD5E1;
      color: var(--primary-accent);
    }

    /* Flipbook Stage Area */
    .reader-stage {
      width: 100%;
      flex: 1;
      min-height: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      position: relative;
      padding: 4px 0;
      box-sizing: border-box;
    }

    .flipbook-viewport {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      transform-origin: center center;
      margin: auto;
    }

    .flipbook-container {
      position: relative;
      margin: 0 auto;
      box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.3), 0 0 1px rgba(15, 23, 42, 0.2);
      border-radius: 6px;
      background: #FFFFFF;
      touch-action: pan-y;
      user-select: none;
    }

    /* StPageFlip component strict sizing */
    .stf__parent {
      position: relative;
      display: block;
      box-sizing: border-box;
      margin: 0 auto;
    }

    .stf__wrapper {
      position: relative;
      width: 100% !important;
      height: 100% !important;
      box-sizing: border-box;
      padding-bottom: 0 !important;
    }

    .stf__block {
      position: absolute;
      inset: 0 !important;
      width: 100% !important;
      height: 100% !important;
      box-sizing: border-box;
      perspective: 2000px;
    }

    /* Spine effect in center of spread */
    .flipbook-container::after {
      content: "";
      position: absolute;
      top: 0;
      bottom: 0;
      left: 50%;
      width: 24px;
      transform: translateX(-50%);
      background: linear-gradient(to right, rgba(0,0,0,0.12) 0%, rgba(0,0,0,0.02) 20%, rgba(0,0,0,0) 50%, rgba(0,0,0,0.02) 80%, rgba(0,0,0,0.12) 100%);
      pointer-events: none;
      z-index: 20;
    }

    /* Individual page in flipbook */
    .flipbook-page {
      background: #FFFFFF;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      box-shadow: inset 0 0 35px rgba(0, 0, 0, 0.04);
      width: 100%;
      height: 100%;
    }

    .flipbook-page canvas {
      width: 100% !important;
      height: 100% !important;
      object-fit: fill !important;
      display: block;
    }

    .flipbook-page-skeleton {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 12px;
      background: #FAFAFA;
      color: var(--text-muted);
      font-size: 0.8rem;
    }

    .page-spinner {
      width: 28px;
      height: 28px;
      border: 3px solid #E2E8F0;
      border-top-color: var(--primary-accent);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Floating Side Flip Arrows */
    .stage-nav-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(6px);
      border: 1px solid var(--card-border);
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--text-primary);
      cursor: pointer;
      z-index: 28;
      transition: all 0.2s ease;
      opacity: 0.92;
    }

    .stage-nav-btn:hover:not(:disabled) {
      background: #FFFFFF;
      opacity: 1;
      transform: translateY(-50%) scale(1.1);
      color: var(--primary-accent);
      box-shadow: 0 8px 24px rgba(67, 97, 238, 0.28);
    }

    .stage-nav-btn:disabled {
      opacity: 0.2;
      cursor: not-allowed;
      pointer-events: none;
    }

    .stage-nav-prev {
      left: -56px;
    }

    .stage-nav-next {
      right: -56px;
    }

    /* Loading state */
    #flipbookLoading {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 14px;
      padding: 60px 20px;
      background: #FFFFFF;
      border-radius: 16px;
      border: 1px solid var(--card-border);
      box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
      width: 100%;
      max-width: 560px;
    }

    @keyframes pulseBook {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.18); opacity: 0.85; }
    }

    .loading-book-icon {
      font-size: 3rem;
      animation: pulseBook 1.6s infinite ease-in-out;
    }

    /* ---------- Quiz Modal Overlay ---------- */
    .reader-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      z-index: 100;
      animation: fadeIn 0.2s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .reader-modal-card {
      background: #FFFFFF;
      border-radius: 20px;
      width: 100%;
      max-width: 680px;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.3);
      border: 1px solid var(--card-border);
      overflow: hidden;
      animation: modalSlide 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalSlide {
      from { opacity: 0; transform: translateY(20px) scale(0.97); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .reader-modal-header {
      padding: 18px 24px;
      border-bottom: 1px solid var(--card-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #F8FAFC;
    }

    .reader-modal-body {
      padding: 24px;
      overflow-y: auto;
      flex: 1;
    }

    .quiz-question-box {
      background: #F8FAFC;
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 16px 18px;
      margin-bottom: 16px;
    }

    .quiz-question-title {
      font-size: 0.94rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 12px;
    }

    .quiz-option-label {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border-radius: 8px;
      border: 1px solid #E2E8F0;
      background: #FFFFFF;
      margin-bottom: 8px;
      cursor: pointer;
      font-size: 0.88rem;
      transition: all 0.15s ease;
    }

    .quiz-option-label:hover {
      border-color: #CBD5E1;
      background: #F1F5F9;
    }

    .quiz-option-label input[type="radio"]:checked + span {
      font-weight: 700;
      color: var(--primary-accent);
    }

    .quiz-option-label:has(input[type="radio"]:checked) {
      border-color: #818CF8;
      background: #EEF2FF;
    }

    /* Mobile toggle button */
    .mobile-sidebar-toggle {
      display: none;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 600;
      background: #EEF2FF;
      color: var(--primary-accent);
      border: 1px solid #C7D2FE;
    }

    /* ---------- Responsive Media Queries ---------- */
    @media (max-width: 1023.98px) {
      .reader-page-wrapper {
        grid-template-columns: 1fr;
      }

      .mobile-sidebar-toggle {
        display: inline-flex;
      }

      .reader-sidebar {
        width: 100%;
        height: auto;
        position: static;
        border-right: none;
        border-bottom: 1px solid var(--card-border);
        padding: 16px;
        gap: 12px;
      }

      .reader-sidebar.collapsed-mobile {
        display: none;
      }

      .stage-nav-btn {
        display: none; /* Mobile users use touch/swipe or toolbar buttons */
      }

      .reader-main-stage {
        padding: 8px 10px 24px;
      }

      .reader-stage {
        padding: 4px 6px;
      }

      .flipbook-container::after {
        display: none; /* In 1-page portrait mode, no spine crease in center */
      }
    }

    /* Desktop Guard (>= 1024px) */
    @media (min-width: 1024px) {
      .mobile-sidebar-toggle {
        display: none !important;
      }
      .reader-sidebar {
        display: flex !important;
      }
    }

    @media (max-width: 640px) {
      .nav-inner {
        padding: 10px 14px;
        flex-wrap: wrap;
        gap: 8px;
        height: auto;
        min-height: 56px;
      }
      .brand #bookTitle {
        max-width: 140px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.92rem;
      }
      .nav-links {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
      }
      .nav-links .btn-sm {
        padding: 5px 9px;
        font-size: 0.78rem;
      }
      .mobile-sidebar-toggle {
        padding: 5px 10px;
        font-size: 0.76rem;
      }
      .reader-toolbar {
        padding: 8px 10px;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
      }
      .reader-toolbar .btn-control {
        width: 38px;
        height: 38px;
        font-size: 1.1rem;
      }
      .flipbook-container {
        padding: 4px;
        max-width: 100vw;
      }
    }

    @media (max-width: 380px) {
      .brand #bookTitle {
        display: none;
      }
      .nav-links a[href="dashboard-siswa.html"] {
        display: none;
      }
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="nav">
    <div class="container nav-inner">
      <div style="display:flex;align-items:center;gap:12px;overflow:hidden;">
        <a class="brand" href="/" style="text-decoration:none;display:flex;align-items:center;gap:10px;flex-shrink:0;">
          <span class="logo-mark" aria-hidden="true" style="width:34px;height:34px;display:inline-block;">
            <svg viewBox="0 0 200 170" role="img" style="width:100%;height:100%;">
              <path d="M36 60h18l10 56-19 4z" fill="#4361EE"/>
              <path d="M164 60h-18l-10 56 19 4z" fill="#4361EE"/>
              <path d="M44 55c23-4 44-3 61 12 17-15 38-16 61-12l-12 48c-18-4-34-3-49 9-15-12-31-13-49-9z" fill="#EEF1FF" stroke="#C7D2FE" stroke-width="7" stroke-linejoin="round"/>
              <path d="M100 95l34-27-22 37-12 16-12-16-22-37z" fill="#F59E0B"/>
            </svg>
          </span>
          <strong style="color:var(--text-primary);font-size:1.05rem;" id="bookTitle">Membaca Buku</strong>
        </a>
      </div>

      <div class="nav-links">
        <button type="button" class="mobile-sidebar-toggle" onclick="toggleMobileSidebar()" id="sidebarToggleBtn">
          📊 Detail Progres
        </button>
        <a href="katalog.html" class="btn btn-outline btn-sm">← Katalog</a>
        <a href="dashboard-siswa.html" class="btn btn-outline btn-sm">Dashboard Siswa</a>
        <a href="#" onclick="logout()" class="btn btn-outline btn-sm" style="color:#EF4444;border-color:#FECACA;">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Reader Page Wrapper -->
  <div class="reader-page-wrapper">
    <!-- Reader Progress Sidebar (Left) -->
    <aside class="reader-sidebar" id="readerSidebar">
      <div class="sidebar-header">
        <h3 class="sidebar-title">📖 Progres Membaca</h3>
        <span id="readerModeBadge" class="reader-badge">📚 Flipbook 3D</span>
      </div>

      <!-- Main Progress Card -->
      <div class="reader-card">
        <div class="progress-header-row">
          <span class="progress-label">Tingkat Selesai:</span>
          <span id="progressText" class="progress-value">0%</span>
        </div>
        <div class="progress-bar-track">
          <div id="progressBar" class="progress-bar-fill"></div>
        </div>
        
        <div class="progress-stats-grid">
          <div class="stat-item">
            <span class="stat-label">Halaman Dibaca</span>
            <span id="pagesReadCount" class="stat-value">0 dari 0 Halaman</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Posisi Sekarang</span>
            <span id="pageInfo" class="stat-value">Halaman 1</span>
          </div>
          <div class="stat-item" style="grid-column: span 2; padding-top:4px;">
            <span class="stat-label">Durasi Membaca</span>
            <span id="readingDuration" class="stat-value" style="color:#2563EB;">⏱️ 0 Menit</span>
          </div>
        </div>
      </div>

      <!-- Book Info Card -->
      <div class="reader-card book-info-card">
        <div class="book-cover-thumb" id="sidebarBookCover">📚</div>
        <div class="book-meta">
          <div id="sidebarBookTitle" class="book-meta-title">Judul Buku</div>
          <div id="sidebarBookAuthor" class="book-meta-author">Penulis Buku</div>
          <div class="book-tag-row">
            <span id="sidebarBookLevel" class="level-badge level-rendah">Tingkat: Rendah</span>
            <span id="sidebarBookCategory" class="category-badge">Umum</span>
          </div>
        </div>
      </div>

      <!-- Quiz Unlocked Card (Active when reading completed) -->
      <div id="quizUnlockedCard" class="quiz-unlock-card hidden">
        <div class="quiz-unlock-header">
          <span class="quiz-unlock-icon">🏆</span>
          <div>
            <div class="quiz-unlock-title">Buku Selesai Dibaca!</div>
            <div style="font-size:0.72rem;color:#6366F1;font-weight:700;">Progres 100% Tercapai</div>
          </div>
        </div>
        <p class="quiz-unlock-desc">
          Kamu telah membaca seluruh lembar buku. Kerjakan Kuis Pemahaman untuk menguji daya serap materi!
        </p>
        <button type="button" class="btn btn-quiz" onclick="openBookQuiz()">
          ✍️ Buka Kuis Pemahaman
        </button>
      </div>

      <!-- Tips Literasi -->
      <div class="tips-card" id="literacyTipsCard">
        💡 <strong>Tips Literasi:</strong> Baca dan cermati setiap lembar buku. Setelah seluruh halaman selesai dibaca (100%), Kuis Pemahaman akan otomatis terbuka!
      </div>
    </aside>

    <!-- Main Flipbook Stage (Right) -->
    <section class="reader-main-stage" id="readerMainStage">
      <!-- Toolbar Controls -->
      <div class="reader-toolbar">
        <button type="button" id="prev" class="btn-control" title="Halaman sebelumnya (Panah Kiri)">‹</button>
        <span class="page-indicator" id="pageNavInfo">Memuat...</span>
        <button type="button" id="next" class="btn-control" title="Halaman berikutnya (Panah Kanan)">›</button>

        <div class="toolbar-divider"></div>

        <button type="button" id="zoomOut" class="btn-control" title="Perkecil">−</button>
        <span id="zoomPercent" style="font-size:0.84rem;font-weight:700;min-width:48px;text-align:center;">100%</span>
        <button type="button" id="zoomIn" class="btn-control" title="Perbesar">+</button>
        <button type="button" id="fitWidthBtn" class="btn-toolbar-action" onclick="resetZoom()" title="Reset pembesaran">↺ 100%</button>

        <div class="toolbar-divider"></div>

        <button type="button" id="fullscreenBtn" class="btn-toolbar-action" onclick="toggleFullscreen()" title="Layar penuh">
          ⛶ Layar Penuh
        </button>
      </div>

      <!-- Interactive Flipbook Stage -->
      <div id="readerStage" class="reader-stage">
        <!-- Loading State Placeholder -->
        <div id="flipbookLoading">
          <div class="loading-book-icon">📖</div>
          <div style="font-size:1.15rem;font-weight:800;color:var(--text-primary);text-align:center;">
            Menyiapkan Buku Interaktif...
          </div>
          <p style="font-size:0.85rem;color:var(--text-secondary);margin:0;text-align:center;max-width:420px;">
            Memproses lembaran PDF resolusi tinggi ke dalam animasi membalik halaman fisik PantauBaca.
          </p>
          <div class="page-spinner"></div>
        </div>

        <!-- Flipbook Scalable Viewport with Flanking Side Arrows -->
        <div id="flipbookViewport" class="flipbook-viewport hidden">
          <button type="button" id="stagePrevBtn" class="stage-nav-btn stage-nav-prev" onclick="handleFlipPrev()" title="Halaman Sebelumnya">‹</button>
          <div id="flipbookContainer" class="flipbook-container"></div>
          <button type="button" id="stageNextBtn" class="stage-nav-btn stage-nav-next" onclick="handleFlipNext()" title="Halaman Berikutnya">›</button>
        </div>
      </div>
    </section>
  </div>

  <!-- Modal Kuis Pemahaman -->
  <div id="quizModal" class="reader-modal-overlay hidden" onclick="if(event.target === this) closeQuizModal()">
    <div class="reader-modal-card">
      <div class="reader-modal-header">
        <div style="display:flex;align-items:center;gap:10px;">
          <span style="font-size:1.4rem;">📝</span>
          <div>
            <h3 id="modalQuizTitle" style="font-size:1.1rem;font-weight:800;color:var(--text-primary);margin:0;">Kuis Pemahaman Buku</h3>
            <p id="modalQuizDesc" style="font-size:0.78rem;color:var(--text-secondary);margin:2px 0 0 0;">Jawab pertanyaan berikut dengan teliti.</p>
          </div>
        </div>
        <button type="button" class="btn-control" onclick="closeQuizModal()" style="border:none;font-size:1.2rem;">✕</button>
      </div>

      <div class="reader-modal-body" id="quizModalBody">
        <form id="quizForm" onsubmit="handleQuizSubmit(event)">
          <div id="quizQuestionsContainer"></div>
          <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
            <button type="button" class="btn btn-outline" onclick="closeQuizModal()">Batal</button>
            <button type="submit" class="btn btn-primary" id="quizSubmitBtn">🚀 Kirim Jawaban Kuis</button>
          </div>
        </form>
        <div id="quizResultPanel" class="hidden" style="text-align:center;padding:24px 10px;"></div>
      </div>
    </div>
  </div>

  <!-- Local and fallback library scripts -->
  <script src="{{ asset('pantaubaca/js/pdf.min.js') }}"></script>
  <script src="{{ asset('pantaubaca/js/page-flip.browser.js') }}"></script>
  <script>
    if (typeof pdfjsLib === 'undefined') {
      document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"><\/script>');
    }
  </script>
  <script>
    if (typeof St === 'undefined') {
      document.write('<script src="https://cdn.jsdelivr.net/npm/page-flip/dist/js/page-flip.browser.js"><\/script>');
    }
  </script>
  <script src="{{ asset('pantaubaca/js/app.js') }}?v={{ file_exists(public_path('pantaubaca/js/app.js')) ? filemtime(public_path('pantaubaca/js/app.js')) : time() }}"></script>
  <script src="{{ asset('pantaubaca/js/reader.js') }}?v={{ file_exists(public_path('pantaubaca/js/reader.js')) ? filemtime(public_path('pantaubaca/js/reader.js')) : time() }}"></script>
</body>
</html>
