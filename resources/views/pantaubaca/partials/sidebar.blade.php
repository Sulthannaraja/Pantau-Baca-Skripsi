@props([
    'role' => 'guru',
    'active' => 'overviewSection'
])

@php
    $roleName = match($role) {
        'guru' => 'Dashboard Guru',
        'siswa' => 'Dashboard Siswa',
        'admin' => 'Dashboard Admin',
        default => 'Dashboard'
    };

    $menus = match($role) {
        'guru' => [
            [
                'id' => 'overviewSection',
                'label' => 'Beranda Guru',
                'icon' => 'home',
                'href' => '#overviewSection',
                'feature' => 'overviewSection',
                'onclick' => "switchFeature('overviewSection', event)"
            ],
            [
                'id' => 'manageKelasSection',
                'label' => 'Kelola Kelas',
                'icon' => 'users',
                'href' => '#manageKelasSection',
                'feature' => 'manageKelasSection',
                'onclick' => "switchFeature('manageKelasSection', event)"
            ],
            [
                'id' => 'bukuSection',
                'label' => 'Kelola & Input Buku',
                'icon' => 'book-open',
                'href' => '#bukuSection',
                'feature' => 'bukuSection',
                'onclick' => "switchFeature('bukuSection', event)"
            ],
            [
                'id' => 'penilaianResumeSection',
                'label' => 'Penilaian Resume Siswa',
                'icon' => 'file-check',
                'href' => '#penilaianResumeSection',
                'feature' => 'penilaianResumeSection',
                'onclick' => "switchFeature('penilaianResumeSection', event)"
            ],
            [
                'id' => 'surveyResultSection',
                'label' => 'Hasil Angket Literasi',
                'icon' => 'chart-column-increasing',
                'href' => '#surveyResultSection',
                'feature' => 'surveyResultSection',
                'onclick' => "switchFeature('surveyResultSection', event)"
            ],
            [
                'id' => 'activitySection',
                'label' => 'Aktivitas Siswa',
                'icon' => 'activity',
                'href' => '#activitySection',
                'feature' => 'activitySection',
                'onclick' => "switchFeature('activitySection', event)"
            ],
            [
                'id' => 'quizSection',
                'label' => 'Kuis Pemahaman',
                'icon' => 'notebook-pen',
                'href' => '#quizSection',
                'feature' => 'quizSection',
                'onclick' => "switchFeature('quizSection', event)"
            ],
            [
                'id' => 'quizGradesSection',
                'label' => 'Rekap Nilai Kuis',
                'icon' => 'chart-no-axes-column',
                'href' => '#quizGradesSection',
                'feature' => 'quizGradesSection',
                'onclick' => "switchFeature('quizGradesSection', event)"
            ],
        ],
        'siswa' => [
            [
                'id' => 'overviewSection',
                'label' => 'Dashboard',
                'icon' => 'home',
                'href' => '#overviewSection',
                'feature' => 'overviewSection',
                'onclick' => "switchFeature('overviewSection', event)"
            ],
            [
                'id' => 'kelasSection',
                'label' => 'Kelas Saya',
                'icon' => 'users',
                'href' => '#kelasSection',
                'feature' => 'kelasSection',
                'onclick' => "switchFeature('kelasSection', event)"
            ],
            [
                'id' => 'angket',
                'label' => 'Angket Literasi Digital',
                'icon' => 'file-question',
                'href' => '#angket',
                'onclick' => "openSurveyModal(); return false;"
            ],
            [
                'id' => 'latihanSection',
                'label' => 'Latihan Membaca',
                'icon' => 'book-open',
                'href' => '#latihanSection',
                'feature' => 'latihanSection',
                'onclick' => "switchFeature('latihanSection', event)"
            ],
            [
                'id' => 'resumeSection',
                'label' => 'Resume & Nilai Saya',
                'icon' => 'file-check',
                'href' => '#resumeSection',
                'feature' => 'resumeSection',
                'onclick' => "switchFeature('resumeSection', event)"
            ],
            [
                'id' => 'katalog',
                'label' => 'Katalog Buku',
                'icon' => 'library',
                'href' => 'katalog.html'
            ],
            [
                'id' => 'riwayatSection',
                'label' => 'Riwayat Membaca',
                'icon' => 'history',
                'href' => '#riwayatSection',
                'feature' => 'riwayatSection',
                'onclick' => "switchFeature('riwayatSection', event)"
            ],
            [
                'id' => 'progressSection',
                'label' => 'Target Literasi',
                'icon' => 'target',
                'href' => '#progressSection',
                'feature' => 'progressSection',
                'onclick' => "switchFeature('progressSection', event)"
            ],
            [
                'id' => 'quizSection',
                'label' => 'Kuis Pemahaman',
                'icon' => 'notebook-pen',
                'href' => '#quizSection',
                'feature' => 'quizSection',
                'onclick' => "switchFeature('quizSection', event)"
            ],
        ],
        'admin' => [
            [
                'id' => 'overviewSection',
                'label' => 'Admin Panel',
                'icon' => 'shield',
                'href' => '#overviewSection',
                'feature' => 'overviewSection',
                'onclick' => "switchFeature('overviewSection', event)"
            ],
            [
                'id' => 'katalog',
                'label' => 'Katalog Buku',
                'icon' => 'library',
                'href' => 'katalog.html'
            ],
            [
                'id' => 'uploadSection',
                'label' => 'Upload Buku PDF',
                'icon' => 'upload',
                'href' => '#uploadSection',
                'feature' => 'uploadSection',
                'onclick' => "switchFeature('uploadSection', event)"
            ],
            [
                'id' => 'bukuSection',
                'label' => 'Manajemen Buku',
                'icon' => 'book-open',
                'href' => '#bukuSection',
                'feature' => 'bukuSection',
                'onclick' => "switchFeature('bukuSection', event)"
            ],
            [
                'id' => 'userSection',
                'label' => 'Manajemen User',
                'icon' => 'users',
                'href' => '#userSection',
                'feature' => 'userSection',
                'onclick' => "switchFeature('userSection', event)"
            ],
            [
                'id' => 'viewGuru',
                'label' => 'View Guru',
                'icon' => 'graduation-cap',
                'href' => 'dashboard-guru.html'
            ],
            [
                'id' => 'viewSiswa',
                'label' => 'View Siswa',
                'icon' => 'user',
                'href' => 'dashboard-siswa.html'
            ],
        ],
        default => []
    };
