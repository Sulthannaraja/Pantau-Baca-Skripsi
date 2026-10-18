if (!requireAuth(["SISWA", "ADMIN"])) {
  throw new Error("Unauthorized");
}

const auth = getAuth();
const quizList = document.getElementById("availableQuizList");
const quizAttemptPanel = document.getElementById("quizAttemptPanel");
let availableQuizzes = [];
let studentReadingData = [];
let studentBooksList = [];
let currentStudentCategory = "DASAR"; // "DASAR" (Rendah) or "LANJUTAN" (Tinggi)

// 10 Butir Instrumen Standar Literasi Digital (Skala Likert 1-4)
const digitalLiteracyQuestions = [
  "Saya mampu mencari dan menemukan buku atau referensi bacaan digital yang relevan di internet/platform.",
  "Saya dapat membedakan informasi yang valid/terpercaya dengan informasi yang meragukan saat membaca bahan digital.",
  "Saya terbiasa membaca buku digital secara mandiri dan fokus tanpa mudah terdistraksi media sosial.",
  "Saya memahami gagasan utama dan pesan penting dari teks buku digital yang saya baca.",
  "Saya mampu membuat rangkuman atau intisari poin penting dari bahan bacaan digital yang telah saya selesaikan.",
  "Saya dapat memanfaatkan fitur navigasi reader (pembatas halaman, zoom, daftar isi) secara efektif saat membaca.",
  "Saya mampu menghubungkan konsep materi buku yang dibaca dengan situasi kehidupan nyata atau pelajaran sekolah.",
  "Saya mampu mengevaluasi dan menarik kesimpulan secara kritis dari berbagai sudut pandang isi bacaan digital.",
  "Saya menerapkan etika literasi digital yang baik dan menghormati hak cipta atas buku yang saya baca.",
  "Saya memiliki motivasi tinggi untuk terus menambah wawasan dan meningkatkan intensitas membaca digital setiap minggu."
];

const likertOptions = [
  { value: 1, label: "Sangat Tidak Setuju" },
  { value: 2, label: "Tidak Setuju" },
  { value: 3, label: "Setuju" },
  { value: 4, label: "Sangat Setuju" }
];

// Set profile name
const profileNameEl = document.getElementById("profileName");
if (profileNameEl && auth.name) {
  profileNameEl.textContent = auth.name;
  const avatar = document.querySelector(".header-profile-avatar");
  if (avatar) avatar.textContent = auth.name.charAt(0).toUpperCase();
  const greetingH1 = document.querySelector(".dashboard-greeting h1");
  if (greetingH1) greetingH1.textContent = `Halo, ${auth.name}! 👋`;
}

(async function () {
  renderSurveyQuestions();

  let data = [];
  try {
    data = await apiGet("/reading/report");
    studentBooksList = await apiGet("/books");
  } catch (e) {
    if (!isNetworkError(e)) throw e;
    data = demoReadingReport();
    studentBooksList = demoBooks();
    showInfo("Backend belum terhubung. Menampilkan data demo sementara.");
  }

  studentReadingData = data;

  const totalDuration = data.reduce((acc, i) => acc + i.duration, 0);
  const uniqueBooks = new Set(data.map(i => i.bookId));
  const bookCount = uniqueBooks.size;

  document.getElementById("totalDuration").textContent = `${totalDuration} menit`;
  document.getElementById("totalBooks").textContent = `${bookCount} buku`;
  document.getElementById("targetInfo").textContent = `${bookCount}/10`;

  // Update progress ring
  const progressPercent = Math.min(Math.round((bookCount / 10) * 100), 100);
  const ring = document.getElementById("progressRing");
  const circumference = 314.16;
  if (ring) {
    ring.style.strokeDashoffset = circumference - (circumference * progressPercent / 100);
  }
  const progressPercentEl = document.getElementById("progressPercent");
  if (progressPercentEl) progressPercentEl.textContent = `${progressPercent}%`;

  // Weekly target text
  const weeklyText = document.getElementById("weeklyTargetText");
  if (weeklyText) {
    const remaining = Math.max(10 - bookCount, 0);
    weeklyText.innerHTML = `<strong>${bookCount} dari 10 buku</strong><br>` +
      (remaining > 0 ? `Masih ${remaining} buku lagi untuk mencapai targetmu!` : `Target tercapai! Luar biasa! 🎉`);
  }

  // Check existing survey result first or evaluate reading level
  await loadExistingSurveyResult(data);

  // Load student submitted resumes and grades
  await loadMyResumesList();

  document.getElementById("historyBody").innerHTML = data.length ? data.map(i => `
    <tr>
      <td><strong>${escapeHtml(i.bookTitle)}</strong></td>
      <td>${i.duration} menit</td>
      <td>
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="progress-wrap" style="width:80px;height:6px;">
            <div class="progress-bar" style="width:${Math.min(i.progress, 100)}%"></div>
          </div>
          <span style="font-size:0.84rem;font-weight:700;">${i.progress}%</span>
        </div>
      </td>
      <td>Halaman ${i.lastPage}</td>
      <td>${new Date(i.updatedAt).toLocaleString()}</td>
      <td>
        <a href="reader.html?id=${i.bookId}" class="btn btn-outline btn-sm">📖 Baca</a>
      </td>
    </tr>
  `).join("") : '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:24px;">Belum ada riwayat membaca.</td></tr>';

  // Update activity feed
  updateActivityFeed(data);

  await loadAvailableQuizzes(data);
})();

/* ---------- Render Angket Literasi Digital 10 Soal ---------- */
function renderSurveyQuestions() {
  const container = document.getElementById("surveyQuestionsContainer");
  if (!container) return;

  container.innerHTML = digitalLiteracyQuestions.map((q, index) => `
    <div class="likert-card">
      <div class="likert-question-title">
        <span class="likert-question-index">${index + 1}.</span>
        <span>${escapeHtml(q)}</span>
      </div>
      <div class="likert-options-simple">
        ${likertOptions.map(opt => `
          <label class="likert-simple-option" id="label-q${index}-v${opt.value}" onclick="selectLikertOption(${index}, ${opt.value})">
            <input type="radio" name="survey_q_${index}" value="${opt.value}" required />
            <span class="likert-option-text">${escapeHtml(opt.label)}</span>
          </label>
        `).join("")}
      </div>
    </div>
  `).join("");
}

window.selectLikertOption = function (qIndex, val) {
  likertOptions.forEach(opt => {
    const el = document.getElementById(`label-q${qIndex}-v${opt.value}`);
    if (el) el.classList.remove("selected");
  });
  const chosen = document.getElementById(`label-q${qIndex}-v${val}`);
  if (chosen) {
    chosen.classList.add("selected");
    const radio = chosen.querySelector('input[type="radio"]');
    if (radio && !radio.checked) {
      radio.checked = true;
    }
  }
};

window.openSurveyModal = function () {
  const modal = document.getElementById("surveyModal");
  if (modal) {
    modal.classList.add("active");
    document.body.style.overflow = "hidden";
  }
};

