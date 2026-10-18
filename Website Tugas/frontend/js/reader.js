if (!requireAuth(["SISWA", "GURU", "ADMIN"])) {
  throw new Error("Unauthorized");
}

const params = new URLSearchParams(window.location.search);
const bookId = params.get("id");
if (!bookId) window.location.href = "katalog.html";

const auth = getAuth();
let pageNum = 1;
let totalPages = 1;
let scale = 1;
let readingStartedAt = Date.now();
let baseDuration = 0;
let fallbackMode = false;
let isSaving = false;
let pdfDocument = null;
let renderTask = null;

const frame = document.getElementById("pdf-frame");
const canvas = document.getElementById("pdf-canvas");
const pageImage = document.getElementById("pageImage");
const prevBtn = document.getElementById("prev");
const nextBtn = document.getElementById("next");
const zoomInBtn = document.getElementById("zoomIn");
const zoomOutBtn = document.getElementById("zoomOut");
const takeQuizButton = document.getElementById("takeQuizButton");
const canvasContext = canvas.getContext("2d");

document.addEventListener("contextmenu", e => e.preventDefault());
document.addEventListener("keydown", e => {
  if (e.ctrlKey && ["s", "p", "u"].includes(e.key.toLowerCase())) e.preventDefault();
});

window.addEventListener("beforeunload", () => {
  if (auth.role !== "SISWA") return;

  fetch(`${API_BASE}/reading/progress`, {
    method: "POST",
    keepalive: true,
    headers: authHeaders(),
    body: JSON.stringify({
      bookId: Number(bookId),
      duration: getTotalDuration(),
      progress: getProgressValue(pageNum),
      lastPage: pageNum
    })
  }).catch(() => {});
});

(async function init() {
  try {
    ensurePdfJsReady();

    const [book, pdfDoc] = await Promise.all([
      apiGet(`/books/${bookId}`),
      loadPdfDocument()
    ]);

    pdfDocument = pdfDoc;
    totalPages = Math.max(1, pdfDocument.numPages || 1);
    document.getElementById("bookTitle").textContent = book.title;

    if (auth.role === "SISWA") {
      const session = await apiPost("/reading/start", { bookId: Number(bookId), currentPage: 1 });
      baseDuration = session.duration || 0;
      pageNum = clampPage(session.lastPage || 1);
      updateProgressUi(session.progress || getProgressValue(pageNum), pageNum, "Melanjutkan bacaan");
    }

    await renderPage(pageNum, { persistProgress: auth.role === "SISWA" });
  } catch (error) {
    try {
      await openFallbackPdf();
    } catch {
      alert("Gagal membuka buku. Silakan login ulang lalu coba lagi.");
      window.location.href = "login.html";
    }
  }
})();

async function loadPdfDocument() {
  const pdfjs = window.pdfjsLib;
  pdfjs.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.8.69/pdf.worker.min.js";

  const loadingTask = pdfjs.getDocument({
    url: `${API_BASE}/books/${bookId}/stream`,
    httpHeaders: {
      Authorization: `Bearer ${auth.token}`
    },
    withCredentials: false
  });

  return loadingTask.promise;
}

async function renderPage(num, { persistProgress = true } = {}) {
  if (!pdfDocument) {
    throw new Error("Dokumen PDF belum siap.");
  }

  const safePage = clampPage(num);

  if (renderTask) {
    try {
      renderTask.cancel();
    } catch {
      // Ignore cancelled render tasks while switching pages rapidly.
    }
  }

  const page = await pdfDocument.getPage(safePage);
  const rawViewport = page.getViewport({ scale: 1 });
  const stage = document.getElementById("readerStage");
  const containerWidth = Math.max(1, stage.clientWidth - 36);
  const containerHeight = Math.max(1, stage.clientHeight - 36);
  const widthScale = containerWidth / rawViewport.width;
  const heightScale = containerHeight / rawViewport.height;
  const defaultScale = Math.min(2, Math.max(0.8, Math.min(widthScale, heightScale)));

  if (scale === 1) {
    scale = defaultScale;
  }

  const viewport = page.getViewport({ scale });

  canvas.width = Math.ceil(viewport.width);
  canvas.height = Math.ceil(viewport.height);
  canvas.style.width = "100%";
  canvas.style.height = "100%";
  canvas.style.maxWidth = "100%";
  canvas.style.maxHeight = "100%";

  pageImage.classList.add("hidden");
  frame.classList.add("hidden");
  canvas.classList.remove("hidden");

  renderTask = page.render({
    canvasContext,
    viewport
  });

  try {
    await renderTask.promise;
  } finally {
    renderTask = null;
  }

  pageNum = safePage;
  updateNavState();

  const progress = getProgressValue(safePage);
  updateProgressUi(progress, safePage);

  if (persistProgress) {
    await sendProgress(progress, safePage);
  }
}

