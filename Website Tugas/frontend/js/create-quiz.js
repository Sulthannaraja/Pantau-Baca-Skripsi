requireAuth(["GURU"]);

const bookSelect = document.getElementById("bookSelect");
const quizTitleInput = document.getElementById("quizTitleInput");
const quizDescriptionInput = document.getElementById("quizDescriptionInput");
const questionPanel = document.getElementById("questionPanel");
const addQuestionButton = document.getElementById("addQuestion");
const createQuizForm = document.getElementById("createQuizForm");
const formMessage = document.getElementById("formMessage");

let questionIndex = 0;

(async function init() {
  try {
    const books = await apiGet("/books");
    fillBooks(books);
  } catch (error) {
    showError("Gagal memuat daftar buku. Pastikan backend terhubung.");
  }

  addQuestion();
})();

addQuestionButton.addEventListener("click", () => addQuestion());

createQuizForm.addEventListener("submit", async event => {
  event.preventDefault();

  const payload = {
    bookId: Number(bookSelect.value),
    title: quizTitleInput.value.trim(),
    description: quizDescriptionInput.value.trim(),
    questions: collectQuestions(),
  };

  try {
    await apiPost("/quizzes", payload);
    formMessage.textContent = "Kuis berhasil dibuat.";
    formMessage.style.color = "#0077b6";
    createQuizForm.reset();
    questionPanel.innerHTML = "";
    addQuestion();
  } catch (error) {
    showError(error.message);
  }
});

function fillBooks(books) {
  if (!books.length) {
    bookSelect.innerHTML = '<option value="">Tidak ada buku tersedia</option>';
    return;
  }

  bookSelect.innerHTML = books.map(book => `
    <option value="${book.id}">${escapeHtml(book.title)} — ${escapeHtml(book.author)}</option>
  `).join("");
}

function addQuestion() {
  const id = questionIndex++;
  const questionCard = document.createElement("div");
  questionCard.className = "card";
  questionCard.style.marginBottom = "18px";
  questionCard.dataset.questionId = String(id);
  questionCard.innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px;">
      <h4 style="margin:0;">Pertanyaan ${id + 1}</h4>
      <button type="button" class="btn btn-outline remove-question">Hapus</button>
    </div>
    <label style="display:block; margin-bottom:12px;">
      <strong>Tipe</strong><br />
      <select class="input question-type">
        <option value="MCQ">Pilihan Ganda</option>
        <option value="ESSAY">Isian Singkat</option>
      </select>
    </label>
    <label style="display:block; margin-bottom:12px;">
      <strong>Pertanyaan</strong><br />
      <textarea class="input question-text" rows="3" placeholder="Tulis pertanyaan"></textarea>
    </label>
    <label class="options-group" style="display:block; margin-bottom:12px;">
      <strong>Opsi (satu per baris)</strong><br />
      <textarea class="input question-options" rows="3" placeholder="Opsi 1\nOpsi 2\nOpsi 3"></textarea>
    </label>
    <label style="display:block; margin-bottom:12px;">
      <strong>Jawaban Benar</strong><br />
      <input type="text" class="input question-answer" placeholder="Jawaban yang benar" />
    </label>
    <label style="display:block; margin-bottom:0;">
      <strong>Bobot</strong><br />
      <input type="number" min="1" value="1" class="input question-points" />
    </label>
  `;

  questionPanel.appendChild(questionCard);
  questionCard.querySelector(".remove-question").addEventListener("click", () => questionCard.remove());
  questionCard.querySelector(".question-type").addEventListener("change", event => {
    const type = event.target.value;
    questionCard.querySelector(".options-group").style.display = type === "MCQ" ? "block" : "none";
    questionCard.querySelector(".question-answer").placeholder = type === "MCQ" ? "Jawaban yang benar" : "Kunci jawaban singkat";
  });
}

function collectQuestions() {
  formMessage.textContent = "";
  const questions = [];

  document.querySelectorAll(".question-card").forEach(card => {
    const type = card.querySelector(".question-type").value;
    const question = card.querySelector(".question-text").value.trim();
    const answer = card.querySelector(".question-answer").value.trim();
    const points = Number(card.querySelector(".question-points").value) || 1;
    const optionsText = card.querySelector(".question-options").value.trim();
    const options = type === "MCQ" ? optionsText.split(/\r?\n/).map(o => o.trim()).filter(Boolean) : null;

    questions.push({
      type,
      question,
      options,
      answer,
      points,
    });
  });

  return questions;
}

function showError(message) {
  formMessage.textContent = message;
  formMessage.style.color = "#b00020";
}