window.closeSurveyModal = function () {
  const modal = document.getElementById("surveyModal");
  if (modal) {
    modal.classList.remove("active");
    document.body.style.overflow = "";
  }
};

window.handleSurveySubmit = async function (event) {
  event.preventDefault();
  const form = document.getElementById("literacySurveyForm");
  const formData = new FormData(form);

  const responses = [];
  for (let i = 0; i < 10; i++) {
    const val = formData.get(`survey_q_${i}`);
    if (!val) {
      alert(`Pernyataan nomor ${i + 1} belum dijawab.`);
      return;
    }
    responses.push(Number(val));
  }

  const totalScore = responses.reduce((a, b) => a + b, 0);
  const isHigh = totalScore >= 28; // Standard: 28/40 (70%) => Tinggi

  try {
    const result = await apiPost("/literacy-survey", {
      responses,
      user_id: auth.userId
    });

    applySurveyLevel(result.level, result.total_score);
    closeSurveyModal();
    if (window.showToast) {
      window.showToast(`Angket berhasil dievaluasi! Skor: ${result.total_score}/40 (${result.level_label})`, "success");
    } else {
      alert(`Angket berhasil dievaluasi! Skor: ${result.total_score}/40 (${result.level_label})`);
    }
  } catch (e) {
    // Demo fallback storage
    localStorage.setItem("pantaubaca_demo_survey", JSON.stringify({
      total_score: totalScore,
      level: isHigh ? "TINGGI" : "RENDAH",
      responses
    }));

    applySurveyLevel(isHigh ? "TINGGI" : "RENDAH", totalScore);
    closeSurveyModal();
    if (window.showToast) {
      window.showToast(`[Demo] Skor Angket: ${totalScore}/40 (${isHigh ? 'Tingkat Tinggi' : 'Tingkat Rendah'})`, "success");
    } else {
      alert(`[Demo] Skor Angket: ${totalScore}/40 (${isHigh ? 'Tingkat Tinggi' : 'Tingkat Rendah'})`);
    }
  }
};

async function loadExistingSurveyResult(readingData) {
  try {
    const res = await apiGet("/literacy-survey/my-result");
    if (res && res.has_submitted) {
      applySurveyLevel(res.level, res.total_score);
      return;
    }
  } catch { }

  // Check demo survey in localStorage
  try {
    const demoSurvey = JSON.parse(localStorage.getItem("pantaubaca_demo_survey") || "null");
    if (demoSurvey) {
      applySurveyLevel(demoSurvey.level, demoSurvey.total_score);
      return;
    }
  } catch { }

  // If no survey submitted yet, fallback to reading logs
  await updateReadingLevel(readingData);
}

function applySurveyLevel(level, score) {
  const levelBadge = document.getElementById("levelBadge");
  const levelIcon = document.getElementById("levelIcon");
  const levelDesc = document.getElementById("levelDesc");
  const currentLevelName = document.getElementById("currentLevelName");
  const surveyScorePill = document.getElementById("surveyScorePill");
  const levelCard = document.getElementById("levelCard");

  // Adaptive banner elements
  const adaptiveBanner = document.getElementById("adaptiveBanner");
  const adaptiveBannerIcon = document.getElementById("adaptiveBannerIcon");
  const adaptiveBannerTitle = document.getElementById("adaptiveBannerTitle");
  const adaptiveCategoryBadge = document.getElementById("adaptiveCategoryBadge");
  const surveyScoreBanner = document.getElementById("surveyScoreBanner");
  const resumeCategoryLabel = document.getElementById("resumeCategoryLabel");

  const scoreText = score ? `Skor: ${score}/40` : 'Skor: -/40';
  if (surveyScorePill) surveyScorePill.textContent = scoreText;
  if (surveyScoreBanner) surveyScoreBanner.textContent = scoreText;

  const isTinggi = (level === "TINGGI" || level === "LANJUTAN");
  currentStudentCategory = isTinggi ? "TINGGI" : "RENDAH";

  if (isTinggi) {
    // Level card
    if (levelCard) { levelCard.style.borderColor = '#6EE7B7'; levelCard.style.background = 'linear-gradient(135deg,#ECFDF5,#D1FAE5)'; }
    if (levelBadge) { levelBadge.className = "level-badge level-tinggi"; levelBadge.textContent = "Tinggi"; }
    if (levelIcon) { levelIcon.style.background = "#6EE7B7"; levelIcon.style.color = "#065F46"; levelIcon.textContent = "🌟"; }
    if (levelDesc) { levelDesc.textContent = `🎉 Kemampuan literasimu di tingkat tinggi!`; levelDesc.style.color = '#065F46'; }
    if (surveyScorePill) { surveyScorePill.style.background = '#6EE7B7'; surveyScorePill.style.color = '#065F46'; }
    if (currentLevelName) currentLevelName.textContent = "Tingkat Tinggi — Materi Mendalam & Analitis";
    // Adaptive banner
    if (adaptiveBanner) { adaptiveBanner.style.background = 'linear-gradient(135deg,#ECFDF5,#D1FAE5)'; adaptiveBanner.style.borderColor = '#6EE7B7'; }
    if (adaptiveBannerIcon) { adaptiveBannerIcon.style.background = '#6EE7B7'; adaptiveBannerIcon.textContent = '🌟'; }
    if (adaptiveBannerTitle) { adaptiveBannerTitle.textContent = 'Tingkat Tinggi — Materi Mendalam & Analitis'; adaptiveBannerTitle.style.color = '#065F46'; }
    if (adaptiveCategoryBadge) { adaptiveCategoryBadge.textContent = '📗 Tingkat Tinggi'; adaptiveCategoryBadge.style.background = '#6EE7B7'; adaptiveCategoryBadge.style.color = '#065F46'; }
    if (resumeCategoryLabel) { resumeCategoryLabel.textContent = '📗 Tingkat Tinggi'; resumeCategoryLabel.style.background = '#DCFCE7'; resumeCategoryLabel.style.color = '#15803D'; }
  } else {
    // Rendah
    if (levelCard) { levelCard.style.borderColor = '#BFDBFE'; levelCard.style.background = 'linear-gradient(135deg,#EFF6FF,#DBEAFE)'; }
    if (levelBadge) { levelBadge.className = "level-badge level-dasar"; levelBadge.textContent = "Rendah"; }
    if (levelIcon) { levelIcon.style.background = "#BFDBFE"; levelIcon.style.color = "#1E40AF"; levelIcon.textContent = "🌱"; }
    if (levelDesc) { levelDesc.textContent = `📚 Fokus penguatan pemahaman konsep dasar dan membaca bertahap.`; levelDesc.style.color = '#1E40AF'; }
    if (surveyScorePill) { surveyScorePill.style.background = '#DBEAFE'; surveyScorePill.style.color = '#1E40AF'; }
    if (currentLevelName) currentLevelName.textContent = "Tingkat Rendah — Penjelasan Bertahap & Contoh Konkret";
    // Adaptive banner
    if (adaptiveBanner) { adaptiveBanner.style.background = 'linear-gradient(135deg,#EFF6FF,#DBEAFE)'; adaptiveBanner.style.borderColor = '#BFDBFE'; }
    if (adaptiveBannerIcon) { adaptiveBannerIcon.style.background = '#BFDBFE'; adaptiveBannerIcon.textContent = '🌱'; }
    if (adaptiveBannerTitle) { adaptiveBannerTitle.textContent = 'Tingkat Rendah — Penjelasan Bertahap & Contoh Konkret'; adaptiveBannerTitle.style.color = '#1E40AF'; }
    if (adaptiveCategoryBadge) { adaptiveCategoryBadge.textContent = '📘 Tingkat Rendah'; adaptiveCategoryBadge.style.background = '#DBEAFE'; adaptiveCategoryBadge.style.color = '#1E40AF'; }
    if (resumeCategoryLabel) { resumeCategoryLabel.textContent = '📘 Tingkat Rendah'; resumeCategoryLabel.style.background = '#DBEAFE'; resumeCategoryLabel.style.color = '#1E40AF'; }
  }

  // Update Latihan Membaca section
  renderLatihanMembaca(currentStudentCategory, score);
}