function updateProgressUi(progress, currentPage, labelPrefix = "") {
  const progressLabel = labelPrefix ? `${labelPrefix} ${progress}%` : `${progress}%`;
  document.getElementById("pageInfo").textContent = `Halaman ${currentPage} / ${totalPages}`;
  document.getElementById("progressText").textContent = progressLabel;
  document.getElementById("progressBar").style.width = `${progress}%`;

  if (auth.role === "SISWA") {
    if (progress >= 100) {
      takeQuizButton.classList.remove("hidden");
    } else {
      takeQuizButton.classList.add("hidden");
    }
  }
}

function getProgressValue(currentPage) {
  return Math.min(100, Math.max(0, Math.round((currentPage / Math.max(totalPages, 1)) * 100)));
}

function getTotalDuration() {
  return baseDuration + Math.max(1, Math.floor((Date.now() - readingStartedAt) / 60000));
}

function clampPage(page) {
  return Math.min(Math.max(1, Number(page) || 1), totalPages);
}

function updateNavState() {
  prevBtn.disabled = fallbackMode || pageNum <= 1;
  nextBtn.disabled = fallbackMode || pageNum >= totalPages;
}

async function sendProgress(progress, lastPage) {
  if (auth.role !== "SISWA" || isSaving) return;

  isSaving = true;
  try {
    const session = await apiPost("/reading/progress", {
      bookId: Number(bookId),
      duration: getTotalDuration(),
      progress,
      lastPage
    });
    baseDuration = session.duration || baseDuration;
  } catch (error) {
    console.error(error);
  } finally {
    isSaving = false;
  }
}

prevBtn.addEventListener("click", async () => {
  if (fallbackMode || pageNum <= 1) return;
  await renderPage(pageNum - 1, { persistProgress: auth.role === "SISWA" });
});

nextBtn.addEventListener("click", async () => {
  if (fallbackMode || pageNum >= totalPages) return;
  await renderPage(pageNum + 1, { persistProgress: auth.role === "SISWA" });
});

zoomInBtn.addEventListener("click", async () => {
  if (fallbackMode) return;
  scale = Math.min(2, Number((scale + 0.15).toFixed(2)));
  await renderPage(pageNum, { persistProgress: false });
});

zoomOutBtn.addEventListener("click", async () => {
  if (fallbackMode) return;
  scale = Math.max(0.7, Number((scale - 0.15).toFixed(2)));
  await renderPage(pageNum, { persistProgress: false });
});

takeQuizButton.addEventListener("click", () => {
  window.location.href = `quiz.html?bookId=${bookId}`;
});

function ensurePdfJsReady() {
  if (!window.pdfjsLib) {
    throw new Error("pdf.js belum termuat.");
  }
}

async function openFallbackPdf() {
  const res = await fetch(`${API_BASE}/books/${bookId}/stream`, {
    headers: { Authorization: `Bearer ${auth.token}` }
  });

  if (!res.ok) throw new Error("Gagal load PDF");

  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  fallbackMode = true;
  pageImage.classList.add("hidden");
  canvas.classList.add("hidden");
  frame.classList.remove("hidden");
  frame.src = url;
  prevBtn.disabled = true;
  nextBtn.disabled = true;
  zoomInBtn.disabled = true;
  zoomOutBtn.disabled = true;
  document.getElementById("progressText").textContent = auth.role === "SISWA"
    ? "Reader cadangan aktif"
    : "Mode pratinjau";
}