@endphp

<aside class="sidebar" id="dashboardSidebar">
  <div class="sidebar-brand">
    <a href="/" class="brand-link" style="display:flex;align-items:center;gap:9px;text-decoration:none;color:inherit;min-width:0;flex:1;">
      <span class="logo-mark" aria-hidden="true">
        <svg viewBox="0 0 200 170" role="img">
          <path d="M36 60h18l10 56-19 4z" fill="#4361EE"/>
          <path d="M164 60h-18l-10 56 19 4z" fill="#4361EE"/>
          <path d="M44 55c23-4 44-3 61 12 17-15 38-16 61-12l-12 48c-18-4-34-3-49 9-15-12-31-13-49-9z" fill="#EEF1FF" stroke="#C7D2FE" stroke-width="7" stroke-linejoin="round"/>
          <path d="M100 95l34-27-22 37-12 16-12-16-22-37z" fill="#F59E0B"/>
          <path d="M84 32c10-8 22-12 36-12 15 0 28 5 39 14" fill="none" stroke="#4361EE" stroke-width="8" stroke-linecap="round"/>
          <path d="M94 45c7-5 15-8 25-8 10 0 19 3 26 9" fill="none" stroke="#4361EE" stroke-width="7" stroke-linecap="round"/>
          <circle cx="119" cy="58" r="6" fill="#4361EE"/>
        </svg>
      </span>
      <div class="sidebar-brand-text">
        <strong>Pantau<span>Baca</span></strong>
        <small>{{ $roleName }}</small>
      </div>
    </a>
    <button type="button" class="sidebar-close-btn" aria-label="Tutup menu navigasi" onclick="closeMobileSidebar()">✕</button>
  </div>

  <nav class="sidebar-nav">
    @foreach($menus as $menu)
      @php
        $isItemActive = ($menu['id'] === $active);
      @endphp
      <a href="{{ $menu['href'] }}"
         @if(!empty($menu['feature'])) data-feature="{{ $menu['feature'] }}" @endif
         class="{{ $isItemActive ? 'active' : '' }}"
         @if(!empty($menu['onclick'])) onclick="{{ $menu['onclick'] }}" @endif>
        <span class="nav-icon">
          @include('pantaubaca.partials.icon', ['name' => $menu['icon'], 'size' => 17])
        </span>
        <span class="nav-label">{{ $menu['label'] }}</span>
      </a>
    @endforeach
  </nav>

  <div class="sidebar-footer">
    <div class="motivation-card" style="background:transparent;padding:0;margin:0;">
      @if($role === 'guru')
        <p>"Membimbing literasi generasi bangsa dengan dedikasi dan keteladanan."</p>
      @elseif($role === 'siswa')
        <p>"Membaca adalah jendela dunia, teruslah belajar dan bertumbuh!"</p>
      @elseif($role === 'admin')
        <p style="font-size:0.76rem;"><strong style="color:var(--primary);font-style:normal;">Hak Akses Administrator</strong><br>Kelola data buku, pengguna, dan pengaturan sistem.</p>
      @endif
    </div>
  </div>
</aside>