window.renderLatihanMembaca = async function (category, score) {
  const badge = document.getElementById("latihanLevelBadge");
  const banner = document.getElementById("latihanLevelBanner");
  const icon = document.getElementById("latihanLevelIcon");
  const title = document.getElementById("latihanLevelTitle");
  const desc = document.getElementById("latihanLevelDesc");
  const grid = document.getElementById("latihanBooksGrid");

  const isTinggi = (category === "TINGGI" || category === "LANJUTAN");

  if (badge) {
    badge.textContent = isTinggi ? "🌟 Tingkat Tinggi" : "🌱 Tingkat Rendah";
    badge.style.background = isTinggi ? "#DCFCE7" : "#DBEAFE";
    badge.style.color = isTinggi ? "#15803D" : "#1D4ED8";
    badge.style.borderColor = isTinggi ? "#86EFAC" : "#93C5FD";
  }

  if (banner) {
    banner.style.background = isTinggi ? "linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%)" : "linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%)";
    banner.style.borderColor = isTinggi ? "#86EFAC" : "#93C5FD";
  }

  if (icon) {
    icon.textContent = isTinggi ? "🧠" : "💡";
  }

  if (title) {
    title.textContent = isTinggi
      ? "Koleksi Rekomendasi: Tingkat Tinggi (Pembahasan Analitis & Studi Kasus)"
      : "Koleksi Rekomendasi: Tingkat Rendah (Materi Sederhana, Bertahap & Contoh Konkret)";
    title.style.color = isTinggi ? "#14532D" : "#1E3A8A";
  }

  if (desc) {
    desc.textContent = isTinggi
      ? "Berdasarkan evaluasi angket literasi digitalmu, kamu direkomendasikan membaca buku dengan analisis mendalam, perspektif kritis, dan pemecahan masalah kompleks untuk mengasah daya pikir analitis."
      : "Berdasarkan evaluasi angket literasi digitalmu, kamu direkomendasikan buku dengan bahasa yang ramah, konsep bertahap, dan contoh konkret kehidupan sehari-hari agar membangun fondasi literasi yang kokoh.";
    desc.style.color = isTinggi ? "#166534" : "#1E40AF";
  }

  if (!grid) return;

  grid.innerHTML = `
    <div style="grid-column:1/-1;text-align:center;padding:36px 16px;color:var(--text-muted);">
      <div style="font-size:2rem;margin-bottom:8px;">⏳</div>
      <p>Mengambil buku latihan membaca sesuai kemampuanmu...</p>
    </div>
  `;

  let books = [];
  try {
    const targetLevel = isTinggi ? "TINGGI" : "RENDAH";
    books = await apiGet(`/books?literacy_level=${targetLevel}`);
  } catch (err) {
    const targetLevel = isTinggi ? "TINGGI" : "RENDAH";
    books = (studentBooksList || []).filter(b => {
      const bLevel = (b.literacy_level || "RENDAH").toUpperCase();
      return isTinggi ? (bLevel === "TINGGI" || bLevel === "LANJUTAN") : (bLevel === "RENDAH" || bLevel === "DASAR");
    });
  }

  if (!books || books.length === 0) {
    grid.innerHTML = `
      <div style="grid-column:1/-1;text-align:center;padding:40px 20px;background:#FFFFFF;border:1.5px dashed var(--border-color);border-radius:var(--radius-xl);">
        <div style="font-size:2.4rem;margin-bottom:10px;">📚</div>
        <h4 style="font-size:1.05rem;font-weight:700;color:var(--text-primary);margin-bottom:6px;">
          Belum ada buku untuk kategori ini
        </h4>
        <p style="font-size:0.88rem;color:var(--text-secondary);max-width:440px;margin:0 auto 16px;">
          Guru sedang mempersiapkan bahan bacaan tambahan yang sesuai dengan tingkatan literasi Anda.
        </p>
        <a href="katalog.html" class="btn btn-outline btn-sm">Buka Semua Buku di Katalog</a>
      </div>
    `;
    return;
  }

  grid.innerHTML = books.map(book => {
    const coverUrl = coverSrc(book.cover, book);
    const badgeText = isTinggi ? "🌟 Tingkat Tinggi" : "🌱 Tingkat Rendah";
    const badgeBg = isTinggi ? "#DCFCE7" : "#DBEAFE";
    const badgeColor = isTinggi ? "#15803D" : "#1D4ED8";
    const excerpt = isTinggi
      ? "Materi analitis dan studi kasus komprehensif untuk menguji pemikiran kritis."
      : "Penjelasan terstruktur langkah demi langkah dengan ilustrasi dan contoh konkret.";

    return `
      <div class="book-card" style="display:flex;flex-direction:column;justify-content:space-between;border:1.5px solid var(--border-color);border-radius:var(--radius-xl);overflow:hidden;background:#FFFFFF;transition:transform 0.2s ease, box-shadow 0.2s ease;">
        <div>
          <div style="position:relative;width:100%;aspect-ratio:3/4;overflow:hidden;background:#EEF2FF;display:flex;align-items:center;justify-content:center;">
            <img src="${coverUrl}" alt="${escapeHtml(book.title)}" style="width:100%;height:100%;object-fit:cover;" onerror="this.src=defaultCover('${escapeHtml(book.title)}', '${escapeHtml(book.author)}', '${escapeHtml(book.category)}', '${book.literacy_level}')" />
            <span style="position:absolute;top:10px;left:10px;padding:4px 10px;border-radius:var(--radius-full);font-size:0.75rem;font-weight:800;background:${badgeBg};color:${badgeColor};box-shadow:0 2px 6px rgba(0,0,0,0.12);">
              ${badgeText}
            </span>
          </div>
          <div style="padding:16px;">
            <span style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;">
              ${escapeHtml(book.category || 'Literasi Digital')}
            </span>
            <h4 style="font-size:1rem;font-weight:800;color:var(--text-primary);margin:6px 0 4px;line-height:1.35;">
              ${escapeHtml(book.title)}
            </h4>
            <p style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:10px;">
              ✍️ ${escapeHtml(book.author || 'Penulis')}
            </p>
            <p style="font-size:0.82rem;color:#475569;line-height:1.45;background:#F8FAFC;padding:8px 10px;border-radius:var(--radius-md);border:1px solid #E2E8F0;">
              ${excerpt}
            </p>
          </div>
        </div>
        <div style="padding:12px 16px 16px;border-top:1px solid #F1F5F9;display:flex;gap:8px;">
          <a href="reader.html?id=${book.id}" class="btn btn-primary btn-sm" style="flex:1;text-align:center;font-weight:700;display:flex;align-items:center;justify-content:center;gap:6px;">
            📖 Baca Flipbook
          </a>
        </div>
      </div>
    `;
  }).join("");
};

