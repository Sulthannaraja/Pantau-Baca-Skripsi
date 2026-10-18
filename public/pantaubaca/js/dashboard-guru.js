if (!requireAuth(["GURU", "ADMIN"])) {
  throw new Error("Unauthorized");
}

const auth = getAuth();
const profileNameEl = document.getElementById("profileName");
if (profileNameEl && auth.name) {
  profileNameEl.textContent = auth.name;
  const avatar = document.querySelector(".header-profile-avatar");
  if (avatar) avatar.textContent = auth.name.charAt(0).toUpperCase();
}

let quizQuestions = [];
const addQuestionBtn = document.getElementById("addQuestionBtn");
const saveQuizBtn = document.getElementById("saveQuizBtn");
const questionType = document.getElementById("questionType");

if (addQuestionBtn) addQuestionBtn.addEventListener("click", addQuizQuestion);
if (saveQuizBtn) saveQuizBtn.addEventListener("click", saveQuiz);
if (questionType) {
  questionType.addEventListener("change", syncQuestionType);
  syncQuestionType();
}

let globalBooks = [];
let allStudentResumes = [];
let activeResumeId = null;

(async function () {
  let data = [];
  try {
    globalBooks = await apiGet("/books");
    await loadTeacherQuizzes();
    renderQuizBookOptions(globalBooks);
    renderTeacherBooksTable();
    await loadTeacherClassrooms();
    await loadStudentResumesForTeacher();
    await loadAllLiteracySurveys();
    await loadQuizGradesRecap();
    data = await apiGet("/reading/report");
  } catch (e) {
    if (!isNetworkError(e)) throw e;
    data = demoReadingReport();
    globalBooks = demoBooks();
    renderQuizBookOptions(globalBooks);
    renderTeacherBooksTable();
    await loadTeacherClassrooms();
    await loadStudentResumesForTeacher();
    await loadAllLiteracySurveys();
    await loadQuizGradesRecap();
    if (addQuestionBtn) addQuestionBtn.disabled = true;
    if (saveQuizBtn) saveQuizBtn.disabled = true;
    showInfo("Backend belum terhubung. Menampilkan data demo sementara.");
  }

  const byStudent = {};
  data.forEach(r => {
    const key = r.userId;
    if (!byStudent[key]) byStudent[key] = { name: r.userName, duration: 0, count: 0, progress: 0 };
    byStudent[key].duration += r.duration;
    byStudent[key].count += 1;
    byStudent[key].progress += r.progress;
  });

  const students = Object.values(byStudent);
  const avgDuration = students.length ? Math.round(students.reduce((a, s) => a + s.duration, 0) / students.length) : 0;

  const activeStudentsEl = document.getElementById("activeStudents");
  const bookReadsEl = document.getElementById("bookReads");
  const avgDurationEl = document.getElementById("avgDuration");

  if (activeStudentsEl) activeStudentsEl.textContent = students.length;
  if (bookReadsEl) bookReadsEl.textContent = globalBooks.length || data.length;
  if (avgDurationEl) avgDurationEl.textContent = `${avgDuration} menit`;

  const teacherTableBody = document.getElementById("teacherTableBody");
  if (teacherTableBody) {
    teacherTableBody.innerHTML = data.length ? data.map(r => `
      <tr>
        <td><strong>${escapeHtml(r.userName)}</strong></td>
        <td>${escapeHtml(r.bookTitle)}</td>
        <td>${r.duration} menit</td>
        <td>
          <div style="display:flex;align-items:center;gap:8px;">
            <div class="progress-wrap" style="width:70px;height:6px;">
              <div class="progress-bar" style="width:${Math.min(r.progress, 100)}%"></div>
            </div>
            <span>${r.progress}%</span>
          </div>
        </td>
        <td>
          <span style="font-size:0.8rem;padding:3px 8px;border-radius:12px;font-weight:600;background:${r.progress >= 70 ? '#DCFCE7;color:#15803D;' : '#FEF3C7;color:#92400E;'}">
            ${r.progress >= 70 ? 'Tinggi' : 'Dasar'}
          </span>
        </td>
      </tr>
    `).join("") : '<tr><td colspan="5" style="text-align:center;padding:16px;">Belum ada aktivitas membaca.</td></tr>';
  }
})();

/* ==========================================================================
   Penilaian Resume Siswa oleh Guru
   ========================================================================== */
async function loadStudentResumesForTeacher() {
  const tableBody = document.getElementById("studentResumesTableBody");
  if (!tableBody) return;

  try {
    const resumes = await apiGet("/resumes");
    if (Array.isArray(resumes)) {
      allStudentResumes = resumes;
    }
  } catch (err) {
    // Demo fallback storage
    try {
      allStudentResumes = JSON.parse(localStorage.getItem("pantaubaca_student_resumes") || "[]");
    } catch {
      allStudentResumes = [];
    }

    if (allStudentResumes.length === 0) {
      // Sample demo student resumes
      allStudentResumes = [
        {
          id: 101,
          user_id: 1,
          book_id: 1,
          user: { id: 1, name: "Aditya Pratama", email: "aditya@pantaubaca.com" },
          book: { id: 1, title: "Laskar Pelangi" },
          content: "Buku Laskar Pelangi menceritakan perjuangan sepuluh anak di Belitung yang memiliki tekad tinggi untuk terus bersekolah meskipun dalam keterbatasan fasilitas. Tokoh seperti Ikal dan Lintang menunjukkan semangat pantang menyerah. Nilai moralnya adalah pendidikan dan persahabatan mampu mengubah masa depan.",
          score: 92,
          teacher_feedback: "Ulasan sangat mendalam dan menangkap pesan moral cerita dengan sangat baik!",
          graded_at: new Date().toISOString()
        },
        {
          id: 102,
          user_id: 2,
          book_id: 2,
          user: { id: 2, name: "Dinda Rahmawati", email: "dinda@pantaubaca.com" },
          book: { id: 2, title: "Bumi Manusia" },
          content: "Buku ini menceritakan kisah Minke, seorang pribumi yang bersekolah di HBS Surabaya, dan pertemuannya dengan Annelies Mellema serta Nyai Ontosoroh. Cerita menggambarkan perjuangan melawan diskriminasi hukum kolonial.",
          score: null,
          teacher_feedback: null,
          graded_at: null
        }
      ];
    }
  }

  updateResumeOverviewCounts();
  renderStudentResumesTable(allStudentResumes);
}

function updateResumeOverviewCounts() {
  const pendingEl = document.getElementById("pendingResumesCount");
  const gradedEl = document.getElementById("gradedResumesCount");

  const pending = allStudentResumes.filter(r => r.score === null || r.score === undefined).length;
  const graded = allStudentResumes.filter(r => r.score !== null && r.score !== undefined).length;

  if (pendingEl) pendingEl.textContent = pending;
  if (gradedEl) gradedEl.textContent = graded;
}

function renderStudentResumesTable(resumes) {
  const tableBody = document.getElementById("studentResumesTableBody");
  if (!tableBody) return;

  if (!resumes.length) {
    tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text-muted);">Belum ada resume siswa yang dikirimkan. Siswa dapat meresume melalui halaman Katalog.</td></tr>';
    return;
  }

  tableBody.innerHTML = resumes.map((r, idx) => {
    const studentName = r.user ? r.user.name : "Siswa #" + r.user_id;
    const bookTitle = r.book ? r.book.title : (globalBooks.find(b => Number(b.id) === Number(r.book_id))?.title || "Buku #" + r.book_id);
    const dateStr = r.updated_at || r.created_at ? new Date(r.updated_at || r.created_at).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' }) : "-";
    const isGraded = r.score !== null && r.score !== undefined;
    const snippet = r.content && r.content.length > 70 ? r.content.substring(0, 70) + "..." : (r.content || "-");

    return `
      <tr>
        <td><strong>${idx + 1}</strong></td>
        <td>
          <div style="font-weight:700;color:var(--text-primary);">${escapeHtml(studentName)}</div>
          <small style="color:var(--text-muted);">${escapeHtml(r.user?.email || "")}</small>
        </td>
        <td><strong style="color:var(--primary);">${escapeHtml(bookTitle)}</strong></td>
        <td style="font-size:0.85rem;color:var(--text-secondary);">${dateStr}</td>
        <td style="max-width:280px;">
          <div style="font-size:0.88rem;color:#334155;line-height:1.4;">${escapeHtml(snippet)}</div>
        </td>
        <td>
          ${isGraded
            ? `<span class="badge-graded">⭐ ${r.score} / 100</span>`
            : `<span class="badge-pending">⏳ Belum Dinilai</span>`
          }
        </td>
        <td style="max-width:200px;font-size:0.85rem;color:var(--text-secondary);">
          ${r.teacher_feedback ? `<em>"${escapeHtml(r.teacher_feedback)}"</em>` : `<span style="color:var(--text-muted);">-</span>`}
        </td>
        <td>
          <button type="button" class="btn ${isGraded ? 'btn-outline' : 'btn-primary'} btn-sm" onclick="openGradeModal(${r.id})">
            ${isGraded ? '✏️ Ubah Nilai' : '⭐ Beri Nilai'}
          </button>
        </td>
      </tr>
    `;
  }).join("");
}

