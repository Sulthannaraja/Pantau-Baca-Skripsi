requireAuth(["SISWA"]);

const quizTitleEl = document.getElementById("quizTitle");
const quizDescriptionEl = document.getElementById("quizDescription");
const quizMessageEl = document.getElementById("quizMessage");
const questionListEl = document.getElementById("questionList");
const quizForm = document.getElementById("quizForm");
const quizResultEl = document.getElementById("quizResult");

const params = new URLSearchParams(window.location.search);
const bookId = params.get("bookId");

if (!bookId) {
  window.location.href = "katalog.html";
}

let quizData = null;

(async function init() {
  try {
    quizData = await apiGet(`/books/${bookId}/quiz`);
    renderQuiz(quizData);
  } catch (error) {
    showError(error.message);
  }
})();

quizForm.addEventListener("submit", async event => {
  event.preventDefault();
  quizResultEl.textContent = "";

  if (!quizData?.quizId) {
    return;
  }

  const answers = Array.from(document.querySelectorAll(".question-card")).map(card => {
    const questionId = Number(card.dataset.questionId);
    const checkedOption = card.querySelector("input[type='radio']:checked");
    const textarea = card.querySelector("textarea");
    const response = checkedOption?.value ?? textarea?.value.trim() ?? "";

    return {
      questionId,
      response,
    };
  });

  try {
    const result = await apiPost("/quizzes/submit", {
      quizId: quizData.quizId,
      answers,
    });

    quizResultEl.textContent = `Kuis selesai. Skor Anda: ${result.score}`;
    quizResultEl.style.color = "#0077b6";
  } catch (error) {
    showError(error.message);
  }
});

function renderQuiz(quiz) {
  quizTitleEl.textContent = quiz.title;
  quizDescriptionEl.textContent = quiz.description || "Tidak ada deskripsi tambahan.";
  quizMessageEl.textContent = ""
  quizForm.classList.remove("hidden");

  if (!quiz.questions.length) {
    quizMessageEl.textContent = "Belum ada pertanyaan untuk kuis ini.";
    quizForm.querySelector("button[type='submit']").disabled = true;
    return;
  }

  questionListEl.innerHTML = quiz.questions.map((question, index) => `
    <div class="card question-card" data-question-id="${question.questionId}" style="margin-bottom:16px;">
      <h4 style="margin-top:0;">${index + 1}. ${escapeHtml(question.question)}</h4>
      ${renderQuestionInput(question)}
    </div>
  `).join("");
}

function renderQuestionInput(question) {
  if (question.type === "MCQ") {
    return `
      <div style="display:grid;gap:12px;">
        ${question.options.map(option => `
          <label style="display:flex;align-items:center;gap:8px;">
            <input type="radio" name="question-${question.questionId}" value="${escapeHtml(option)}" />
            ${escapeHtml(option)}
          </label>
        `).join("")}
      </div>
    `;
  }

  return `
    <textarea rows="4" class="input" placeholder="Tulis jawaban Anda di sini..."></textarea>
  `;
}

function showError(message) {
  quizMessageEl.textContent = message;
  quizMessageEl.style.color = "#b00020";
  quizForm.classList.add("hidden");
}
