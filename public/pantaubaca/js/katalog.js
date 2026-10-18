requireAuth();

const auth = getAuth() || {};
let allBooks = [];
let myResumes = [];
let activeBookId = null;

// Setup header navigation & role indicator
(function setupHeader() {
  const linksContainer = document.getElementById("userDashboardLinks");
  const roleBadge = document.getElementById("roleIndicatorBadge");

  if (linksContainer) {
    if (auth.role === "SISWA") {
      linksContainer.innerHTML = `
        <a href="dashboard-siswa.html" class="btn btn-outline btn-sm">🎓 Dashboard Siswa</a>
      `;
    } else if (auth.role === "GURU") {
      linksContainer.innerHTML = `
        <a href="dashboard-guru.html" class="btn btn-outline btn-sm">🧑‍🏫 Dashboard Guru</a>
      `;
    } else if (auth.role === "ADMIN") {
      linksContainer.innerHTML = `
        <a href="dashboard-admin.html" class="btn btn-outline btn-sm">⚙️ Dashboard Admin</a>
      `;
    }
  }

  if (roleBadge) {
    if (auth.role === "SISWA") {
      roleBadge.innerHTML = `
        <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:20px;font-size:0.84rem;font-weight:700;background:#DBEAFE;color:#1D4ED8;">
          🎓 Mode Siswa (Dapat Meresume Buku)
        </span>
      `;
    } else if (auth.role === "GURU") {
      roleBadge.innerHTML = `
        <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:20px;font-size:0.84rem;font-weight:700;background:#FEF3C7;color:#92400E;">
          🧑‍🏫 Mode Guru (Menilai Resume di Dashboard)
        </span>
      `;
    }
  }
})();

let studentSurveyLevel = null;
let isStudentFilterActive = false;

// Fetch books and student resumes
(async function initKatalog() {
  try {
    const books = await apiGet("/books");
    allBooks = books;
    fillCategories(books);

    // If student, check survey result and apply automatic filtering
    if (auth.role === "SISWA") {
      try {
        const surveyRes = await apiGet("/literacy-survey/my-result");
        if (surveyRes && surveyRes.has_submitted) {
          studentSurveyLevel = (surveyRes.level === "TINGGI" || surveyRes.level === "LANJUTAN") ? "TINGGI" : "RENDAH";
        }
      } catch (err) {
        // Fallback demo survey
        try {
          const demoSurvey = JSON.parse(localStorage.getItem("pantaubaca_demo_survey") || "null");
          if (demoSurvey) {
            studentSurveyLevel = (demoSurvey.level === "TINGGI" || demoSurvey.level === "LANJUTAN") ? "TINGGI" : "RENDAH";
          }
        } catch {}
      }

      // Default: if student has survey level, apply filter automatically
      if (studentSurveyLevel) {
        isStudentFilterActive = true;
        applyStudentLiteracyUI(studentSurveyLevel);
      }

      try {
        const resumes = await apiGet("/resumes");
        if (Array.isArray(resumes)) {
          myResumes = resumes;
        }
      } catch (err) {
        console.warn("Could not load resumes from API, falling back to local:", err);
        myResumes = getLocalResumes();
      }
    }

    filter();
  } catch (e) {
    if (!isNetworkError(e)) throw e;
    allBooks = demoBooks();
    fillCategories(allBooks);
    myResumes = getLocalResumes();
    render(allBooks);
    showInfo("Backend belum terhubung. Menampilkan katalog demo.");
  }
})();

const searchEl = document.getElementById("search");
const catEl = document.getElementById("categoryFilter");
const litEl = document.getElementById("literacyFilter");

if (searchEl) searchEl.addEventListener("input", filter);
if (catEl) catEl.addEventListener("change", filter);
if (litEl) litEl.addEventListener("change", () => {
  // If user manually changes dropdown, deactivate automatic override
  isStudentFilterActive = false;
  filter();
});