window.filterStudentResumes = function() {
  const filter = document.getElementById("resumeGradeStatusFilter")?.value || "ALL";
  if (filter === "ALL") {
    renderStudentResumesTable(allStudentResumes);
  } else if (filter === "PENDING") {
    renderStudentResumesTable(allStudentResumes.filter(r => r.score === null || r.score === undefined));
  } else if (filter === "GRADED") {
    renderStudentResumesTable(allStudentResumes.filter(r => r.score !== null && r.score !== undefined));
  }
};

/* ==========================================================================
   Modal Beri Nilai Resume
   ========================================================================== */
window.openGradeModal = function(resumeId) {
  const resume = allStudentResumes.find(r => Number(r.id) === Number(resumeId));
  if (!resume) return;

  activeResumeId = resumeId;

  const studentName = resume.user ? resume.user.name : "Siswa";
  const bookTitle = resume.book ? resume.book.title : (globalBooks.find(b => Number(b.id) === Number(resume.book_id))?.title || "Buku");

  const titleEl = document.getElementById("gradeModalTitle");
  const subtitleEl = document.getElementById("gradeModalSubtitle");
  const contentEl = document.getElementById("gradeModalContent");
  const scoreInput = document.getElementById("gradeScoreInput");
  const feedbackInput = document.getElementById("gradeFeedbackInput");

  if (titleEl) titleEl.textContent = `Penilaian Resume: ${studentName}`;
  if (subtitleEl) subtitleEl.textContent = `Buku: "${bookTitle}"`;
  if (contentEl) contentEl.textContent = resume.content || "(Teks resume kosong)";
  if (scoreInput) scoreInput.value = resume.score !== null && resume.score !== undefined ? resume.score : "";
  if (feedbackInput) feedbackInput.value = resume.teacher_feedback || "";

  const modal = document.getElementById("gradeModal");
  if (modal) modal.style.display = "flex";
};

window.closeGradeModal = function() {
  const modal = document.getElementById("gradeModal");
  if (modal) modal.style.display = "none";
  activeResumeId = null;
};

window.submitGradeResume = async function() {
  if (!activeResumeId) return;

  const scoreVal = document.getElementById("gradeScoreInput")?.value;
  const feedbackVal = document.getElementById("gradeFeedbackInput")?.value.trim();

  if (scoreVal === "" || isNaN(scoreVal)) {
    showToast("Masukkan nilai berupa angka (0 - 100).", "error");
    return;
  }

  const scoreNum = Number(scoreVal);
  if (scoreNum < 0 || scoreNum > 100) {
    showToast("Nilai harus berada dalam rentang 0 sampai 100.", "error");
    return;
  }

  const saveBtn = document.getElementById("saveGradeBtn");
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.textContent = "⏳ Menyimpan...";
  }

  try {
    const res = await apiPost(`/resumes/${activeResumeId}/grade`, {
      score: scoreNum,
      teacher_feedback: feedbackVal
    });

    showToast(res.message || "Nilai berhasil disimpan!", "success");

    // Update in allStudentResumes
    const targetIdx = allStudentResumes.findIndex(r => Number(r.id) === Number(activeResumeId));
    if (targetIdx >= 0) {
      allStudentResumes[targetIdx].score = scoreNum;
      allStudentResumes[targetIdx].teacher_feedback = feedbackVal;
      allStudentResumes[targetIdx].graded_at = new Date().toISOString();
    }

    // Also update localStorage demo storage if present
    try {
      const stored = JSON.parse(localStorage.getItem("pantaubaca_student_resumes") || "[]");
      const sIdx = stored.findIndex(r => Number(r.id) === Number(activeResumeId));
      if (sIdx >= 0) {
        stored[sIdx].score = scoreNum;
        stored[sIdx].teacher_feedback = feedbackVal;
        stored[sIdx].graded_at = new Date().toISOString();
        localStorage.setItem("pantaubaca_student_resumes", JSON.stringify(stored));
      }
    } catch {}

    closeGradeModal();
    updateResumeOverviewCounts();
    filterStudentResumes();
  } catch (err) {
    if (isNetworkError(err)) {
      // Local fallback
      const targetIdx = allStudentResumes.findIndex(r => Number(r.id) === Number(activeResumeId));
      if (targetIdx >= 0) {
        allStudentResumes[targetIdx].score = scoreNum;
        allStudentResumes[targetIdx].teacher_feedback = feedbackVal;
        allStudentResumes[targetIdx].graded_at = new Date().toISOString();
      }
      showToast("[Demo Mode] Nilai berhasil disimpan secara lokal!", "success");
      closeGradeModal();
      updateResumeOverviewCounts();
      filterStudentResumes();
      return;
    }
    showToast(err.message || "Gagal menyimpan nilai.", "error");
  } finally {
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.textContent = "💾 Simpan Nilai";
    }
  }
};

/* ==========================================================================
   Pengelolaan Kuis Pemahaman Buku oleh Guru
   ========================================================================== */
let globalQuizzes = [];
let currentQuizId = null;
let currentPreviewQuestionIndex = 0;

async function loadTeacherQuizzes() {
  try {
    const res = await apiGet("/quizzes");
    if (Array.isArray(res)) {
      globalQuizzes = res;
    }
  } catch (err) {
    console.warn("Gagal memuat daftar kuis guru:", err);
    globalQuizzes = [];
  }
}

function renderQuizBookOptions(books) {
  const select = document.getElementById("quizBook");
  if (!select) return;
  select.innerHTML = books.length
    ? books.map(book => `<option value="${book.id}">${escapeHtml(book.title)}</option>`).join("")
    : '<option value="">Belum ada buku</option>';

  onQuizBookSelected();
}

window.onQuizBookSelected = async function() {
  const select = document.getElementById("quizBook");
  const titleInput = document.getElementById("quizTitle");
  const descInput = document.getElementById("quizDescription");
  const statusSelect = document.getElementById("quizActiveStatus");
  const statusBadge = document.getElementById("quizStatusBadge");

  if (!select || !select.value) return;
  const bookId = Number(select.value);

  // Check if we already have this quiz in globalQuizzes
  let existingQuiz = globalQuizzes.find(q => Number(q.bookId) === bookId);

  if (existingQuiz) {
    currentQuizId = existingQuiz.quizId;
    if (titleInput) titleInput.value = existingQuiz.title || "";
    if (descInput) descInput.value = existingQuiz.description || "";
    if (statusSelect) statusSelect.value = existingQuiz.active ? "1" : "0";

    // Clone questions so local edits don't corrupt until saved
    quizQuestions.splice(0, quizQuestions.length, ...(existingQuiz.questions || []).map((q, idx) => ({
      questionId: q.questionId,
      type: q.type || "MCQ",
      question: q.question,
      options: Array.isArray(q.options) ? [...q.options] : [],
      answer: q.answer || "",
      points: Number(q.points || 20),
      order: Number(q.order || (idx + 1)),
      explanation: q.explanation || ""
    })));

    if (statusBadge) {
      if (existingQuiz.active) {
        statusBadge.className = "quiz-badge quiz-badge-completed";
        statusBadge.textContent = "🟢 Kuis Aktif (Terbit)";
      } else {
        statusBadge.className = "quiz-badge quiz-badge-inprogress";
        statusBadge.textContent = "🟡 Draf (Belum Terbit)";
      }
    }
  } else {
    currentQuizId = null;
    const selectedBook = globalBooks.find(b => Number(b.id) === bookId);
    if (titleInput) titleInput.value = selectedBook ? `Kuis Pemahaman: ${selectedBook.title}` : "";
    if (descInput) descInput.value = "Uji daya serap pemahaman bacaan setelah membaca buku ini secara tuntas.";
    if (statusSelect) statusSelect.value = "1";
    quizQuestions.splice(0, quizQuestions.length);

    if (statusBadge) {
      statusBadge.className = "quiz-badge quiz-badge-notstarted";
      statusBadge.textContent = "🆕 Kuis Baru (Belum Disimpan)";
    }
  }

  renderQuizQuestions();
};

