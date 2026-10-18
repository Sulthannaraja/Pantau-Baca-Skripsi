<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Masuk — PantauBaca</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('pantaubaca/css/style.css') }}?v={{ file_exists(public_path('pantaubaca/css/style.css')) ? filemtime(public_path('pantaubaca/css/style.css')) : time() }}" />
  <style>
    body { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; margin: 0; background: #F4F6FC; }
    
    .auth-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: 
        radial-gradient(circle at 10% 20%, rgba(67, 97, 238, 0.12) 0%, transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(114, 9, 183, 0.08) 0%, transparent 40%),
        linear-gradient(135deg, #F8FAFC 0%, #EEF2FF 100%);
      padding: 32px 20px;
    }
    .auth-container {
      display: flex;
      max-width: 960px;
      width: 100%;
      background: #FFFFFF;
      border-radius: 24px;
      box-shadow: 0 24px 60px -15px rgba(67, 97, 238, 0.18), 0 0 1px 1px rgba(67, 97, 238, 0.06);
      overflow: hidden;
      border: 1.5px solid #E2E8F0;
    }
    .auth-sidebar-banner {
      flex: 1;
      background: linear-gradient(145deg, #4361EE 0%, #3B82F6 50%, #7209B7 100%);
      color: #FFFFFF;
      padding: 48px 40px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
    }
    .auth-sidebar-banner::after {
      content: '';
      position: absolute;
      width: 280px;
      height: 280px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.08);
      bottom: -60px;
      right: -60px;
      pointer-events: none;
    }
    .auth-form-side {
      flex: 1.15;
      padding: 48px 44px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: #FFFFFF;
    }
    .login-input {
      width: 100%;
      box-sizing: border-box;
      border: 1.5px solid #CBD5E1;
      border-radius: 12px;
      padding: 13px 16px;
      font-size: 0.95rem;
      font-family: inherit;
      background: #F8FAFC;
      color: #0F172A;
      transition: all 0.2s ease;
      outline: none;
    }
    .login-input:focus {
      background: #FFFFFF;
      border-color: #4361EE;
      box-shadow: 0 0 0 3.5px rgba(67, 97, 238, 0.15);
    }
    .btn-login-submit {
      width: 100%;
      padding: 14px 20px;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 700;
      color: #FFFFFF;
      background: linear-gradient(135deg, #4361EE 0%, #3B82F6 100%);
      border: none;
      cursor: pointer;
      box-shadow: 0 6px 18px rgba(67, 97, 238, 0.35);
      transition: all 0.25s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .btn-login-submit:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(67, 97, 238, 0.45);
      background: linear-gradient(135deg, #3751D4 0%, #2563EB 100%);
    }
    .btn-login-submit:active {
      transform: translateY(0);
    }
    .btn-login-submit:disabled {
      opacity: 0.65;
      cursor: not-allowed;
      transform: none;
    }
    @media (max-width: 768px) {
      .auth-wrapper { padding: 18px 12px; }
      .auth-container { flex-direction: column; border-radius: 20px; }
      .auth-sidebar-banner { padding: 28px 22px; }
      .auth-form-side { padding: 28px 22px; }
      .login-input { font-size: 16px !important; min-height: 48px; }
      .btn-login-submit { min-height: 48px; }
      .auth-quote-box { display: none; }
    }
    @media (max-width: 480px) {
      .auth-sidebar-banner h3 { font-size: 1.3rem !important; }
      .auth-sidebar-banner p { font-size: 0.86rem !important; }
      .auth-form-side h2 { font-size: 1.4rem !important; }
    }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-container">
      
      <!-- Left Banner -->
      <div class="auth-sidebar-banner">
        <div>
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <div style="width:44px;height:44px;border-radius:14px;background:rgba(255,255,255,0.2);backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center;padding:6px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
              <svg viewBox="0 0 200 170" role="img" style="width:100%;height:100%;">
                <path d="M36 60h18l10 56-19 4z" fill="#FFFFFF"/>
                <path d="M164 60h-18l-10 56 19 4z" fill="#FFFFFF"/>
                <path d="M44 55c23-4 44-3 61 12 17-15 38-16 61-12l-12 48c-18-4-34-3-49 9-15-12-31-13-49-9z" fill="#EEF1FF" stroke="#FFFFFF" stroke-width="7" stroke-linejoin="round"/>
                <path d="M100 95l34-27-22 37-12 16-12-16-22-37z" fill="#F59E0B"/>
              </svg>
            </div>
            <h2 style="font-size:1.45rem;font-weight:800;color:#FFFFFF;margin:0;">Pantau<span style="color:#FDE047;">Baca</span></h2>
          </div>
          <h3 style="font-size:1.55rem;font-weight:800;line-height:1.25;margin-bottom:12px;">Selamat Datang Kembali! 👋</h3>
          <p style="font-size:0.92rem;line-height:1.6;opacity:0.92;margin:0;">Pantau kemajuan membacamu, tulis resume buku di katalog, dapatkan nilai guru, dan raih tingkat literasi terbaikmu.</p>
        </div>

        <div class="auth-quote-box" style="background:rgba(255,255,255,0.14);backdrop-filter:blur(10px);border-radius:16px;padding:16px 18px;border:1px solid rgba(255,255,255,0.25);margin-top:24px;">
          <p style="font-size:0.86rem;font-style:italic;margin:0;opacity:0.95;line-height:1.5;">
            "Buku adalah teman yang paling setia dan guru yang paling bijaksana."
          </p>
        </div>
      </div>

      <!-- Right Form -->
      <div class="auth-form-side">
        <div style="margin-bottom:28px;">
          <h2 style="font-size:1.7rem;font-weight:800;color:#0F172A;margin-bottom:6px;">Masuk ke Akun</h2>
          <p style="font-size:0.92rem;color:#64748B;">Masukkan informasi akun Anda untuk melanjutkan ke dashboard.</p>
        </div>

        <!-- Alert Container -->
        <div id="loginAlert" style="display:none;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:0.88rem;font-weight:600;"></div>

        <form onsubmit="return false;" style="display:flex;flex-direction:column;gap:20px;">
          <div class="field-group">
            <label style="font-size:0.86rem;font-weight:700;margin-bottom:8px;display:block;color:#1E293B;">Email atau Username</label>
            <input id="email" class="login-input" placeholder="admin / guru@pantaubaca.com / siswa@pantaubaca.com" autocomplete="username" />
          </div>

          <div class="field-group">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <label style="font-size:0.86rem;font-weight:700;display:block;color:#1E293B;">Password</label>
            </div>
            <input id="password" class="login-input" type="password" placeholder="••••••••" autocomplete="current-password" />
          </div>

          <button type="button" id="loginBtn" class="btn-login-submit" style="margin-top:6px;">
            <span>Masuk Sekarang</span>
          </button>
        </form>

        <p style="text-align:center;font-size:0.9rem;color:#64748B;margin-top:24px;">
          Belum punya akun? <a href="register.html" style="color:#4361EE;text-decoration:none;font-weight:700;">Daftar akun baru</a>
        </p>

        <div style="text-align:center;margin-top:14px;">
          <a href="/" style="font-size:0.86rem;color:#94A3B8;text-decoration:none;font-weight:600;">← Kembali ke Halaman Utama</a>
        </div>
      </div>

    </div>
  </div>

  <script src="{{ asset('pantaubaca/js/app.js') }}"></script>
  <script src="{{ asset('pantaubaca/js/login.js') }}"></script>
</body>
</html>