function applyStudentLiteracyUI(level) {
  const banner = document.getElementById("studentLiteracyBanner");
  const pill = document.getElementById("studentLiteracyPill");
  const icon = document.getElementById("studentLiteracyIcon");
  const title = document.getElementById("studentLiteracyTitle");
  const desc = document.getElementById("studentLiteracyDesc");
  const btn = document.getElementById("toggleAllBooksBtn");

  if (!banner) return;
  banner.style.display = "flex";

  const isTinggi = (level === "TINGGI");
  if (litEl) litEl.value = isTinggi ? "TINGGI" : "RENDAH";

  if (pill) {
    pill.textContent = isTinggi ? "🌟 Buku Kemampuan Lanjutan" : "🌱 Buku Kemampuan Rendah";
    pill.style.background = isTinggi ? "#DCFCE7" : "#DBEAFE";
    pill.style.color = isTinggi ? "#15803D" : "#1D4ED8";
  }

  if (icon) icon.textContent = isTinggi ? "🌟" : "🌱";
  if (title) {
    title.textContent = isTinggi
      ? "Katalog Disesuaikan: Buku Kemampuan Lanjutan"
      : "Katalog Disesuaikan: Buku Kemampuan Rendah";
  }
  if (desc) {
    desc.textContent = isTinggi
      ? "Berdasarkan hasil asesmen awal, Anda mendapatkan rekomendasi buku dengan pembahasan analitis dan tantangan pemahaman."
      : "Berdasarkan hasil asesmen awal, Anda mendapatkan rekomendasi buku dengan penjelasan bertahap, bahasa mudah dipahami, dan contoh konkret.";
  }
  if (btn) btn.textContent = "Lihat Semua Buku";
}

window.toggleStudentLiteracyFilter = function() {
  const btn = document.getElementById("toggleAllBooksBtn");
  if (isStudentFilterActive) {
    isStudentFilterActive = false;
    if (litEl) litEl.value = "";
    if (btn) btn.textContent = "Terapkan Filter Sesuai Asesmen";
  } else {
    isStudentFilterActive = true;
    if (litEl && studentSurveyLevel) litEl.value = studentSurveyLevel;
    if (btn) btn.textContent = "Lihat Semua Buku";
  }
  filter();
};

// Character count indicator
const resumeInputContent = document.getElementById("resumeInputContent");
const charCountEl = document.getElementById("charCount");
if (resumeInputContent && charCountEl) {
  resumeInputContent.addEventListener("input", function() {
    charCountEl.textContent = `${this.value.length} karakter`;
  });
}

function fillCategories(books) {
  if (!catEl) return;
  const defaults = ["Novel", "Cerpen", "Buku Pembelajaran"];
  const categories = [...new Set([...defaults, ...books.map(b => b.category).filter(Boolean)])];
  catEl.innerHTML = '<option value="">Semua Kategori / Genre</option>' + categories.map(c => `<option value="${c}">${c}</option>`).join("");
}

function filter() {
  const q = searchEl ? searchEl.value.toLowerCase().trim() : "";
  const c = catEl ? catEl.value : "";
  const l = litEl ? litEl.value : "";

  const filtered = allBooks.filter(b => {
    const inText = `${b.title} ${b.author}`.toLowerCase().includes(q);
    const inCategory = !c || b.category === c;

    let inLiteracy = true;
    if (l) {
      const bLevel = (b.literacy_level || "RENDAH").toUpperCase();
      if (l === "RENDAH") {
        inLiteracy = (bLevel === "RENDAH" || bLevel === "DASAR");
      } else if (l === "TINGGI") {
        inLiteracy = (bLevel === "TINGGI" || bLevel === "LANJUTAN");
      }
    }

    return inText && inCategory && inLiteracy;
  });

  render(filtered);
}

function getLocalResumes() {
  try {
    const stored = JSON.parse(localStorage.getItem("pantaubaca_student_resumes") || "[]");
    return Array.isArray(stored) ? stored.filter(r => !auth.userId || Number(r.user_id) === Number(auth.userId)) : [];
  } catch {
    return [];
  }
}

function saveLocalResume(bookId, content) {
  try {
    let list = JSON.parse(localStorage.getItem("pantaubaca_student_resumes") || "[]");
    const existingIdx = list.findIndex(r => Number(r.book_id) === Number(bookId) && Number(r.user_id) === Number(auth.userId));
    const book = allBooks.find(b => Number(b.id) === Number(bookId)) || { title: "Buku" };

    const resumeItem = {
      id: existingIdx >= 0 ? list[existingIdx].id : Date.now(),
      book_id: bookId,
      user_id: auth.userId || 1,
      user: { id: auth.userId || 1, name: auth.name || "Siswa" },
      book: { id: bookId, title: book.title },
      content,
      score: existingIdx >= 0 ? list[existingIdx].score : null,
      teacher_feedback: existingIdx >= 0 ? list[existingIdx].teacher_feedback : null,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    };

    if (existingIdx >= 0) {
      list[existingIdx] = resumeItem;
    } else {
      list.push(resumeItem);
    }
    localStorage.setItem("pantaubaca_student_resumes", JSON.stringify(list));
    myResumes = list.filter(r => !auth.userId || Number(r.user_id) === Number(auth.userId));
    return resumeItem;
  } catch (e) {
    console.error(e);
  }
}