function syncQuestionType() {
  const typeSelect = document.getElementById("questionType");
  const mcqBlock = document.getElementById("mcqOptionsBlock");
  const essayBlock = document.getElementById("essayOptionsBlock");
  if (!typeSelect || !mcqBlock || !essayBlock) return;

  const isMcq = typeSelect.value === "MCQ";
  mcqBlock.style.display = isMcq ? "block" : "none";
  essayBlock.style.display = isMcq ? "none" : "block";
}

window.syncEditQuestionType = function() {
  const typeSelect = document.getElementById("editQuestionType");
  const mcqBlock = document.getElementById("editMcqBlock");
  const essayBlock = document.getElementById("editEssayBlock");
  if (!typeSelect || !mcqBlock || !essayBlock) return;

  const isMcq = typeSelect.value === "MCQ";
  mcqBlock.style.display = isMcq ? "block" : "none";
  essayBlock.style.display = isMcq ? "none" : "block";
};

function addQuizQuestion() {
  const type = document.getElementById("questionType")?.value || "MCQ";
  const question = document.getElementById("questionText")?.value.trim() || "";
  const points = Math.max(1, Number(document.getElementById("questionPoints")?.value || 20));
  const explanation = document.getElementById("questionExplanation")?.value.trim() || "";

  if (!question) {
    showToast("Teks pertanyaan kuis tidak boleh kosong.", "error");
    return;
  }

  let options = [];
  let answer = "";

  if (type === "MCQ") {
    const optA = document.getElementById("optionA")?.value.trim() || "";
    const optB = document.getElementById("optionB")?.value.trim() || "";
    const optC = document.getElementById("optionC")?.value.trim() || "";
    const optD = document.getElementById("optionD")?.value.trim() || "";
    const optE = document.getElementById("optionE")?.value.trim() || "";

    const rawOptions = [optA, optB, optC, optD, optE];
    options = rawOptions.filter(Boolean);

    if (options.length < 2) {
      showToast("Pilihan ganda harus memiliki minimal 2 pilihan jawaban (misal A dan B).", "error");
      return;
    }

    const keySelect = document.getElementById("questionKeySelect")?.value;
    if (!keySelect) {
      showToast("Silakan tentukan kunci jawaban yang benar (A, B, C, D, atau E).", "error");
      return;
    }

    const keyMap = { A: optA, B: optB, C: optC, D: optD, E: optE };
    answer = keyMap[keySelect] || "";

    if (!answer) {
      showToast(`Pilihan ${keySelect} yang Anda jadikan kunci jawaban masih kosong. Silakan isi teks pilihannya.`, "error");
      return;
    }
  } else {
    answer = document.getElementById("essayKeyInput")?.value.trim() || "";
  }

  quizQuestions.push({
    type,
    question,
    options: type === "MCQ" ? options : null,
    answer: answer || undefined,
    points,
    order: quizQuestions.length + 1,
    explanation: explanation || undefined
  });

  // Reset form inputs
  document.getElementById("questionText").value = "";
  document.getElementById("questionPoints").value = "20";
  document.getElementById("questionExplanation").value = "";
  if (type === "MCQ") {
    document.getElementById("optionA").value = "";
    document.getElementById("optionB").value = "";
    document.getElementById("optionC").value = "";
    document.getElementById("optionD").value = "";
    document.getElementById("optionE").value = "";
    document.getElementById("questionKeySelect").value = "";
  } else {
    const essayInput = document.getElementById("essayKeyInput");
    if (essayInput) essayInput.value = "";
  }

  renderQuizQuestions();
  showToast("Soal berhasil ditambahkan ke daftar kuis!", "success");
}