async function updateReadingLevel(readingData) {
  const completedBooks = readingData.filter(log => Number(log.progress) >= 100);
  const completedCount = new Set(completedBooks.map(b => b.bookId)).size;
  const avgProgress = readingData.length
    ? Math.round(readingData.reduce((a, i) => a + i.progress, 0) / readingData.length)
    : 0;

  let totalQuizScore = 0;
  let quizCount = 0;
  try {
    const submissions = await apiGet("/quizzes/my-submissions");
    if (Array.isArray(submissions) && submissions.length > 0) {
      submissions.forEach(s => {
        if (s.score !== undefined) {
          totalQuizScore += Number(s.score);
          quizCount++;
        }
      });
    }
  } catch { }

  const avgQuizScore = quizCount > 0 ? totalQuizScore / quizCount : 0;
  const isHighLevel = (completedCount >= 3 && avgProgress >= 70) || (quizCount > 0 && avgQuizScore >= 70);

  applySurveyLevel(isHighLevel ? "TINGGI" : "RENDAH", 0);
}

window.loadMyResumesList = async function () {
  const tableBody = document.getElementById("studentResumesListBody");
  if (!tableBody) return;

  let resumes = [];
  try {
    resumes = await apiGet("/resumes");
  } catch (err) {
    try {
      resumes = JSON.parse(localStorage.getItem("pantaubaca_student_resumes") || "[]");
      if (auth.userId) {
        resumes = resumes.filter(r => Number(r.user_id) === Number(auth.userId));
      }
    } catch {
      resumes = [];
    }
  }

  if (!resumes || resumes.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;padding:32px 16px;color:var(--text-muted);">
          <div style="font-size:2rem;margin-bottom:8px;">📝</div>
          <p style="font-weight:700;color:var(--text-secondary);margin-bottom:4px;">Kamu belum membuat resume buku.</p>
          <p style="font-size:0.86rem;margin-bottom:14px;">Buka katalog buku dan mulailah meresume buku yang kamu baca untuk dinilai oleh gurumu!</p>
          <a href="katalog.html" class="btn btn-primary btn-sm">Buka Katalog Buku →</a>
        </td>
      </tr>
    `;
    return;
  }

  tableBody.innerHTML = resumes.map((r, idx) => {
    const bookTitle = r.book ? r.book.title : (studentBooksList.find(b => Number(b.id) === Number(r.book_id))?.title || "Buku #" + r.book_id);
    const dateStr = r.updated_at || r.created_at ? new Date(r.updated_at || r.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : "-";
    const isGraded = r.score !== null && r.score !== undefined;
    const snippet = r.content && r.content.length > 80 ? r.content.substring(0, 80) + "..." : (r.content || "-");

    return `
      <tr>
        <td><strong>${idx + 1}</strong></td>
        <td><strong style="color:var(--primary);">${escapeHtml(bookTitle)}</strong></td>
        <td style="font-size:0.85rem;color:var(--text-secondary);">${dateStr}</td>
        <td style="max-width:280px;font-size:0.88rem;color:#334155;line-height:1.4;">${escapeHtml(snippet)}</td>
        <td>
          ${isGraded
        ? `<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:800;background:#DCFCE7;color:#15803D;">⭐ ${r.score} / 100</span>`
        : `<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:800;background:#FEF3C7;color:#92400E;">⏳ Menunggu Nilai</span>`
      }
        </td>
        <td style="max-width:220px;font-size:0.86rem;color:var(--text-secondary);">
          ${r.teacher_feedback ? `<em>"${escapeHtml(r.teacher_feedback)}"</em>` : `<span style="color:var(--text-muted);">-</span>`}
        </td>
        <td>
          <a href="katalog.html" class="btn btn-outline btn-sm">Lihat di Katalog</a>
        </td>
      </tr>
    `;
  }).join("");
};

function updateActivityFeed(data) {
  const feed = document.getElementById("activityFeed");
  if (!feed || data.length === 0) return;

  const recent = data.slice(0, 3);
  feed.innerHTML = recent.map(item => `
    <div class="activity-item">
      <div class="activity-dot blue">📖</div>
      <div class="activity-text">
        Membaca "${escapeHtml(item.bookTitle)}" selama ${item.duration} menit
        <small>${new Date(item.updatedAt).toLocaleString()}</small>
      </div>
    </div>
  `).join("") + `
    <div class="activity-item">
      <div class="activity-dot green">🎯</div>
      <div class="activity-text">
        Target mingguan: ${new Set(data.map(i => i.bookId)).size}/10 buku
        <small>Terus semangat membaca!</small>
      </div>
    </div>
  `;
}

let studentSubmissionsList = [];
let activeQuizState = null;

async function loadAvailableQuizzes(readingLogs) {
  if (auth.role !== "SISWA") {
    if (quizList) quizList.innerHTML = '<p class="empty-state">Mode admin hanya untuk memantau dashboard siswa.</p>';
    return;
  }

  // 1. Fetch student submissions from database
  try {
    const subs = await apiGet("/quizzes/my-submissions");
    if (Array.isArray(subs)) {
      studentSubmissionsList = subs;
    }
  } catch (err) {
    console.warn("Gagal memuat riwayat kuis:", err);
    studentSubmissionsList = [];
  }

  // 2. Identify completed books (progress >= 100%)
  const completedBooks = Array.from(
    new Map(
      readingLogs
        .filter(log => Number(log.progress) >= 100)
        .map(log => [Number(log.bookId), log])
    ).values()
  );

  // Also include books that were previously submitted even if not in current log session
  studentSubmissionsList.forEach(sub => {
    if (sub.bookId && !completedBooks.some(b => Number(b.bookId) === Number(sub.bookId))) {
      completedBooks.push({ bookId: sub.bookId, bookTitle: sub.bookTitle || "Buku #" + sub.bookId, progress: 100 });
    }
  });

  if (completedBooks.length === 0) {
    if (quizList) {
      quizList.innerHTML = `
        <div style="text-align:center;padding:36px 16px;background:#F8FAFC;border:1.5px dashed #CBD5E1;border-radius:16px;">
          <div style="font-size:2.5rem;margin-bottom:8px;">📖</div>
          <h3 style="font-size:1.15rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">Kuis Belum Terbuka</h3>
          <p style="color:var(--text-secondary);font-size:0.88rem;max-width:440px;margin:0 auto 16px;">
            Selesaikan membaca buku sampai 100% di e-reader PantauBaca untuk membuka kuis pemahaman materi.
          </p>
          <a href="katalog.html" class="btn btn-primary btn-sm">Buka Katalog Buku &rarr;</a>
        </div>
      `;
    }
    return;
  }

  // 3. Fetch active quiz for each completed book
  const quizResults = await Promise.all(
    completedBooks.map(async log => {
      try {
        const q = await apiGet(`/books/${log.bookId}/quiz`);
        if (q && q.quizId) {
          q.bookTitle = log.bookTitle || (studentBooksList.find(b => Number(b.id) === Number(log.bookId))?.title) || "Buku #" + log.bookId;
          return q;
        }
        return null;
      } catch {
        return null;
      }
    })
  );

  availableQuizzes = quizResults.filter(Boolean);

  renderQuizListUI();
}

function getQuizDraftKey(quizId) {
  return `pantaubaca_quiz_draft_${auth.userId || 0}_${quizId}`;
}

function renderQuizListUI() {
  if (!quizList) return;

  if (availableQuizzes.length === 0) {
    quizList.innerHTML = `
      <div style="text-align:center;padding:36px 16px;background:#F8FAFC;border:1.5px dashed #CBD5E1;border-radius:16px;">
        <div style="font-size:2.5rem;margin-bottom:8px;">📝</div>
        <h3 style="font-size:1.15rem;font-weight:800;color:var(--text-primary);margin-bottom:4px;">Belum Ada Kuis Aktif</h3>
        <p style="color:var(--text-secondary);font-size:0.88rem;max-width:440px;margin:0 auto;">
          Guru belum mengaktifkan kuis untuk buku yang telah kamu selesaikan. Silakan baca buku lainnya di katalog!
        </p>
      </div>
    `;
    return;
  }

  quizList.innerHTML = `
    <div class="quiz-grid-container">
      ${availableQuizzes.map(quiz => {
        const sub = studentSubmissionsList.find(s => Number(s.quizId) === Number(quiz.quizId));
        let draft = null;
        try {
          draft = JSON.parse(localStorage.getItem(getQuizDraftKey(quiz.quizId)) || "null");
        } catch { }

        const qCount = quiz.questions ? quiz.questions.length : 0;
        const totalPoints = quiz.questions ? quiz.questions.reduce((a, q) => a + Number(q.points || 0), 0) : 0;

        let badgeHtml = "";
        let metaHtml = "";
        let actionsHtml = "";

        if (sub) {
          const dateStr = sub.submittedAt
            ? new Date(sub.submittedAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
            : "-";

          badgeHtml = `<span class="quiz-badge quiz-badge-completed">✅ Selesai Dikerjakan</span>`;
          metaHtml = `
            <div style="display:flex;align-items:center;justify-content:space-between;width:100%;margin-bottom:6px;">
              <span class="quiz-score-badge">Nilai: ${sub.score} / 100</span>
              <span style="font-size:0.8rem;color:var(--text-secondary);">${dateStr}</span>
            </div>
            <div style="font-size:0.82rem;color:var(--text-muted);">
              Hasil: <strong>${sub.correctCount || 0} Benar</strong> &bull; <strong>${sub.wrongCount || 0} Salah</strong> &bull; Total ${sub.totalQuestions || qCount} Soal
            </div>
          `;
          actionsHtml = `
            <button type="button" class="btn btn-primary btn-sm" onclick="viewQuizResult(${sub.id})" style="flex:1;">
              📊 Lihat Hasil & Pembahasan
            </button>
            <button type="button" class="btn btn-outline btn-sm" onclick="startQuiz(${quiz.quizId}, true)" title="Kerjakan kuis kembali">
              🔄 Ulangi
            </button>
          `;
        } else if (draft && draft.answers && Object.keys(draft.answers).length > 0) {
          const answeredCount = Object.keys(draft.answers).length;
          badgeHtml = `<span class="quiz-badge quiz-badge-inprogress">⏳ Sedang Berlangsung</span>`;
          metaHtml = `
            <div style="font-size:0.85rem;color:#92400E;font-weight:700;margin-bottom:4px;">
              Draft Tersimpan: ${answeredCount} dari ${qCount} Soal Terjawab
            </div>
            <div style="font-size:0.8rem;color:var(--text-muted);">
              Waktu berjalan: ${formatQuizDuration(draft.timerSeconds || 0)}
            </div>
          `;
          actionsHtml = `
            <button type="button" class="btn btn-primary btn-sm" onclick="startQuiz(${quiz.quizId}, false)" style="flex:1;">
              ▶️ Lanjutkan Kuis
            </button>
            <button type="button" class="btn btn-outline btn-sm" onclick="restartQuizDraft(${quiz.quizId})" title="Mulai ulang dari awal">
              Mulai Ulang
            </button>
          `;
        } else {
          badgeHtml = `<span class="quiz-badge quiz-badge-notstarted">🆕 Belum Dikerjakan</span>`;
          metaHtml = `
            <div style="font-size:0.84rem;color:var(--text-secondary);">
              📝 <strong>${qCount} Soal</strong> &bull; Bobot <strong>${totalPoints} Poin</strong> &bull; Estimasi ${Math.ceil(qCount * 2)} Menit
            </div>
          `;
          actionsHtml = `
            <button type="button" class="btn btn-primary btn-sm" onclick="startQuiz(${quiz.quizId}, true)" style="width:100%;">
              🚀 Kerjakan Kuis
            </button>
          `;
        }

        return `
          <article class="quiz-card">
            <div>
              <div class="quiz-card-top">
                <span class="quiz-card-book">📖 ${escapeHtml(quiz.bookTitle || "Buku Digital")}</span>
                ${badgeHtml}
              </div>
              <h3 class="quiz-card-title">${escapeHtml(quiz.title)}</h3>
              <p class="quiz-card-desc">${escapeHtml(quiz.description || "Uji daya serap materi bacaan setelah membaca buku ini secara tuntas.")}</p>
            </div>
            <div>
              <div class="quiz-card-meta">
                ${metaHtml}
              </div>
              <div class="quiz-card-actions">
                ${actionsHtml}
              </div>
            </div>
          </article>
        `;
      }).join("")}
    </div>
  `;
}

function formatQuizDuration(seconds) {
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return `${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
}

window.restartQuizDraft = function(quizId) {
  localStorage.removeItem(getQuizDraftKey(quizId));
  startQuiz(quizId, true);
};

/* ==========================================================================
   Quiz Player (Pengerjaan Kuis Siswa: Modern UI/UX)
   ========================================================================== */
window.startQuiz = function(quizId, isNewAttempt) {
  const quiz = availableQuizzes.find(item => Number(item.quizId) === Number(quizId));
  if (!quiz || !quiz.questions || quiz.questions.length === 0) {
    showToast("Pertanyaan kuis belum tersedia.", "warning");
    return;
  }

  // Clean existing timer if any
  if (activeQuizState && activeQuizState.timerInterval) {
    clearInterval(activeQuizState.timerInterval);
  }

  const draftKey = getQuizDraftKey(quiz.quizId);
  let savedAnswers = {};
  let savedTimerSeconds = 0;
  let savedIndex = 0;

  if (!isNewAttempt) {
    try {
      const draft = JSON.parse(localStorage.getItem(draftKey) || "null");
      if (draft) {
        savedAnswers = draft.answers || {};
        savedTimerSeconds = Number(draft.timerSeconds || 0);
        savedIndex = Number(draft.currentIndex || 0);
        if (savedIndex >= quiz.questions.length) savedIndex = 0;
      }
    } catch { }
  } else {
    localStorage.removeItem(draftKey);
  }

  activeQuizState = {
    quiz,
    currentIndex: savedIndex,
    answers: savedAnswers,
    timerSeconds: savedTimerSeconds,
    timerInterval: null,
    isSubmitting: false
  };

  // Start timer interval
  activeQuizState.timerInterval = setInterval(() => {
    if (!activeQuizState) return;
    activeQuizState.timerSeconds++;
    const timerPill = document.getElementById("playerTimerPill");
    if (timerPill) {
      timerPill.textContent = `⏱️ ${formatQuizDuration(activeQuizState.timerSeconds)}`;
    }
    // Autosave timer every 5 seconds
    if (activeQuizState.timerSeconds % 5 === 0) {
      saveQuizDraftLocally();
    }
  }, 1000);

  // Toggle panels
  if (quizList) quizList.classList.add("hidden");
  if (quizAttemptPanel) quizAttemptPanel.classList.remove("hidden");

  renderCurrentQuizQuestion();
  quizAttemptPanel.scrollIntoView({ behavior: "smooth", block: "start" });
};

function saveQuizDraftLocally() {
  if (!activeQuizState || !activeQuizState.quiz) return;
  const draftKey = getQuizDraftKey(activeQuizState.quiz.quizId);
  const data = {
    answers: activeQuizState.answers,
    timerSeconds: activeQuizState.timerSeconds,
    currentIndex: activeQuizState.currentIndex
  };
  localStorage.setItem(draftKey, JSON.stringify(data));
}

function renderCurrentQuizQuestion() {
  if (!activeQuizState || !activeQuizState.quiz || !quizAttemptPanel) return;

  const quiz = activeQuizState.quiz;
  const total = quiz.questions.length;
  const currentIdx = activeQuizState.currentIndex;
  const q = quiz.questions[currentIdx];
  const percent = Math.round(((currentIdx + 1) / total) * 100);
  const answeredCount = Object.keys(activeQuizState.answers).filter(k => Boolean(activeQuizState.answers[k]?.trim())).length;
  const currentAnswer = activeQuizState.answers[q.questionId] || "";

  const isMcq = q.type === "MCQ";
  const options = Array.isArray(q.options) ? q.options : [];
  const isFirst = currentIdx === 0;
  const isLast = currentIdx === total - 1;

  quizAttemptPanel.innerHTML = `
    <div class="quiz-player-wrap">
      <!-- Player Header -->
      <div class="quiz-player-header">
        <div style="display:flex;align-items:center;gap:12px;">
          <button type="button" class="btn btn-outline btn-sm" onclick="backToQuizList(true)" style="display:inline-flex;align-items:center;gap:6px;font-weight:700;">
            &larr; Keluar Kuis
          </button>
          <div>
            <h2 style="font-size:1.25rem;font-weight:800;color:var(--text-primary);margin:0;">
              ${escapeHtml(quiz.title)}
            </h2>
            <div style="font-size:0.84rem;color:var(--primary);font-weight:600;margin-top:2px;">
              📖 ${escapeHtml(quiz.bookTitle || "Buku")}
            </div>
          </div>
        </div>
        <div>
          <span class="quiz-timer-pill" id="playerTimerPill">
            ⏱️ ${formatQuizDuration(activeQuizState.timerSeconds)}
          </span>
        </div>
      </div>

      <!-- Progress Panel -->
      <div class="quiz-progress-panel">
        <div class="quiz-progress-top">
          <span>Soal <strong>${currentIdx + 1}</strong> dari <strong>${total}</strong></span>
          <span style="color:var(--primary);">Terjawab: <strong>${answeredCount}</strong> / ${total}</span>
        </div>
        <div class="quiz-progress-bar-container">
          <div class="quiz-progress-bar-active" style="width:${percent}%"></div>
        </div>

        <!-- Question Pills Grid -->
        <div class="quiz-nav-pills-row">
          ${quiz.questions.map((item, idx) => {
            const hasAns = Boolean(activeQuizState.answers[item.questionId]?.trim());
            const isCurrent = idx === currentIdx;
            let classes = "quiz-nav-pill-btn";
            if (isCurrent) classes += " current";
            if (hasAns) classes += " answered";
            return `
              <button type="button" class="${classes}" onclick="jumpToQuizQuestion(${idx})" title="Buka Soal #${idx + 1}">
                ${idx + 1}
              </button>
            `;
          }).join("")}
        </div>
      </div>

      <!-- Active Question Card -->
      <div class="quiz-active-card">
        <div class="quiz-question-meta-row">
          <div style="display:flex;gap:8px;align-items:center;">
            <span class="quiz-badge quiz-badge-notstarted">Soal #${currentIdx + 1}</span>
            <span style="font-size:0.82rem;font-weight:700;color:var(--text-secondary);background:#F1F5F9;padding:3px 10px;border-radius:999px;">
              ${isMcq ? 'Pilihan Ganda' : 'Esai'}
            </span>
          </div>
          <span style="font-size:0.82rem;font-weight:800;color:#92400E;background:#FEF3C7;padding:3px 12px;border-radius:999px;">
            ⭐ ${q.points || 1} Poin
          </span>
        </div>

        <div class="quiz-active-question-text">
          ${escapeHtml(q.question)}
        </div>

        ${isMcq ? `
          <div class="quiz-options-container">
            ${options.map((opt, oIdx) => {
              const letter = String.fromCharCode(65 + oIdx);
              const isSelected = strEquals(currentAnswer, opt);
              return `
                <label class="quiz-option-item ${isSelected ? 'selected' : ''}" onclick="selectQuizOption(${q.questionId}, '${escapeHtml(opt)}')">
                  <input type="radio" name="question_${q.questionId}" class="quiz-option-radio-input" value="${escapeHtml(opt)}" ${isSelected ? 'checked' : ''} />
                  <span class="quiz-option-badge-letter">${letter}</span>
                  <span class="quiz-option-item-text">${escapeHtml(opt)}</span>
                </label>
              `;
            }).join("")}
          </div>
        ` : `
          <div>
            <textarea class="quiz-essay-textarea" placeholder="Tuliskan jawaban pemahamanmu di sini secara terperinci..." oninput="onQuizEssayInput(${q.questionId}, this.value)">${escapeHtml(currentAnswer)}</textarea>
          </div>
        `}
      </div>

      <!-- Player Navigation Footer -->
      <div class="quiz-player-nav-footer">
        <div>
          <button type="button" class="btn btn-outline" onclick="prevQuizQuestion()" ${isFirst ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''}>
            &larr; Soal Sebelumnya
          </button>
        </div>

        <div>
          <span class="quiz-autosave-status">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Jawaban tersimpan otomatis
          </span>
        </div>

        <div style="display:flex;gap:10px;">
          ${!isLast ? `
            <button type="button" class="btn btn-primary" onclick="nextQuizQuestion()">
              Soal Berikutnya &rarr;
            </button>
          ` : `
            <button type="button" class="btn btn-primary" onclick="openQuizConfirmModal()" style="background:#10B981;border-color:#10B981;">
              ✅ Selesaikan Kuis
            </button>
          `}
        </div>
      </div>
    </div>
  `;
}

window.selectQuizOption = function(qId, value) {
  if (!activeQuizState) return;
  activeQuizState.answers[qId] = value;
  saveQuizDraftLocally();
  renderCurrentQuizQuestion();
};

window.onQuizEssayInput = function(qId, value) {
  if (!activeQuizState) return;
  activeQuizState.answers[qId] = value;
  saveQuizDraftLocally();
};

window.nextQuizQuestion = function() {
  if (!activeQuizState || !activeQuizState.quiz) return;
  if (activeQuizState.currentIndex < activeQuizState.quiz.questions.length - 1) {
    activeQuizState.currentIndex++;
    saveQuizDraftLocally();
    renderCurrentQuizQuestion();
    quizAttemptPanel.scrollIntoView({ behavior: "smooth", block: "start" });
  }
};

window.prevQuizQuestion = function() {
  if (!activeQuizState) return;
  if (activeQuizState.currentIndex > 0) {
    activeQuizState.currentIndex--;
    saveQuizDraftLocally();
    renderCurrentQuizQuestion();
    quizAttemptPanel.scrollIntoView({ behavior: "smooth", block: "start" });
  }
};

window.jumpToQuizQuestion = function(idx) {
  if (!activeQuizState || !activeQuizState.quiz) return;
  if (idx >= 0 && idx < activeQuizState.quiz.questions.length) {
    activeQuizState.currentIndex = idx;
    saveQuizDraftLocally();
    renderCurrentQuizQuestion();
    quizAttemptPanel.scrollIntoView({ behavior: "smooth", block: "start" });
  }
};

/* Modal Konfirmasi Pengiriman Kuis */
window.openQuizConfirmModal = function() {
  if (!activeQuizState || !activeQuizState.quiz) return;
  const total = activeQuizState.quiz.questions.length;
  const answeredCount = Object.keys(activeQuizState.answers).filter(k => Boolean(activeQuizState.answers[k]?.trim())).length;
  const unansweredCount = total - answeredCount;

  const ansEl = document.getElementById("confirmAnsweredCount");
  const unansEl = document.getElementById("confirmUnansweredCount");
  const warnBox = document.getElementById("confirmWarningBox");
  const warnText = document.getElementById("confirmWarningUnansweredText");
  const readyBox = document.getElementById("confirmReadyBox");

  if (ansEl) ansEl.textContent = answeredCount;
  if (unansEl) unansEl.textContent = unansweredCount;

  if (unansweredCount > 0) {
    if (warnBox) warnBox.style.display = "block";
    if (warnText) warnText.textContent = unansweredCount;
    if (readyBox) readyBox.style.display = "none";
  } else {
    if (warnBox) warnBox.style.display = "none";
    if (readyBox) readyBox.style.display = "block";
  }

  const modal = document.getElementById("quizSubmitConfirmModal");
  if (modal) modal.classList.add("active");
};

window.closeQuizConfirmModal = function() {
  const modal = document.getElementById("quizSubmitConfirmModal");
  if (modal) modal.classList.remove("active");
};

window.executeQuizSubmit = async function() {
  if (!activeQuizState || activeQuizState.isSubmitting) return;

  const submitBtn = document.getElementById("confirmSubmitQuizBtn");
  activeQuizState.isSubmitting = true;
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Mengirim jawaban...";
  }

  const quiz = activeQuizState.quiz;
  const answers = quiz.questions.map(q => ({
    questionId: q.questionId,
    response: String(activeQuizState.answers[q.questionId] || "")
  }));

  try {
    const res = await apiPost("/quizzes/submit", {
      quizId: quiz.quizId,
      durationSeconds: activeQuizState.timerSeconds || 0,
      answers
    });

    // Clear local storage draft
    localStorage.removeItem(getQuizDraftKey(quiz.quizId));

    // Clear interval
    if (activeQuizState.timerInterval) {
      clearInterval(activeQuizState.timerInterval);
    }

    closeQuizConfirmModal();

    // Load full submission review
    showToast("Jawaban kuis berhasil dikirim!", "success");
    await viewQuizResult(res.submissionId);

    // Refresh student literacy levels & background data
    try {
      const data = await apiGet("/reading/report");
      await updateReadingLevel(data);
    } catch { }
  } catch (err) {
    showToast(err.message || "Gagal mengirim kuis.", "error");
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = "🚀 Ya, Kirim Sekarang";
    }
    activeQuizState.isSubmitting = false;
  }
};

/* ==========================================================================
   Halaman Hasil Kuis Pemahaman Siswa
   ========================================================================== */
window.viewQuizResult = async function(submissionId) {
  if (quizList) quizList.classList.add("hidden");
  if (quizAttemptPanel) {
    quizAttemptPanel.classList.remove("hidden");
    quizAttemptPanel.innerHTML = `
      <div class="quiz-result-card" style="text-align:center;padding:50px 20px;">
        <div class="page-spinner" style="margin:0 auto 16px;"></div>
        <p style="color:var(--text-secondary);font-weight:600;">Memuat hasil dan rincian kuis...</p>
      </div>
    `;
    quizAttemptPanel.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  try {
    const data = await apiGet(`/quizzes/submissions/${submissionId}`);
    renderQuizResultView(data);
  } catch (err) {
    showToast(err.message || "Gagal memuat hasil kuis.", "error");
    backToQuizList(false);
  }
};

function renderQuizResultView(data) {
  if (!quizAttemptPanel) return;

  const score = Number(data.score || 0);
  let scoreClass = "#10B981";
  let motivationalTitle = "🎉 Luar Biasa! Pemahaman Membaca Sangat Baik!";
  let motivationalDesc = "Kamu berhasil memahami pesan dan gagasan utama dari buku ini secara sangat tajam.";

  if (score < 60) {
    scoreClass = "#EF4444";
    motivationalTitle = "📚 Terus Semangat Berlatih Membaca!";
    motivationalDesc = "Coba cermati kembali poin-poin penting pada buku dan pelajari pembahasannya di bawah ini.";
  } else if (score < 80) {
    scoreClass = "#F59E0B";
    motivationalTitle = "👍 Bagus Sekali! Pemahamanmu Cukup Baik!";
    motivationalDesc = "Daya serap materimu sudah baik, simak pembahasan berikut untuk menyempurnakan pemahamanmu.";
  }

  const durationStr = formatQuizDuration(data.durationSeconds || 0);
  const dateStr = data.submittedAt
    ? new Date(data.submittedAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    : "-";

  const answers = Array.isArray(data.answers) ? data.answers : [];

  quizAttemptPanel.innerHTML = `
    <div class="quiz-result-card">
      <!-- Hero Hasil -->
      <div class="quiz-result-hero">
        <div class="quiz-score-circle">
          <span class="score-number">${score}</span>
          <span class="score-label">Skala 0 - 100</span>
        </div>
        <h2 style="font-size:1.35rem;font-weight:800;color:#0F172A;margin:0 0 6px 0;">${motivationalTitle}</h2>
        <p style="font-size:0.92rem;color:#475569;max-width:540px;margin:0 auto 12px;line-height:1.6;">${motivationalDesc}</p>
        <div style="font-size:0.84rem;color:#64748B;">
          Kuis: <strong>${escapeHtml(data.quizTitle || "Kuis Pemahaman")}</strong> &bull; Buku: <strong>${escapeHtml(data.bookTitle || "-")}</strong> &bull; Dikerjakan: ${dateStr}
        </div>
      </div>

      <!-- Ringkasan Statistik -->
      <div class="quiz-result-stats-row">
        <div class="quiz-stat-card">
          <div class="stat-lbl">Nilai Akhir</div>
          <div class="stat-val" style="color:var(--primary);">${score} / 100</div>
        </div>
        <div class="quiz-stat-card">
          <div class="stat-lbl">Jawaban Benar</div>
          <div class="stat-val" style="color:#10B981;">${data.correctCount || 0} Soal</div>
        </div>
        <div class="quiz-stat-card">
          <div class="stat-lbl">Jawaban Salah</div>
          <div class="stat-val" style="color:#EF4444;">${data.wrongCount || 0} Soal</div>
        </div>
        <div class="quiz-stat-card">
          <div class="stat-lbl">Durasi Pengerjaan</div>
          <div class="stat-val" style="color:#6366F1;">${durationStr}</div>
        </div>
      </div>

      <!-- Rincian Tiap Soal & Kunci Jawaban -->
      <div class="quiz-review-section">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
          <h3 style="font-size:1.15rem;font-weight:800;color:#0F172A;margin:0;">
            📋 Rincian Jawaban & Kunci Soal
          </h3>
          <span style="font-size:0.84rem;color:#64748B;">Total ${data.totalQuestions || answers.length} Soal</span>
        </div>

        <div class="quiz-review-list">
          ${answers.map((ans, idx) => {
            const isCorrect = Boolean(ans.isCorrect);
            const isMcq = ans.type === "MCQ";
            const expId = `explanation_${idx}`;

            return `
              <div class="quiz-review-item-card ${isCorrect ? 'is-correct' : 'is-wrong'}">
                <div class="quiz-review-item-header">
                  <span class="quiz-badge ${isCorrect ? 'quiz-badge-completed' : 'quiz-badge-inprogress'}" style="font-size:0.8rem;">
                    Soal #${idx + 1} &bull; ${isCorrect ? '✅ Benar (+ ' + (ans.points || ans.maxPoints) + ' Poin)' : '❌ Salah (0 Poin)'}
                  </span>
                  <span style="font-size:0.8rem;color:#64748B;font-weight:600;">
                    ${isMcq ? 'Pilihan Ganda' : 'Esai'}
                  </span>
                </div>

                <div class="quiz-review-question-text">
                  ${escapeHtml(ans.question)}
                </div>

                <div class="quiz-review-answers-box">
                  <div class="quiz-answer-display-box ${isCorrect ? 'student-correct' : 'student-wrong'}">
                    <strong style="display:block;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px;">
                      Jawaban Kamu:
                    </strong>
                    <span>${ans.studentResponse ? escapeHtml(ans.studentResponse) : '<em>(Tidak dijawab)</em>'}</span>
                  </div>

                  <div class="quiz-answer-display-box correct-key">
                    <strong style="display:block;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px;">
                      Kunci Jawaban Guru:
                    </strong>
                    <span>${escapeHtml(ans.correctAnswer || '(Pedoman Guru)')}</span>
                  </div>
                </div>

                ${ans.explanation ? `
                  <div style="margin-top:12px;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="toggleExplanation('${expId}')" style="font-size:0.8rem;padding:4px 10px;">
                      💡 Lihat Pembahasan Soal
                    </button>
                    <div id="${expId}" class="quiz-explanation-collapsible" style="display:none;">
                      <strong>Penjelasan Guru:</strong><br />
                      ${escapeHtml(ans.explanation)}
                    </div>
                  </div>
                ` : ''}
              </div>
            `;
          }).join("")}
        </div>
      </div>

      <!-- Action Footer -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:28px;padding-top:20px;border-top:1.5px solid #E2E8F0;flex-wrap:wrap;gap:12px;">
        <button type="button" class="btn btn-outline" onclick="backToQuizList(false)">
          &larr; Kembali ke Kuis Pemahaman
        </button>
        <div style="display:flex;gap:10px;">
          <a href="reader.html?id=${data.bookId}" class="btn btn-outline">
            📖 Baca Buku Kembali
          </a>
          <button type="button" class="btn btn-primary" onclick="startQuiz(${data.quizId}, true)">
            🔄 Kerjakan Ulang Kuis
          </button>
        </div>
      </div>
    </div>
  `;
}

window.toggleExplanation = function(id) {
  const el = document.getElementById(id);
  if (el) {
    el.style.display = el.style.display === "none" ? "block" : "none";
  }
};

window.backToQuizList = async function(confirmIfRunning) {
  if (confirmIfRunning && activeQuizState) {
    const ok = await appConfirm("Kuis sedang berlangsung. Jawabanmu sudah tersimpan otomatis. Kamu dapat melanjutkannya nanti. Keluar sekarang?");
    if (!ok) return;
  }

  if (activeQuizState && activeQuizState.timerInterval) {
    clearInterval(activeQuizState.timerInterval);
    activeQuizState = null;
  }

  if (quizAttemptPanel) quizAttemptPanel.classList.add("hidden");
  if (quizList) quizList.classList.remove("hidden");

  try {
    const data = await apiGet("/reading/report");
    await loadAvailableQuizzes(data);
  } catch {
    renderQuizListUI();
  }
};