function render(books) {
  const grid = document.getElementById("bookGrid");
  if (!grid) return;

  if (books.length === 0) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:48px 20px;color:var(--text-muted);">Tidak ada buku yang cocok dengan pencarian.</div>';
    return;
  }

  grid.innerHTML = books.map(b => {
    // Check if this student has a resume for this book
    const existingResume = myResumes.find(r => Number(r.book_id) === Number(b.id));
    const isGraded = existingResume && existingResume.score !== null && existingResume.score !== undefined;
    const isPending = existingResume && !isGraded;

    let statusBadgeHtml = "";
    if (isGraded) {
      statusBadgeHtml = `<div class="resume-status-badge badge-graded">⭐ Nilai: ${existingResume.score}/100</div>`;
    } else if (isPending) {
      statusBadgeHtml = `<div class="resume-status-badge badge-pending">⏳ Resume Terkirim (Menunggu Nilai)</div>`;
    }

    let resumeButtonHtml = "";
    if (auth.role === "SISWA") {
      resumeButtonHtml = `
        <button type="button" class="btn btn-primary" onclick="openResumeModal(${b.id})" title="Tulis atau edit resume buku ini">
          ${existingResume ? '✏️ Edit Resume' : '📝 Tulis Resume'}
        </button>
      `;
    } else if (auth.role === "GURU") {
      resumeButtonHtml = `
        <button type="button" class="btn btn-outline" disabled style="opacity:0.65;cursor:not-allowed;font-size:0.8rem;" title="Hanya siswa yang dapat membuat resume. Guru memberikan nilai di Dashboard Guru.">
          🔒 Khusus Siswa
        </button>
      `;
    }

    const isTinggi = (b.literacy_level === "TINGGI" || b.literacy_level === "LANJUTAN");
    const levelLabel = isTinggi ? "Kemampuan Lanjutan" : "Kemampuan Rendah";
    const levelTagBg = isTinggi ? "rgba(21, 128, 61, 0.88)" : "rgba(29, 78, 216, 0.88)";
    const levelIcon = isTinggi ? "🌟" : "🌱";

    return `
      <article class="card book-card">
        <div class="book-cover-wrap">
          <img class="book-cover" id="cover-img-${b.id}" src="${coverSrc(b.cover, b)}" alt="cover ${escapeHtml(b.title)}" onerror="handleCoverError(this, '${escapeHtml(b.title)}', '${escapeHtml(b.author)}', '${escapeHtml(b.category)}', '${escapeHtml(b.literacy_level)}')" />
          <span class="book-category-tag">${escapeHtml(b.category || "Umum")}</span>
          <span style="position:absolute;top:12px;right:12px;background:${levelTagBg};backdrop-filter:blur(4px);color:#FFFFFF;font-size:0.73rem;font-weight:800;padding:4px 9px;border-radius:999px;box-shadow:0 2px 6px rgba(0,0,0,0.15);">
            ${levelIcon} ${levelLabel}
          </span>
          ${statusBadgeHtml}
        </div>
        <div class="book-meta">
          <h3 class="book-title">${escapeHtml(b.title)}</h3>
          <p class="book-author">✍️ ${escapeHtml(b.author || "Penulis")}${b.year ? ` • 📅 ${escapeHtml(String(b.year))}` : ""}</p>
          <div class="book-actions">
            <a class="btn btn-outline" href="reader.html?id=${b.id}">📖 Baca</a>
            ${resumeButtonHtml}
          </div>
        </div>
      </article>
    `;
  }).join("");

  // Check and trigger background PDF.js cover rendering for any book without a saved cover
  setTimeout(() => {
    books.forEach(b => {
      if (!b.cover || b.cover.includes("data:image/svg+xml")) {
        ensureBookCoverRendered(b);
      }
    });
  }, 100);
}

const renderingBooks = new Set();
async function ensureBookCoverRendered(book) {
  if (!book || !book.id || renderingBooks.has(book.id)) return;
  renderingBooks.add(book.id);

  try {
    const imgEl = document.getElementById(`cover-img-${book.id}`);
    await renderBookCoverWithPdfJs(book, (dataUrl) => {
      if (imgEl) {
        imgEl.src = dataUrl;
      }
      book.cover = dataUrl;
    });
  } catch (e) {
    console.warn(`Cover render error for book ${book.id}:`, e);
  } finally {
    renderingBooks.delete(book.id);
  }
}

/* ==========================================================================
   Modal Resume Siswa
   ========================================================================== */