function renderQuizQuestions() {
  const body = document.getElementById("quizQuestionBody");
  const countEl = document.getElementById("quizTotalQuestionsCount");
  const pointsEl = document.getElementById("quizTotalPointsCount");
  const tableCounterEl = document.getElementById("tableQuestionCounter");

  const totalPoints = quizQuestions.reduce((acc, q) => acc + Number(q.points || 0), 0);
  if (countEl) countEl.textContent = quizQuestions.length;
  if (pointsEl) pointsEl.textContent = totalPoints;
  if (tableCounterEl) tableCounterEl.textContent = quizQuestions.length;

  if (!body) return;

  if (quizQuestions.length === 0) {
    body.innerHTML = `
      <tr>
        <td colspan="5" style="text-align:center;padding:28px 16px;color:var(--text-muted);">
          <div style="font-size:2rem;margin-bottom:6px;">📝</div>
          <p style="font-weight:700;color:var(--text-secondary);margin-bottom:4px;">Belum ada soal pada kuis ini.</p>
          <p style="font-size:0.86rem;color:var(--text-muted);margin:0;">Gunakan formulir "Tambah Soal Baru" di atas untuk menambahkan pertanyaan.</p>
        </td>
      </tr>
    `;
    return;
  }

  body.innerHTML = quizQuestions.map((q, idx) => {
    const isFirst = idx === 0;
    const isLast = idx === quizQuestions.length - 1;
    const isMcq = q.type === "MCQ";
    const options = Array.isArray(q.options) ? q.options : [];

    const optionsSnippet = isMcq
      ? `<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
          ${options.map((opt, oIdx) => {
            const letter = String.fromCharCode(65 + oIdx);
            const isAnswer = opt && q.answer && strEquals(opt, q.answer);
            return `
              <span style="font-size:0.78rem;padding:2px 8px;border-radius:6px;font-weight:600;display:inline-flex;align-items:center;gap:4px;
                ${isAnswer ? 'background:#DCFCE7;color:#15803D;border:1px solid #86EFAC;' : 'background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;'}">
                <strong>${letter}.</strong> ${escapeHtml(opt)} ${isAnswer ? '✓ (Kunci)' : ''}
              </span>
            `;
          }).join("")}
        </div>`
      : `<div style="font-size:0.82rem;color:var(--text-secondary);margin-top:4px;"><em>Pedoman/Kunci: ${escapeHtml(q.answer || '-')}</em></div>`;

    const explanationBadge = q.explanation
      ? `<div style="font-size:0.78rem;color:#92400E;background:#FEF3C7;padding:2px 8px;border-radius:6px;margin-top:4px;display:inline-block;">
          💡 Pembahasan: ${escapeHtml(q.explanation.length > 50 ? q.explanation.substring(0, 50) + '...' : q.explanation)}
        </div>`
      : '';

    return `
      <tr>
        <td style="text-align:center;">
          <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
            <strong style="font-size:0.95rem;color:var(--primary);">${idx + 1}</strong>
            <div style="display:flex;flex-direction:column;gap:2px;">
              <button type="button" class="btn-resizer" onclick="moveQuizQuestion(${idx}, -1)" ${isFirst ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : ''} title="Geser Naik" style="padding:1px 5px;font-size:0.7rem;">▲</button>
              <button type="button" class="btn-resizer" onclick="moveQuizQuestion(${idx}, 1)" ${isLast ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : ''} title="Geser Turun" style="padding:1px 5px;font-size:0.7rem;">▼</button>
            </div>
          </div>
        </td>
        <td>
          <div style="font-weight:700;color:#0F172A;font-size:0.95rem;line-height:1.5;">${escapeHtml(q.question)}</div>
          ${optionsSnippet}
          ${explanationBadge}
        </td>
        <td style="text-align:center;">
          <span style="font-size:0.8rem;padding:3px 10px;border-radius:12px;font-weight:700;background:${isMcq ? '#EEF2FF;color:#4361EE;' : '#F5F3FF;color:#7C3AED;'}">
            ${isMcq ? 'Pilihan Ganda' : 'Esai'}
          </span>
        </td>
        <td style="text-align:center;">
          <span style="font-weight:800;color:#0F172A;font-size:0.95rem;">${q.points}</span>
        </td>
        <td style="text-align:center;">
          <div style="display:flex;gap:6px;justify-content:center;">
            <button type="button" class="btn btn-outline btn-sm" onclick="openEditQuestionModal(${idx})" style="padding:4px 10px;font-size:0.82rem;" title="Edit Soal">
              ✏️ Edit
            </button>
            <button type="button" class="btn btn-outline btn-sm" onclick="openDeleteQuestionModal(${idx})" style="padding:4px 10px;font-size:0.82rem;color:#DC2626;border-color:#FCA5A5;" title="Hapus Soal">
              🗑️ Hapus
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join("");
}

function strEquals(a, b) {
  return String(a || "").trim().toLowerCase() === String(b || "").trim().toLowerCase();
}

window.moveQuizQuestion = function(idx, direction) {
  const targetIdx = idx + direction;
  if (targetIdx < 0 || targetIdx >= quizQuestions.length) return;

  const temp = quizQuestions[idx];
  quizQuestions[idx] = quizQuestions[targetIdx];
  quizQuestions[targetIdx] = temp;

  // re-assign order
  quizQuestions.forEach((q, i) => { q.order = i + 1; });
  renderQuizQuestions();
};

/* Modal Edit Soal */
window.openEditQuestionModal = function(idx) {
  const q = quizQuestions[idx];
  if (!q) return;

  document.getElementById("editQuestionIndex").value = idx;
  document.getElementById("editQuestionModalTitle").textContent = `✏️ Edit Soal #${idx + 1}`;
  document.getElementById("editQuestionType").value = q.type || "MCQ";
  document.getElementById("editQuestionPoints").value = q.points || 20;
  document.getElementById("editQuestionText").value = q.question || "";
  document.getElementById("editQuestionExplanation").value = q.explanation || "";

  syncEditQuestionType();

  if (q.type === "MCQ") {
    const opts = Array.isArray(q.options) ? q.options : [];
    document.getElementById("editOptionA").value = opts[0] || "";
    document.getElementById("editOptionB").value = opts[1] || "";
    document.getElementById("editOptionC").value = opts[2] || "";
    document.getElementById("editOptionD").value = opts[3] || "";
    document.getElementById("editOptionE").value = opts[4] || "";

    // determine which letter matches the answer
    let matchingLetter = "";
    opts.forEach((opt, oIdx) => {
      if (opt && q.answer && strEquals(opt, q.answer)) {
        matchingLetter = String.fromCharCode(65 + oIdx);
      }
    });
    document.getElementById("editQuestionKeySelect").value = matchingLetter;
  } else {
    document.getElementById("editEssayKeyInput").value = q.answer || "";
  }

  const modal = document.getElementById("editQuestionModal");
  if (modal) modal.classList.add("active");
};

window.closeEditQuestionModal = function() {
  const modal = document.getElementById("editQuestionModal");
  if (modal) modal.classList.remove("active");
};

window.submitEditQuestion = function() {
  const idx = Number(document.getElementById("editQuestionIndex").value);
  if (idx < 0 || idx >= quizQuestions.length) return;

  const type = document.getElementById("editQuestionType").value;
  const question = document.getElementById("editQuestionText").value.trim();
  const points = Math.max(1, Number(document.getElementById("editQuestionPoints").value || 20));
  const explanation = document.getElementById("editQuestionExplanation").value.trim();

  if (!question) {
    showToast("Teks pertanyaan tidak boleh kosong.", "error");
    return;
  }

  let options = [];
  let answer = "";

  if (type === "MCQ") {
    const optA = document.getElementById("editOptionA").value.trim();
    const optB = document.getElementById("editOptionB").value.trim();
    const optC = document.getElementById("editOptionC").value.trim();
    const optD = document.getElementById("editOptionD").value.trim();
    const optE = document.getElementById("editOptionE").value.trim();

    options = [optA, optB, optC, optD, optE].filter(Boolean);
    if (options.length < 2) {
      showToast("Pilihan ganda harus memiliki minimal 2 pilihan jawaban.", "error");
      return;
    }

    const keySelect = document.getElementById("editQuestionKeySelect").value;
    if (!keySelect) {
      showToast("Pilih kunci jawaban yang benar.", "error");
      return;
    }

    const keyMap = { A: optA, B: optB, C: optC, D: optD, E: optE };
    answer = keyMap[keySelect] || "";
    if (!answer) {
      showToast(`Pilihan ${keySelect} masih kosong.`, "error");
      return;
    }
  } else {
    answer = document.getElementById("editEssayKeyInput").value.trim();
  }

  quizQuestions[idx] = {
    ...quizQuestions[idx],
    type,
    question,
    options: type === "MCQ" ? options : null,
    answer: answer || undefined,
    points,
    explanation: explanation || undefined
  };

  closeEditQuestionModal();
  renderQuizQuestions();
  showToast("Perubahan soal berhasil disimpan!", "success");
};

/* Modal Hapus Soal */
window.openDeleteQuestionModal = function(idx) {
  const q = quizQuestions[idx];
  if (!q) return;

  document.getElementById("deleteQuestionIndex").value = idx;
  const numBadge = document.getElementById("deleteQuestionNumberBadge");
  const box = document.getElementById("deleteQuestionSummaryBox");

  if (numBadge) numBadge.textContent = `Soal #${idx + 1}`;
  if (box) box.textContent = q.question;

  const modal = document.getElementById("deleteQuestionModal");
  if (modal) modal.classList.add("active");
};

window.closeDeleteQuestionModal = function() {
  const modal = document.getElementById("deleteQuestionModal");
  if (modal) modal.classList.remove("active");
};

window.confirmDeleteQuestion = function() {
  const idx = Number(document.getElementById("deleteQuestionIndex").value);
  if (idx >= 0 && idx < quizQuestions.length) {
    quizQuestions.splice(idx, 1);
    quizQuestions.forEach((q, i) => { q.order = i + 1; });
    closeDeleteQuestionModal();
    renderQuizQuestions();
    showToast("Soal berhasil dihapus dari kuis.", "success");
  }
};

/* Pratinjau Kuis Guru (Tampilan Siswa) */
window.openQuizPreviewModal = function() {
  if (quizQuestions.length === 0) {
    showToast("Tambahkan minimal 1 pertanyaan terlebih dahulu sebelum pratinjau.", "warning");
    return;
  }

  currentPreviewQuestionIndex = 0;
  const title = document.getElementById("quizTitle")?.value.trim() || "Kuis Pemahaman Buku";
  const desc = document.getElementById("quizDescription")?.value.trim() || "";

  document.getElementById("previewModalQuizTitle").textContent = title;
  document.getElementById("previewModalQuizSubtitle").textContent = desc || "Pratinjau tampilan pengerjaan kuis siswa.";

  renderQuizPreviewQuestion();

  const modal = document.getElementById("previewQuizModal");
  if (modal) modal.classList.add("active");
};

window.closeQuizPreviewModal = function() {
  const modal = document.getElementById("previewQuizModal");
  if (modal) modal.classList.remove("active");
};

function renderQuizPreviewQuestion() {
  const container = document.getElementById("previewQuizBody");
  if (!container || quizQuestions.length === 0) return;

  const q = quizQuestions[currentPreviewQuestionIndex];
  const total = quizQuestions.length;
  const percent = Math.round(((currentPreviewQuestionIndex + 1) / total) * 100);
  const isMcq = q.type === "MCQ";
  const options = Array.isArray(q.options) ? q.options : [];

  container.innerHTML = `
    <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:16px;padding:16px 20px;margin-bottom:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.86rem;font-weight:700;color:#334155;margin-bottom:8px;">
        <span>Soal ${currentPreviewQuestionIndex + 1} dari ${total}</span>
        <span class="quiz-timer-pill" style="padding:3px 12px;font-size:0.8rem;">⏱️ 15:00 (Simulasi Timer)</span>
      </div>
      <div class="quiz-progress-bar-container">
        <div class="quiz-progress-bar-active" style="width:${percent}%"></div>
      </div>
      <div class="quiz-nav-pills-row" style="margin-top:12px;">
        ${quizQuestions.map((_, i) => `
          <button type="button" class="quiz-nav-pill-btn ${i === currentPreviewQuestionIndex ? 'current' : ''}" onclick="gotoPreviewQuestion(${i})">
            ${i + 1}
          </button>
        `).join("")}
      </div>
    </div>

    <div class="quiz-active-card" style="margin-top:0;">
      <div class="quiz-question-meta-row">
        <span class="quiz-badge quiz-badge-notstarted">Soal #${currentPreviewQuestionIndex + 1}</span>
        <span style="font-size:0.82rem;font-weight:700;color:var(--text-secondary);">${q.points} Poin &bull; ${isMcq ? 'Pilihan Ganda' : 'Esai'}</span>
      </div>

      <div class="quiz-active-question-text">${escapeHtml(q.question)}</div>

      ${isMcq ? `
        <div class="quiz-options-container">
          ${options.map((opt, oIdx) => {
            const letter = String.fromCharCode(65 + oIdx);
            return `
              <label class="quiz-option-item">
                <input type="radio" name="preview_choice" class="quiz-option-radio-input" />
                <span class="quiz-option-badge-letter">${letter}</span>
                <span class="quiz-option-item-text">${escapeHtml(opt)}</span>
              </label>
            `;
          }).join("")}
        </div>
      ` : `
        <textarea class="quiz-essay-textarea" placeholder="Siswa akan menuliskan jawaban esai di sini..."></textarea>
      `}
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;">
      <button type="button" class="btn btn-outline" onclick="gotoPreviewQuestion(${currentPreviewQuestionIndex - 1})" ${currentPreviewQuestionIndex === 0 ? 'disabled style="opacity:0.4;"' : ''}>
        &larr; Sebelumnya
      </button>
      <span style="font-size:0.84rem;color:#10B981;font-weight:600;">✓ Jawaban tersimpan otomatis</span>
      <button type="button" class="btn btn-primary" onclick="gotoPreviewQuestion(${currentPreviewQuestionIndex + 1})" ${currentPreviewQuestionIndex === total - 1 ? 'disabled style="opacity:0.4;"' : ''}>
        Berikutnya &rarr;
      </button>
    </div>
  `;
}

window.gotoPreviewQuestion = function(i) {
  if (i >= 0 && i < quizQuestions.length) {
    currentPreviewQuestionIndex = i;
    renderQuizPreviewQuestion();
  }
};

/* Simpan Kuis Guru ke Database */
async function saveQuiz() {
  const bookId = Number(document.getElementById("quizBook")?.value);
  const title = document.getElementById("quizTitle")?.value.trim();
  const description = document.getElementById("quizDescription")?.value.trim();
  const activeStatus = document.getElementById("quizActiveStatus")?.value === "1";

  if (!bookId) {
    showToast("Silakan pilih buku terlebih dahulu.", "error");
    return;
  }
  if (!title) {
    showToast("Judul kuis wajib diisi.", "error");
    return;
  }
  if (quizQuestions.length === 0) {
    showToast("Tambahkan minimal satu pertanyaan sebelum menyimpan kuis.", "error");
    return;
  }

  const saveBtn = document.getElementById("saveQuizBtn");
  const saveBtnBottom = document.getElementById("saveQuizBottomBtn");
  if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = "Menyimpan..."; }
  if (saveBtnBottom) { saveBtnBottom.disabled = true; saveBtnBottom.textContent = "Menyimpan..."; }

  try {
    const payload = {
      bookId,
      title,
      description,
      active: activeStatus,
      questions: quizQuestions.map((q, idx) => ({
        type: q.type,
        question: q.question,
        options: q.type === "MCQ" ? q.options : null,
        answer: q.answer || undefined,
        points: q.points || 1,
        order: idx + 1,
        explanation: q.explanation || undefined
      }))
    };

    let res;
    if (currentQuizId) {
      res = await apiPatch(`/quizzes/${currentQuizId}`, payload);
    } else {
      res = await apiPost("/quizzes", payload);
      if (res.quiz && res.quiz.quizId) {
        currentQuizId = res.quiz.quizId;
      }
    }

    // Refresh teacher quizzes in background
    await loadTeacherQuizzes();

    // Update status badge
    const statusBadge = document.getElementById("quizStatusBadge");
    if (statusBadge) {
      if (activeStatus) {
        statusBadge.className = "quiz-badge quiz-badge-completed";
        statusBadge.textContent = "🟢 Kuis Aktif (Terbit)";
      } else {
        statusBadge.className = "quiz-badge quiz-badge-inprogress";
        statusBadge.textContent = "🟡 Draf (Belum Terbit)";
      }
    }

    showToast(res.message || "Kuis berhasil disimpan ke database!", "success");
  } catch (error) {
    showToast(error.message || "Gagal menyimpan kuis.", "error");
  } finally {
    if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = "💾 Simpan Kuis & Perubahan"; }
    if (saveBtnBottom) { saveBtnBottom.disabled = false; saveBtnBottom.textContent = "💾 Simpan Kuis & Perubahan"; }
  }
}

/* ==========================================================================
   Hasil Angket Literasi Digital Siswa
   ========================================================================== */
let allSurveysList = [];

async function loadAllLiteracySurveys() {
  const tableBody = document.getElementById("surveyTableBody");
  if (!tableBody) return;

  try {
    const surveys = await apiGet("/literacy-surveys");
    if (Array.isArray(surveys)) {
      allSurveysList = surveys;
    }
  } catch (e) {
    allSurveysList = [
      { id: 1, user: { name: "Aditya Pratama", email: "aditya@pantaubaca.com" }, total_score: 35, level: "TINGGI", created_at: new Date().toISOString() },
      { id: 2, user: { name: "Dinda Rahmawati", email: "dinda@pantaubaca.com" }, total_score: 23, level: "RENDAH", created_at: new Date().toISOString() },
      { id: 3, user: { name: "Fajar Nugraha", email: "fajar@pantaubaca.com" }, total_score: 37, level: "TINGGI", created_at: new Date().toISOString() },
      { id: 4, user: { name: "Siti Nurhaliza", email: "siti@pantaubaca.com" }, total_score: 23, level: "RENDAH", created_at: new Date().toISOString() }
    ];
  }

  renderSurveysTable(allSurveysList);
}

function renderSurveysTable(list) {
  const tableBody = document.getElementById("surveyTableBody");
  const totalStudentsEl = document.getElementById("surveyTotalStudents");
  const dasarCountEl = document.getElementById("surveyDasarCount");
  const lanjutanCountEl = document.getElementById("surveyLanjutanCount");
  const avgScoreEl = document.getElementById("surveyAvgScore");
  const overviewDasarEl = document.getElementById("overviewDasarCount");
  const overviewLanjutanEl = document.getElementById("overviewLanjutanCount");

  const total = allSurveysList.length;
  const dasarCount = allSurveysList.filter(s => s.level === "RENDAH").length;
  const lanjutanCount = allSurveysList.filter(s => s.level === "TINGGI").length;
  const totalScoreSum = allSurveysList.reduce((acc, s) => acc + Number(s.total_score || 0), 0);
  const avgScore = total > 0 ? (totalScoreSum / total).toFixed(1) : 0;

  if (totalStudentsEl) totalStudentsEl.textContent = total;
  if (dasarCountEl) dasarCountEl.textContent = dasarCount;
  if (lanjutanCountEl) lanjutanCountEl.textContent = lanjutanCount;
  if (avgScoreEl) avgScoreEl.textContent = `${avgScore}/40`;
  if (overviewDasarEl) overviewDasarEl.textContent = dasarCount;
  if (overviewLanjutanEl) overviewLanjutanEl.textContent = lanjutanCount;

  if (!tableBody) return;

  if (list.length === 0) {
    tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted);">Belum ada siswa yang mengisi angket literasi digital.</td></tr>';
    return;
  }

  tableBody.innerHTML = list.map((s, idx) => {
    const isHigh = s.level === "TINGGI";
    const studentName = s.user ? s.user.name : "Siswa #" + s.user_id;
    const studentEmail = s.user ? s.user.email : "-";
    const percent = Math.round((s.total_score / 40) * 100);
    const dateStr = s.created_at ? new Date(s.created_at).toLocaleDateString('id-ID', { year:'numeric', month:'short', day:'numeric' }) : "-";

    return `
      <tr>
        <td><strong>${idx + 1}</strong></td>
        <td><strong style="color:var(--text-primary);">${escapeHtml(studentName)}</strong></td>
        <td style="color:var(--text-muted);font-size:0.88rem;">${escapeHtml(studentEmail)}</td>
        <td>
          <span style="font-weight:800;font-size:1rem;color:${isHigh ? '#065F46' : '#92400E'};">
            ${s.total_score}/40
          </span>
        </td>
        <td>
          <div style="display:flex;align-items:center;gap:6px;">
            <div class="progress-wrap" style="width:60px;height:6px;">
              <div class="progress-bar" style="width:${percent}%;background:${isHigh ? '#10B981' : '#F59E0B'};"></div>
            </div>
            <span style="font-size:0.84rem;font-weight:700;">${percent}%</span>
          </div>
        </td>
        <td>
          <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:800;background:${isHigh ? '#D1FAE5' : '#FEF3C7'};color:${isHigh ? '#065F46' : '#92400E'};">
            ${isHigh ? '🌟 Tinggi' : '📊 Rendah'}
          </span>
        </td>
        <td style="font-size:0.84rem;color:var(--text-muted);">${dateStr}</td>
      </tr>
    `;
  }).join("");
}

window.filterSurveyTable = function() {
  const filter = document.getElementById("surveyFilterCategory")?.value || "ALL";
  if (filter === "ALL") {
    renderSurveysTable(allSurveysList);
  } else {
    renderSurveysTable(allSurveysList.filter(s => s.level === filter));
  }
};

/* ==========================================================================
   Kelola & Input Buku oleh Guru
   ========================================================================== */