window.openResumeModal = function(bookId) {
  if (auth.role !== "SISWA") {
    showToast("Fitur resume buku hanya dapat dilakukan oleh siswa.", "warning");
    return;
  }

  const book = allBooks.find(b => Number(b.id) === Number(bookId));
  if (!book) return;

  activeBookId = bookId;

  const titleEl = document.getElementById("modalBookTitle");
  const authorEl = document.getElementById("modalBookAuthor");
  const contentEl = document.getElementById("resumeInputContent");
  const gradeBanner = document.getElementById("modalGradeBanner");
  const submitBtn = document.getElementById("saveResumeSubmitBtn");
  const charEl = document.getElementById("charCount");

  if (titleEl) titleEl.textContent = `Resume: ${book.title}`;
  if (authorEl) authorEl.textContent = `Penulis: ${book.author || '-'}${book.year ? ` (${book.year})` : ''} | Kategori: ${book.category || 'Umum'}`;

  // Find existing resume
  const existingResume = myResumes.find(r => Number(r.book_id) === Number(bookId));

  if (existingResume) {
    if (contentEl) contentEl.value = existingResume.content || "";
    if (charEl) charEl.textContent = `${(existingResume.content || "").length} karakter`;

    if (existingResume.score !== null && existingResume.score !== undefined) {
      if (gradeBanner) {
        gradeBanner.style.display = "block";
        const graderName = existingResume.grader ? existingResume.grader.name : "Guru Kelas";
        const dateStr = existingResume.graded_at ? new Date(existingResume.graded_at).toLocaleDateString('id-ID', { year:'numeric', month:'long', day:'numeric' }) : "";
        gradeBanner.innerHTML = `
          <div class="grade-feedback-box">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
              <strong style="color:#065F46;font-size:1.15rem;">🎉 Nilai dari Guru: ${existingResume.score} / 100</strong>
              <span style="font-size:0.8rem;color:#047857;font-weight:600;">Dinilai oleh: ${escapeHtml(graderName)} ${dateStr ? '• ' + dateStr : ''}</span>
            </div>
            ${existingResume.teacher_feedback ? `
              <div style="margin-top:8px;font-size:0.9rem;color:#064E3B;line-height:1.5;">
                <strong>Catatan Guru:</strong> "${escapeHtml(existingResume.teacher_feedback)}"
              </div>
            ` : ''}
          </div>
        `;
      }
      if (submitBtn) submitBtn.textContent = "💾 Perbarui Resume";
    } else {
      if (gradeBanner) {
        gradeBanner.style.display = "block";
        gradeBanner.innerHTML = `
          <div class="pending-status-box">
            <strong style="color:#92400E;display:block;margin-bottom:4px;">⏳ Resume Telah Dikirim — Menunggu Penilaian Guru</strong>
            <span style="font-size:0.84rem;color:#B45309;">
              Resume kamu sudah tersimpan di sistem dan dapat dilihat oleh guru untuk dinilai. Kamu masih bisa menyempurnakan isinya di bawah.
            </span>
          </div>
        `;
      }
      if (submitBtn) submitBtn.textContent = "💾 Perbarui Resume";
    }
  } else {
    if (contentEl) contentEl.value = "";
    if (charEl) charEl.textContent = "0 karakter";
    if (gradeBanner) {
      gradeBanner.style.display = "none";
      gradeBanner.innerHTML = "";
    }
    if (submitBtn) submitBtn.textContent = "🚀 Kirim Resume ke Guru";
  }

  const modal = document.getElementById("resumeModal");
  if (modal) modal.classList.add("active");
};

window.closeResumeModal = function() {
  const modal = document.getElementById("resumeModal");
  if (modal) modal.classList.remove("active");
  activeBookId = null;
};

window.submitResume = async function(event) {
  if (event) event.preventDefault();

  if (!activeBookId) return;

  const content = document.getElementById("resumeInputContent")?.value.trim();
  if (!content) {
    showToast("Isi resume tidak boleh kosong.", "error");
    return;
  }

  const submitBtn = document.getElementById("saveResumeSubmitBtn");
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "⏳ Mengirim...";
  }

  try {
    const res = await apiPost(`/books/${activeBookId}/resumes`, {
      content
    });

    showToast(res.message || "Resume berhasil dikirim ke guru!", "success");

    // Update in-memory resumes
    const updated = res.resume;
    if (updated) {
      const idx = myResumes.findIndex(r => Number(r.book_id) === Number(activeBookId));
      if (idx >= 0) {
        myResumes[idx] = { ...myResumes[idx], ...updated };
      } else {
        myResumes.push(updated);
      }
    }

    closeResumeModal();
    render(allBooks);
  } catch (err) {
    // Fallback to local storage in demo mode
    if (isNetworkError(err)) {
      saveLocalResume(activeBookId, content);
      showToast("[Demo Mode] Resume berhasil disimpan secara lokal!", "success");
      closeResumeModal();
      render(allBooks);
      return;
    }
    showToast(err.message || "Gagal menyimpan resume.", "error");
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = "🚀 Kirim Resume ke Guru";
    }
  }
};