window.teacherUploadBook = async function() {
  const fileInput = document.getElementById("teacherBookFile");
  const title = document.getElementById("teacherBookTitle")?.value.trim();
  const author = document.getElementById("teacherBookAuthor")?.value.trim();
  const year = document.getElementById("teacherBookYear")?.value.trim();
  const category = document.getElementById("teacherBookCategory")?.value;
  const literacyLevel = document.getElementById("teacherBookLiteracyLevel")?.value;
  const cover = document.getElementById("teacherBookCover")?.value.trim();
  const file = fileInput?.files[0];
  const uploadBtn = document.getElementById("teacherUploadBtn");

  if (!title || !author || !literacyLevel || !file) {
    showToast("Judul buku, penulis, kategori kemampuan literasi, dan file PDF wajib diisi.", "error");
    return;
  }

  if (file.type !== "application/pdf" && !file.name.toLowerCase().endsWith(".pdf")) {
    showToast("File harus berupa dokumen PDF.", "error");
    return;
  }

  try {
    if (uploadBtn) {
      uploadBtn.disabled = true;
      uploadBtn.textContent = "⏳ Mengunggah...";
    }

    const form = new FormData();
    form.append("title", title);
    form.append("author", author);
    if (year) {
      form.append("year", year);
    }
    form.append("category", category);
    form.append("literacy_level", literacyLevel);

    if (cover) {
      form.append("cover", cover);
    } else if (window.pdfjsLib && file) {
      try {
        pdfjsLib.GlobalWorkerOptions.workerSrc = "/pantaubaca/js/pdf.worker.min.js";
        const arrayBuffer = await file.arrayBuffer();
        const pdfDoc = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        const page = await pdfDoc.getPage(1);
        const viewport = page.getViewport({ scale: 1.5 });
        const canvas = document.createElement("canvas");
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        const ctx = canvas.getContext("2d");
        await page.render({ canvasContext: ctx, viewport }).promise;
        const dataUrl = canvas.toDataURL("image/jpeg", 0.90);
        form.append("cover_data", dataUrl);
      } catch (pdfErr) {
        console.warn("Could not generate PDF cover during upload:", pdfErr);
      }
    }

    form.append("file", file);

    await apiPost("/books/upload", form, true);
    showToast(`Buku "${title}" berhasil diunggah!`, "success");

    // Reset form
    document.getElementById("teacherBookTitle").value = "";
    document.getElementById("teacherBookAuthor").value = "";
    if (document.getElementById("teacherBookYear")) {
      document.getElementById("teacherBookYear").value = "";
    }
    document.getElementById("teacherBookCover").value = "";
    if (document.getElementById("teacherBookLiteracyLevel")) {
      document.getElementById("teacherBookLiteracyLevel").value = "";
    }
    if (fileInput) fileInput.value = "";

    // Reload books
    globalBooks = await apiGet("/books");
    renderQuizBookOptions(globalBooks);
    renderTeacherBooksTable();
  } catch (e) {
    showToast(e.message || "Gagal mengunggah buku.", "error");
  } finally {
    if (uploadBtn) {
      uploadBtn.disabled = false;
      uploadBtn.textContent = "🚀 Simpan & Upload Buku";
    }
  }
};

window.deleteTeacherBook = async function(bookId, bookTitle) {
  const ok = await appConfirm(`Hapus buku "${bookTitle}" beserta semua data riwayat bacaannya?`, { isDanger: true, confirmText: "Hapus" });
  if (!ok) return;

  try {
    await apiDelete(`/books/${bookId}`);
    showToast(`Buku "${bookTitle}" berhasil dihapus.`, "success");
    globalBooks = await apiGet("/books");
    renderQuizBookOptions(globalBooks);
    renderTeacherBooksTable();
  } catch (e) {
    showToast(e.message || "Gagal menghapus buku.", "error");
  }
};

function renderTeacherBooksTable() {
  const tableBody = document.getElementById("teacherBookTableBody");
  if (!tableBody) return;

  if (!globalBooks.length) {
    tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:16px;color:var(--text-muted);">Belum ada buku di perpustakaan. Silakan upload buku pertama di atas!</td></tr>';
    return;
  }

  tableBody.innerHTML = globalBooks.map((b, idx) => {
    const isTinggi = (b.literacy_level === "TINGGI" || b.literacy_level === "LANJUTAN");
    const levelLabel = isTinggi ? "Buku Kemampuan Lanjutan" : "Buku Kemampuan Rendah";
    const levelBadgeBg = isTinggi ? "#DCFCE7" : "#DBEAFE";
    const levelBadgeColor = isTinggi ? "#15803D" : "#1D4ED8";
    const levelIcon = isTinggi ? "🌟" : "🌱";

    return `
      <tr>
        <td><strong>${idx + 1}</strong></td>
        <td>
          <div style="font-weight:700;color:var(--text-primary);">${escapeHtml(b.title)}</div>
        </td>
        <td>${escapeHtml(b.author || "-")}</td>
        <td>
          <span style="font-weight:600;color:var(--text-secondary);font-size:0.86rem;">
            ${escapeHtml(b.year ? String(b.year) : "-")}
          </span>
        </td>
        <td>
          <span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:0.8rem;background:#EEF2FF;color:var(--primary);font-weight:600;">
            ${escapeHtml(b.category || "Umum")}
          </span>
        </td>
        <td>
          <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:0.8rem;background:${levelBadgeBg};color:${levelBadgeColor};font-weight:700;">
            ${levelIcon} ${levelLabel}
          </span>
        </td>
        <td>
          <button type="button" class="btn btn-outline btn-sm" onclick="deleteTeacherBook(${b.id}, '${escapeHtml(b.title)}')" style="color:#DC2626;border-color:#FCA5A5;">
            Hapus
          </button>
        </td>
      </tr>
    `;
  }).join("");
}

/* ==========================================================================
   Kelola Kelas Guru
   ========================================================================== */
let teacherClassrooms = [];

async function loadTeacherClassrooms() {
  const container = document.getElementById("teacherClassList");
  const totalClassesEl = document.getElementById("totalClasses");
  const totalStudentsEl = document.getElementById("totalStudents");

  try {
    const list = await apiGet("/classrooms");
    teacherClassrooms = Array.isArray(list) ? list : [];
  } catch (err) {
    teacherClassrooms = [];
  }

  const classCount = teacherClassrooms.length;
  let totalStudentCount = 0;
  teacherClassrooms.forEach(c => {
    totalStudentCount += Number(c.users_count || (c.users ? c.users.length : 0));
  });

  if (totalClassesEl) totalClassesEl.textContent = classCount;
  if (totalStudentsEl) totalStudentsEl.textContent = totalStudentCount;

  if (!container) return;

  if (teacherClassrooms.length === 0) {
    container.innerHTML = `
      <div style="text-align:center;padding:32px 16px;background:#F8FAFC;border:1.5px dashed #CBD5E1;border-radius:12px;">
        <div style="font-size:2rem;margin-bottom:8px;">🏫</div>
        <p style="font-weight:700;color:var(--text-secondary);margin-bottom:4px;">Belum ada kelas yang Anda kelola.</p>
        <p style="font-size:0.86rem;color:var(--text-muted);margin-bottom:12px;">Klik tombol "+ Buat Kelas Baru" di atas untuk membuat kelas dan membagikan kode kelas ke siswa Anda.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = teacherClassrooms.map(c => {
    const studentCount = Number(c.users_count || (c.users ? c.users.length : 0));
    return `
      <div class="class-card">
        <div>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <h3 style="font-size:1.15rem;font-weight:800;color:var(--primary);margin:0;">${escapeHtml(c.name)}</h3>
            <span class="class-badge" style="cursor:pointer;" onclick="navigator.clipboard.writeText('${escapeHtml(c.code)}'); showToast('Kode kelas disalin ke clipboard!', 'success');" title="Klik untuk salin kode kelas">
              Kode: <strong>${escapeHtml(c.code)}</strong> 📋
            </span>
          </div>
          <div style="font-size:0.86rem;color:var(--text-secondary);">
            👥 <strong>${studentCount} Siswa</strong> bergabung
          </div>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText('${escapeHtml(c.code)}'); showToast('Kode kelas disalin!', 'success');">
            Salin Kode
          </button>
          <button type="button" class="btn btn-outline btn-sm" onclick="deleteClass(${c.id}, '${escapeHtml(c.name)}')" style="color:#DC2626;border-color:#FCA5A5;">
            Hapus
          </button>
        </div>
      </div>
    `;
  }).join("");
}

window.createClass = async function() {
  const name = await appPrompt("Nama Kelas Baru", "Contoh: Kelas 10-A Bahasa Indonesia");
  if (!name || !name.trim()) return;

  try {
    const res = await apiPost("/classrooms", { name: name.trim() });
    showToast(res.message || "Kelas berhasil dibuat!", "success");
    await loadTeacherClassrooms();
  } catch (err) {
    showToast(err.message || "Gagal membuat kelas.", "error");
  }
};

window.deleteClass = async function(classId, className) {
  const ok = await appConfirm(`Hapus kelas "${className}"? Siswa yang bergabung akan terlepas dari kelas ini.`, { isDanger: true, confirmText: "Hapus" });
  if (!ok) return;

  try {
    await apiDelete(`/classrooms/${classId}`);
    showToast(`Kelas "${className}" berhasil dihapus.`, "success");
    await loadTeacherClassrooms();
  } catch (err) {
    showToast(err.message || "Gagal menghapus kelas.", "error");
  }
};

/* ==========================================================================
   Rekap Nilai Kuis Pemahaman Siswa
   ========================================================================== */
let allRecapItems = [];
let recapSummaryData = null;

async function loadQuizGradesRecap() {
  const tableBody = document.getElementById("quizGradesTableBody");
  if (tableBody) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="10" style="text-align:center;padding:24px;color:var(--text-muted);">
          Memuat rekap nilai kuis...
        </td>
      </tr>
    `;
  }

  try {
    const data = await apiGet("/quizzes/grades-recap");
    recapSummaryData = data.summary || {
      totalStudents: 0,
      completedStudentsCount: 0,
      pendingStudentsCount: 0,
      averageScore: 0,
      totalSubmissions: 0,
    };
    allRecapItems = Array.isArray(data.items) ? data.items : [];

    // Update Stat Cards
    const totalStudentsEl = document.getElementById("recapTotalStudents");
    const completedCountEl = document.getElementById("recapCompletedCount");
    const pendingCountEl = document.getElementById("recapPendingCount");
    const avgScoreEl = document.getElementById("recapAverageScore");
    const overviewAvgScoreEl = document.getElementById("overviewQuizAvgScore");

    if (totalStudentsEl) totalStudentsEl.textContent = recapSummaryData.totalStudents;
    if (completedCountEl) completedCountEl.textContent = recapSummaryData.completedStudentsCount;
    if (pendingCountEl) pendingCountEl.textContent = recapSummaryData.pendingStudentsCount;
    if (avgScoreEl) avgScoreEl.textContent = recapSummaryData.averageScore;
    if (overviewAvgScoreEl) overviewAvgScoreEl.textContent = recapSummaryData.averageScore;

    // Populate Filter Options (Class & Quiz)
    const classFilter = document.getElementById("recapClassFilter");
    if (classFilter && Array.isArray(data.classrooms)) {
      const currentVal = classFilter.value;
      classFilter.innerHTML = '<option value="ALL">Semua Kelas</option>' +
        data.classrooms.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join("");
      if (currentVal && Array.from(classFilter.options).some(o => o.value === currentVal)) {
        classFilter.value = currentVal;
      }
    }

    const quizFilter = document.getElementById("recapQuizFilter");
    if (quizFilter && Array.isArray(data.quizzes)) {
      const currentVal = quizFilter.value;
      quizFilter.innerHTML = '<option value="ALL">Semua Judul Kuis</option>' +
        data.quizzes.map(q => `<option value="${q.id}">${escapeHtml(q.title)}</option>`).join("");
      if (currentVal && Array.from(quizFilter.options).some(o => o.value === currentVal)) {
        quizFilter.value = currentVal;
      }
    }

    applyRecapFilters();
  } catch (err) {
    if (tableBody) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="10" style="text-align:center;padding:24px;color:#DC2626;">
            Gagal memuat rekap nilai kuis: ${escapeHtml(err.message || "Terjadi kesalahan")}
          </td>
        </tr>
      `;
    }
  }
}

window.loadQuizGradesRecap = loadQuizGradesRecap;

function applyRecapFilters() {
  const tableBody = document.getElementById("quizGradesTableBody");
  if (!tableBody) return;

  const search = document.getElementById("recapSearchInput")?.value.trim().toLowerCase() || "";
  const classVal = document.getElementById("recapClassFilter")?.value || "ALL";
  const quizVal = document.getElementById("recapQuizFilter")?.value || "ALL";
  const statusVal = document.getElementById("recapStatusFilter")?.value || "ALL";
  const sortOrder = document.getElementById("recapSortOrder")?.value || "score_desc";

  let filtered = [...allRecapItems];

  // Search filter (Student name or email)
  if (search) {
    filtered = filtered.filter(item => {
      const name = (item.studentName || "").toLowerCase();
      const email = (item.studentEmail || "").toLowerCase();
      return name.includes(search) || email.includes(search);
    });
  }

  // Class filter
  if (classVal !== "ALL") {
    filtered = filtered.filter(item => {
      if (Array.isArray(item.classIds) && item.classIds.includes(Number(classVal))) {
        return true;
      }
      return false;
    });
  }

  // Quiz filter
  if (quizVal !== "ALL") {
    filtered = filtered.filter(item => String(item.quizId) === String(quizVal));
  }

  // Status filter
  if (statusVal !== "ALL") {
    filtered = filtered.filter(item => item.status === statusVal);
  }

  // Sorting
  filtered.sort((a, b) => {
    if (sortOrder === "score_desc") {
      const scoreA = a.score !== null && a.score !== undefined ? Number(a.score) : -1;
      const scoreB = b.score !== null && b.score !== undefined ? Number(b.score) : -1;
      return scoreB - scoreA;
    } else if (sortOrder === "score_asc") {
      const scoreA = a.score !== null && a.score !== undefined ? Number(a.score) : 9999;
      const scoreB = b.score !== null && b.score !== undefined ? Number(b.score) : 9999;
      return scoreA - scoreB;
    } else if (sortOrder === "name_asc") {
      return (a.studentName || "").localeCompare(b.studentName || "");
    } else if (sortOrder === "date_desc") {
      const dateA = a.submittedAt ? new Date(a.submittedAt).getTime() : 0;
      const dateB = b.submittedAt ? new Date(b.submittedAt).getTime() : 0;
      return dateB - dateA;
    }
    return 0;
  });

  if (filtered.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="10" style="text-align:center;padding:32px;color:var(--text-muted);">
          Tidak ada data rekap kuis yang sesuai dengan filter.
        </td>
      </tr>
    `;
    return;
  }

  tableBody.innerHTML = filtered.map((item, index) => {
    const isCompleted = item.status === "COMPLETED";
    const scoreColor = (item.score >= 75) ? "#059669" : (item.score >= 50 ? "#D97706" : "#DC2626");

    return `
      <tr style="${isCompleted ? 'cursor:pointer;' : ''}" onclick="${isCompleted ? `openQuizResultDetailModal(${item.submissionId})` : ''}">
        <td style="text-align:center;font-weight:700;color:var(--text-secondary);">${index + 1}</td>
        <td>
          <div style="font-weight:700;color:var(--text-primary);">${escapeHtml(item.studentName || "-")}</div>
          <small style="color:var(--text-muted);font-size:0.75rem;">${escapeHtml(item.studentEmail || "")}</small>
        </td>
        <td>
          <span style="display:inline-block;background:#EEF2FF;color:#4361EE;padding:3px 10px;border-radius:6px;font-size:0.8rem;font-weight:600;">
            ${escapeHtml(item.className || "-")}
          </span>
        </td>
        <td>
          <div style="font-weight:600;color:var(--text-primary);">${escapeHtml(item.quizTitle || "-")}</div>
          ${item.bookTitle && item.bookTitle !== "-" ? `<small style="color:var(--text-secondary);font-size:0.75rem;">📖 ${escapeHtml(item.bookTitle)}</small>` : ""}
        </td>
        <td style="text-align:center;font-weight:600;">
          ${item.totalQuestions !== null ? item.totalQuestions : '<span style="color:var(--text-muted);">-</span>'}
        </td>
        <td style="text-align:center;font-weight:600;color:#059669;">
          ${item.correctCount !== null ? item.correctCount : '<span style="color:var(--text-muted);">-</span>'}
        </td>
        <td style="text-align:center;">
          ${isCompleted
            ? `<span style="font-weight:800;font-size:1.05rem;color:${scoreColor};">${item.score}</span>`
            : '<span style="color:var(--text-muted);font-size:0.85rem;font-weight:600;">Belum Ada Nilai</span>'
          }
        </td>
        <td style="text-align:center;">
          ${isCompleted
            ? `<span class="badge" style="background:#DCFCE7;color:#15803D;font-weight:700;padding:4px 10px;border-radius:20px;font-size:0.78rem;">Selesai</span>${item.attemptCount > 1 ? `<span title="Nilai dari percobaan terakhir (${item.attemptCount}x pengerjaan)" style="display:inline-block;margin-left:4px;background:#EFF6FF;color:#2563EB;font-size:0.72rem;font-weight:700;padding:2px 7px;border-radius:12px;border:1px solid #BFDBFE;">Ke-${item.attemptCount}</span>` : ''}`
            : '<span class="badge" style="background:#FEF3C7;color:#92400E;font-weight:700;padding:4px 10px;border-radius:20px;font-size:0.78rem;">Belum Mengerjakan</span>'
          }
        </td>
        <td style="font-size:0.85rem;color:var(--text-secondary);">
          ${escapeHtml(item.submittedAtFormatted || "-")}
        </td>
        <td style="text-align:center;" onclick="event.stopPropagation();">
          ${isCompleted
            ? `<button type="button" class="btn btn-outline btn-sm" onclick="openQuizResultDetailModal(${item.submissionId})" style="padding:4px 10px;font-size:0.8rem;font-weight:700;">👁️ Detail</button>`
            : '<span style="color:var(--text-muted);font-size:0.82rem;">-</span>'
          }
        </td>
      </tr>
    `;
  }).join("");
}

window.applyRecapFilters = applyRecapFilters;

async function openQuizResultDetailModal(submissionId) {
  if (!submissionId) return;

  const modal = document.getElementById("quizResultDetailModal");
  const studentNameEl = document.getElementById("detailModalStudentName");
  const metaInfoEl = document.getElementById("detailModalMetaInfo");
  const bodyEl = document.getElementById("detailModalBody");

  if (!modal || !bodyEl) return;

  modal.classList.add("active");
  document.body.style.overflow = "hidden";

  if (studentNameEl) studentNameEl.textContent = "Memuat data...";
  if (metaInfoEl) metaInfoEl.textContent = "Mengambil rincian jawaban dari server...";
  bodyEl.innerHTML = `
    <div style="text-align:center;padding:48px 16px;">
      <div style="font-size:2rem;margin-bottom:8px;">⏳</div>
      <p style="font-weight:700;color:var(--text-secondary);">Mengambil rincian jawaban siswa...</p>
    </div>
  `;

  try {
    const data = await apiGet(`/quizzes/submissions/${submissionId}`);

    if (studentNameEl) studentNameEl.textContent = data.studentName || "Siswa";
    const dateStr = data.submittedAt
      ? new Date(data.submittedAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
      : "-";

    const durationMin = Math.floor((data.durationSeconds || 0) / 60);
    const durationSec = (data.durationSeconds || 0) % 60;
    const durationStr = durationMin > 0 ? `${durationMin}m ${durationSec}s` : `${durationSec} detik`;

    if (metaInfoEl) {
      const attemptBadge = data.totalAttempts > 1
        ? ` &bull; <span style="background:#EFF6FF;color:#2563EB;padding:2px 8px;border-radius:10px;font-size:0.76rem;font-weight:700;border:1px solid #BFDBFE;">Percobaan #${data.attemptNumber} dari ${data.totalAttempts}</span>`
        : '';
      metaInfoEl.innerHTML = `
        Kuis: <strong>${escapeHtml(data.quizTitle || "Kuis Pemahaman")}</strong> &bull; Buku: <strong>${escapeHtml(data.bookTitle || "-")}</strong> &bull; Selesai: ${dateStr}${attemptBadge}
      `;
    }

    const answers = Array.isArray(data.answers) ? data.answers : [];
    const scoreColor = (data.score >= 75) ? "#059669" : (data.score >= 50 ? "#D97706" : "#DC2626");

    bodyEl.innerHTML = `
      <!-- Ringkasan Statistik Siswa -->
      <div class="quiz-result-stats-row" style="margin-bottom:20px;">
        <div class="quiz-stat-card">
          <div class="stat-lbl">Nilai Akhir</div>
          <div class="stat-val" style="color:${scoreColor};">${data.score} / 100</div>
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

      <!-- Riwayat Percobaan Kuis (Jika siswa mengerjakan lebih dari 1 kali) -->
      ${Array.isArray(data.attemptHistory) && data.attemptHistory.length > 1 ? `
        <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:12px 16px;margin-bottom:20px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
            <div style="font-weight:700;font-size:0.86rem;color:var(--text-primary);display:flex;align-items:center;gap:6px;">
              <span>🔁</span> Riwayat Percobaan Kuis (${data.totalAttempts} kali diselesaikan)
            </div>
            <span style="font-size:0.74rem;color:var(--text-muted);">Nilai percobaan terakhir yang aktif di rekap utama</span>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            ${data.attemptHistory.map(att => {
              const isCurrent = att.isCurrent;
              const isLatest = att.attemptNumber === data.totalAttempts;
              const attScoreColor = (att.score >= 75) ? "#059669" : (att.score >= 50 ? "#D97706" : "#DC2626");
              return `
                <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:8px;font-size:0.8rem;border:1.5px solid ${isCurrent ? '#4361EE' : '#CBD5E1'};background:${isCurrent ? '#EEF2FF' : '#FFFFFF'};cursor:${isCurrent ? 'default' : 'pointer'};transition:all 0.15s ease;"
                     ${isCurrent ? '' : `onclick="openQuizResultDetailModal(${att.id})"`}
                     title="${isCurrent ? 'Sedang dilihat' : 'Klik untuk melihat rincian pengerjaan percobaan ini'}">
                  <strong>Percobaan #${att.attemptNumber}${isLatest ? ' (Terbaru)' : ''}:</strong>
                  <span style="font-weight:800;color:${attScoreColor};">${att.score} / 100</span>
                  <small style="color:var(--text-muted);font-size:0.74rem;">(${escapeHtml(att.submittedAtFormatted || '-')})</small>
                  ${isCurrent ? '<span style="color:#4361EE;font-weight:700;font-size:0.75rem;">✓ Sedang Dilihat</span>' : '<span style="color:var(--text-secondary);font-size:0.75rem;">👁️</span>'}
                </div>
              `;
            }).join("")}
          </div>
        </div>
      ` : ''}

      <!-- Rincian Tiap Soal & Kunci Jawaban -->
      <div class="quiz-review-section">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
          <h3 style="font-size:1.1rem;font-weight:800;color:#0F172A;margin:0;">
            📋 Evaluasi Tiap Butir Soal & Kunci Jawaban
          </h3>
          <span style="font-size:0.84rem;color:#64748B;">Total ${data.totalQuestions || answers.length} Soal</span>
        </div>

        <div class="quiz-review-list">
          ${answers.map((ans, idx) => {
            const isCorrect = Boolean(ans.isCorrect);
            const isMcq = ans.type === "MCQ";

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
                    <strong style="display:block;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">
                      Jawaban Siswa:
                    </strong>
                    <span style="font-weight:700;">${ans.studentResponse ? escapeHtml(ans.studentResponse) : '<em>(Tidak dijawab)</em>'}</span>
                  </div>

                  <div class="quiz-answer-display-box correct-key">
                    <strong style="display:block;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">
                      Kunci Jawaban Guru:
                    </strong>
                    <span style="font-weight:700;">${escapeHtml(ans.correctAnswer || '(Pedoman Guru)')}</span>
                  </div>
                </div>

                ${ans.explanation ? `
                  <div style="margin-top:12px;background:#F8FAFC;border:1px dashed #CBD5E1;border-radius:8px;padding:10px 14px;font-size:0.85rem;color:#334155;">
                    <strong style="color:var(--primary);display:block;margin-bottom:2px;">💡 Pembahasan / Penjelasan Guru:</strong>
                    ${escapeHtml(ans.explanation)}
                  </div>
                ` : ""}
              </div>
            `;
          }).join("")}
        </div>
      </div>
    `;
  } catch (err) {
    bodyEl.innerHTML = `
      <div style="text-align:center;padding:32px 16px;color:#DC2626;">
        <p style="font-weight:700;">Gagal memuat rincian kuis:</p>
        <p style="font-size:0.88rem;">${escapeHtml(err.message || "Terjadi kesalahan")}</p>
      </div>
    `;
  }
}

window.openQuizResultDetailModal = openQuizResultDetailModal;

function closeQuizResultDetailModal() {
  const modal = document.getElementById("quizResultDetailModal");
  if (modal) {
    modal.classList.remove("active");
    document.body.style.overflow = "";
  }
}

window.closeQuizResultDetailModal = closeQuizResultDetailModal;

window.onFeatureSwitched = function(featureId) {
  if (featureId === "quizGradesSection") {
    loadQuizGradesRecap();
  }
};
