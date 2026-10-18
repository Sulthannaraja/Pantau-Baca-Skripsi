/**
 * PantauBaca — Interactive 3D Flipbook Reader Engine
 * Features:
 * - PDF.js crisp progressive rendering into StPageFlip 3D physical book component
 * - Two-page spread on desktop, single-page portrait on mobile
 * - Visited-pages based honest reading progress tracking (prevents instant 100% skip)
 * - Automatic DB sync and session restoration via /api/reading/start & /api/reading/progress
 * - Full integration with Kuis Pemahaman (/api/books/{id}/quiz & /api/quizzes/submit)
 * - Zoom, Fullscreen, Web Audio realistic page turn sound, and keyboard navigation
 */

if (!requireAuth(["SISWA", "GURU", "ADMIN"])) {
  throw new Error("Unauthorized");
}

const auth = getAuth();
const params = new URLSearchParams(window.location.search);
const bookId = params.get("id");

if (!bookId) {
  window.location.href = "/katalog.html";
}

// State variables
let bookData = null;
let pdfDocument = null;
let pageFlipInstance = null;
let totalPages = 1;
let currentPageIndex = 0; // 0-indexed in StPageFlip
let zoomLevel = 1.0;
let readingStartedAt = Date.now();
let baseDuration = 0;
let isSavingProgress = false;
let saveDebounceTimer = null;
let currentLoadedQuiz = null;
let lastRenderedStageWidth = 0;
let lastRenderedStageHeight = 0;

// Visited pages tracking for genuine reading completion calculation
const visitedPages = new Set();
const renderedPages = new Set();
const renderQueue = [];
let isRenderingQueue = false;

// DOM Elements
const prevBtn = document.getElementById("prev");
const nextBtn = document.getElementById("next");
const stagePrevBtn = document.getElementById("stagePrevBtn");
const stageNextBtn = document.getElementById("stageNextBtn");
const pageNavInfoEl = document.getElementById("pageNavInfo");
const zoomInBtn = document.getElementById("zoomIn");
const zoomOutBtn = document.getElementById("zoomOut");
const zoomPercentEl = document.getElementById("zoomPercent");
const flipbookViewport = document.getElementById("flipbookViewport");
const flipbookContainer = document.getElementById("flipbookContainer");
const flipbookLoading = document.getElementById("flipbookLoading");

// Prevent native browser actions
document.addEventListener("contextmenu", e => e.preventDefault());
document.addEventListener("keydown", e => {
  if (e.ctrlKey && ["s", "p", "u"].includes(e.key.toLowerCase())) {
    e.preventDefault();
  }
  // Keyboard arrow flip navigation
  if (e.key === "ArrowLeft") {
    handleFlipPrev();
  } else if (e.key === "ArrowRight") {
    handleFlipNext();
  }
});

// Save progress automatically when leaving the page
window.addEventListener("beforeunload", () => {
  if (auth.role !== "SISWA" || totalPages <= 0) return;

  const progress = calculateProgress();
  const lastPage = getActivePageNumber();

  fetch(`${API_BASE}/reading/progress`, {
    method: "POST",
    keepalive: true,
    headers: authHeaders(),
    body: JSON.stringify({
      bookId: Number(bookId),
      duration: getTotalDuration(),
      progress: progress,
      lastPage: lastPage
    })
  }).catch(() => {});
});

/**
 * Initialize Reader Application
 */
(async function init() {
  try {
    ensurePdfJsReady();

    // 1. Fetch book metadata and PDF document in parallel
    const [book, pdfDoc] = await Promise.all([
      apiGet(`/books/${bookId}`),
      loadPdfDocument()
    ]);

    bookData = book;
    pdfDocument = pdfDoc;
    totalPages = Math.max(1, pdfDoc.numPages || 1);

    // 2. Populate book details in UI
    updateBookInfoUi(book);

    // 3. Initialize or restore student session from database
    let initialPage = 1;
    let initialProgress = 0;

    if (auth.role === "SISWA") {
      try {
        const session = await apiPost("/reading/start", {
          bookId: Number(bookId),
          currentPage: 1
        });

        baseDuration = Number(session.duration) || 0;
        initialProgress = Number(session.progress) || 0;
        initialPage = clampPage(session.lastPage || 1);
      } catch (err) {
        console.warn("Could not start reading session from server:", err);
      }
    }

    // 4. Restore visited pages from localStorage or session
    restoreVisitedPages(initialProgress, initialPage);

    // 5. Build and launch 3D Flipbook
    await buildAndMountFlipbook(initialPage);

    // 6. Start duration timer
    startDurationTracker();

    // 7. Initial UI Progress update
    const currentProg = calculateProgress();
    updateProgressUi(currentProg, initialPage);

    // 8. Auto check if quiz is already unlocked
    if (currentProg >= 100) {
      handleReadingCompleted();
    }
  } catch (error) {
    console.error("Reader initialization error:", error);
    if (flipbookLoading) {
      flipbookLoading.innerHTML = `
        <div style="font-size:3rem;margin-bottom:10px;">⚠️</div>
        <h3 style="font-weight:700;color:var(--text-primary);margin:0;">Gagal Memuat Buku Digital</h3>
        <p style="color:var(--text-secondary);font-size:0.86rem;max-width:380px;text-align:center;margin:6px 0 16px;">
          ${escapeHtml(error.message || "Terjadi kesalahan saat memproses berkas PDF.")}
        </p>
        <button class="btn btn-primary btn-sm" onclick="window.location.reload()">Coba Muat Ulang</button>
      `;
    }
  }
})();

/**
 * Configure and render PDF document using PDF.js
 */
async function loadPdfDocument() {
  const pdfjs = window.pdfjsLib;
  if (!pdfjs) throw new Error("PDF.js library tidak tersedia.");

  try {
    pdfjs.GlobalWorkerOptions.workerSrc = "/pantaubaca/js/pdf.worker.min.js";
  } catch {}

  const res = await fetch(`${API_BASE}/books/${bookId}/stream`, {
    headers: { Authorization: `Bearer ${auth.token}` }
  });

  if (!res.ok) {
    throw new Error(`Gagal mengunduh stream buku (Status: ${res.status})`);
  }

  const arrayBuffer = await res.arrayBuffer();
  return pdfjs.getDocument({
    data: arrayBuffer,
    cMapUrl: "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/",
    cMapPacked: true
  }).promise;
}

/**
 * Mount Flipbook with responsive two-page spread or single-page mobile layout
 */
async function buildAndMountFlipbook(targetInitialPage = 1) {
  if (!window.St || !window.St.PageFlip) {
    throw new Error("Library PageFlip tidak ditemukan.");
  }

  flipbookContainer.innerHTML = "";

  // 1. Get Page 1 aspect ratio to compute realistic proportions
  const samplePage = await pdfDocument.getPage(1);
  const sampleVp = samplePage.getViewport({ scale: 1 });
  const pageAspect = sampleVp.height / sampleVp.width; // typically ~1.414 for A4

  // 2. Compute exact optimal width & height using entire available stage area
  const stage = document.getElementById("readerStage");
  const stageWidth = stage ? stage.clientWidth : (window.innerWidth - 240);
  const isMobile = window.innerWidth <= 850;

  // Space for side arrow buttons flanking the book (56px each side = 112px, plus safety margin)
  const navButtonsSpace = isMobile ? 0 : 130;
  const availableWidth = Math.max(280, stageWidth - navButtonsSpace);

  // Available height: inner height minus navbar (74px) minus compact toolbar (~44px) minus margins (~24px)
  const availableHeight = Math.max(360, window.innerHeight - (isMobile ? 180 : 142));

  let singlePageWidth;
  if (isMobile) {
    singlePageWidth = Math.min(availableWidth, Math.floor(availableHeight / pageAspect));
    singlePageWidth = Math.max(260, Math.min(singlePageWidth, 650));
  } else {
    // Two-page spread desktop: width limit per page vs height limit per page
    const maxWidthPerPage = Math.floor(availableWidth / 2);
    const maxHeightPerPage = Math.floor(availableHeight / pageAspect);
    singlePageWidth = Math.min(maxWidthPerPage, maxHeightPerPage);
    singlePageWidth = Math.max(320, Math.min(singlePageWidth, 850));
  }
  const singlePageHeight = Math.round(singlePageWidth * pageAspect);

  lastRenderedStageWidth = stageWidth;
  lastRenderedStageHeight = window.innerHeight;

  const totalSpreadWidth = isMobile ? singlePageWidth : (singlePageWidth * 2);
  const totalSpreadHeight = singlePageHeight;

  // Explicitly set calculated dimensions directly on container & viewport
  flipbookViewport.style.width = totalSpreadWidth + "px";
  flipbookViewport.style.height = totalSpreadHeight + "px";
  flipbookContainer.style.width = totalSpreadWidth + "px";
  flipbookContainer.style.height = totalSpreadHeight + "px";
  flipbookContainer.style.minWidth = totalSpreadWidth + "px";
  flipbookContainer.style.minHeight = totalSpreadHeight + "px";

  // 3. Create DOM page elements for all pages
  for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
    const pageEl = document.createElement("div");
    pageEl.className = "flipbook-page";
    pageEl.setAttribute("data-page-num", String(pageNum));
    pageEl.setAttribute("data-density", pageNum === 1 || pageNum === totalPages ? "hard" : "soft");
    pageEl.style.width = singlePageWidth + "px";
    pageEl.style.height = singlePageHeight + "px";

    // Skeleton placeholder while page renders
    const skeletonEl = document.createElement("div");
    skeletonEl.className = "flipbook-page-skeleton";
    skeletonEl.id = `skeleton-${pageNum}`;
    skeletonEl.innerHTML = `
      <div class="page-spinner"></div>
      <span>Memuat Hal. ${pageNum}...</span>
    `;
    pageEl.appendChild(skeletonEl);

    const canvasEl = document.createElement("canvas");
    canvasEl.className = "flipbook-canvas";
    canvasEl.id = `canvas-page-${pageNum}`;
    pageEl.appendChild(canvasEl);

    flipbookContainer.appendChild(pageEl);
  }

  // 4. Pre-render initial critical pages (1 to 4) immediately so flipbook opens right away
  const initialPagesToRender = [1, 2, 3, 4].filter(p => p <= totalPages);
  await Promise.all(initialPagesToRender.map(p => renderPdfPage(p, singlePageWidth, singlePageHeight)));

  // Hide loading indicator, reveal flipbook stage
  if (flipbookLoading) flipbookLoading.classList.add("hidden");
  if (flipbookViewport) flipbookViewport.classList.remove("hidden");

  // 5. Initialize StPageFlip with "fixed" size at the EXACT calculated dimensions
  const startPageIndex = Math.max(0, targetInitialPage - 1);

  pageFlipInstance = new St.PageFlip(flipbookContainer, {
    width: singlePageWidth,
    height: singlePageHeight,
    size: "fixed",
    autoSize: false,
    showCover: true,
    usePortrait: isMobile,
    drawShadow: true,
    flippingTime: 650,
    useMouseEvents: true,
    swipeDistance: 25,
    maxShadowOpacity: 0.35,
    showPageCorners: true,
    startPage: startPageIndex
  });

  pageFlipInstance.loadFromHTML(flipbookContainer.querySelectorAll(".flipbook-page"));

  // Track initial page viewing
  markPagesAsVisited(targetInitialPage);

  // 4. StPageFlip Event Listeners
  pageFlipInstance.on("flip", e => {
    playPageTurnSound();
    currentPageIndex = e.data || 0;
    const activePage = getActivePageNumber();

    // Mark current pages as visited
    markPagesAsVisited(activePage);

    // Update UI & indicator
    const currentProgress = calculateProgress();
    updateProgressUi(currentProgress, activePage);
    updateControlsState();

    // Render upcoming and nearby pages on demand
    queueNearbyPagesRendering(activePage, singlePageWidth, singlePageHeight);

    // Debounced sync to database
    if (auth.role === "SISWA") {
      triggerDebouncedProgressSync(currentProgress, activePage);
    }
  });

  // Attach button triggers
  prevBtn.onclick = handleFlipPrev;
  nextBtn.onclick = handleFlipNext;
  zoomInBtn.onclick = handleZoomIn;
  zoomOutBtn.onclick = handleZoomOut;

  // Background queue for remaining pages
  queueNearbyPagesRendering(targetInitialPage, singlePageWidth, singlePageHeight);
  scheduleBackgroundPagesRendering(singlePageWidth, singlePageHeight);

  updateControlsState();
}

/**
 * Render individual PDF page to canvas with high DPI scaling
 */
async function renderPdfPage(pageNum, containerWidth, containerHeight) {
  if (renderedPages.has(pageNum)) return;
  renderedPages.add(pageNum);

  try {
    const page = await pdfDocument.getPage(pageNum);
    const canvasEl = document.getElementById(`canvas-page-${pageNum}`);
    const skeletonEl = document.getElementById(`skeleton-${pageNum}`);
    if (!canvasEl) return;

    const ctx = canvasEl.getContext("2d");
    const unscaledVp = page.getViewport({ scale: 1 });

    // High DPI crisp resolution (1.5x - 2.0x for razor sharp text)
    const dpr = Math.min(window.devicePixelRatio || 1, 2.5);
    const scale = (containerWidth / unscaledVp.width) * dpr;
    const viewport = page.getViewport({ scale: Math.max(1.2, scale) });

    canvasEl.width = viewport.width;
    canvasEl.height = viewport.height;
    canvasEl.style.width = "100%";
    canvasEl.style.height = "100%";

    await page.render({
      canvasContext: ctx,
      viewport: viewport
    }).promise;

    if (skeletonEl) skeletonEl.style.display = "none";
  } catch (err) {
    console.warn(`Error rendering page ${pageNum}:`, err);
    renderedPages.delete(pageNum); // Allow retry
  }
}

/**
 * Queue nearby pages rendering (current +/- 2)
 */
function queueNearbyPagesRendering(centerPage, width, height) {
  const nearby = [
    centerPage - 2,
    centerPage - 1,
    centerPage,
    centerPage + 1,
    centerPage + 2,
    centerPage + 3
  ].filter(p => p >= 1 && p <= totalPages && !renderedPages.has(p));

  for (const pageNum of nearby) {
    renderPdfPage(pageNum, width, height);
  }
}

/**
 * Progressively render any remaining unrendered pages in background
 */
function scheduleBackgroundPagesRendering(width, height) {
  let nextPage = 1;
  function processNext() {
    while (nextPage <= totalPages && renderedPages.has(nextPage)) {
      nextPage++;
    }
    if (nextPage <= totalPages) {
      renderPdfPage(nextPage, width, height).then(() => {
        nextPage++;
        setTimeout(processNext, 120);
      });
    }
  }
  setTimeout(processNext, 500);
}

/**
 * Visited pages logic:
 * Progress is calculated honestly based on visited pages,
 * ensuring skipping to the last page doesn't instantly give 100%!
 */
function markPagesAsVisited(centerPage) {
  visitedPages.add(centerPage);

  // In two-page spread mode, adjacent page is also visible
  if (pageFlipInstance && pageFlipInstance.getOrientation() === "landscape") {
    if (centerPage % 2 === 0 && centerPage + 1 <= totalPages) {
      visitedPages.add(centerPage + 1);
    } else if (centerPage % 2 === 1 && centerPage - 1 >= 1) {
      visitedPages.add(centerPage - 1);
    }
  }

  saveVisitedPagesToStorage();

  const progress = calculateProgress();
  if (progress >= 100) {
    handleReadingCompleted();
  }
}

function calculateProgress() {
  if (totalPages <= 0) return 0;
  return Math.min(100, Math.max(0, Math.round((visitedPages.size / totalPages) * 100)));
}

function restoreVisitedPages(initialProgress, initialPage) {
  const storageKey = `pantau_read_pages_${auth.userId || auth.name}_${bookId}`;
  try {
    const saved = localStorage.getItem(storageKey);
    if (saved) {
      const parsed = JSON.parse(saved);
      if (Array.isArray(parsed)) {
        parsed.forEach(p => {
          if (p >= 1 && p <= totalPages) visitedPages.add(p);
        });
      }
    }
  } catch {}

  // If localStorage didn't have pages but server recorded progress, seed visited pages conservatively
  if (visitedPages.size === 0 && initialProgress > 0) {
    const minPagesToSeed = Math.min(totalPages, Math.max(1, Math.round((initialProgress / 100) * totalPages)));
    for (let i = 1; i <= minPagesToSeed; i++) {
      visitedPages.add(i);
    }
  }

  visitedPages.add(initialPage);
}

function saveVisitedPagesToStorage() {
  const storageKey = `pantau_read_pages_${auth.userId || auth.name}_${bookId}`;
  try {
    localStorage.setItem(storageKey, JSON.stringify(Array.from(visitedPages)));
  } catch {}
}

/**
 * Handle Reading Completion (Progress 100%)
 * Displays the Kuis Pemahaman unlock banner and updates tips
 */
function handleReadingCompleted() {
  const quizCard = document.getElementById("quizUnlockedCard");
  if (quizCard) {
    quizCard.classList.remove("hidden");
  }

  const tipsCard = document.getElementById("literacyTipsCard");
  if (tipsCard) {
    tipsCard.style.background = "#ECFDF5";
    tipsCard.style.borderColor = "#A7F3D0";
    tipsCard.style.color = "#065F46";
    tipsCard.innerHTML = `
      🎉 <strong>Hebat Sekali!</strong> Seluruh halaman telah selesai dibaca (100%). Kerjakan <strong>Kuis Pemahaman</strong> sekarang untuk melatih dan mengevaluasi daya serap bacaanmu!
    `;
  }
}

/**
 * Update UI Elements (Progress, Book Info, Nav)
 */
function updateProgressUi(progress, currentPage) {
  // Progress text & bar
  const progressTextEl = document.getElementById("progressText");
  if (progressTextEl) progressTextEl.textContent = `${progress}%`;

  const progressBarEl = document.getElementById("progressBar");
  if (progressBarEl) progressBarEl.style.width = `${progress}%`;

  // Pages read counter
  const pagesReadCountEl = document.getElementById("pagesReadCount");
  if (pagesReadCountEl) {
    pagesReadCountEl.textContent = `${visitedPages.size} dari ${totalPages} Halaman`;
  }

  // Current page position
  const pageInfoEl = document.getElementById("pageInfo");
  if (pageInfoEl) {
    pageInfoEl.textContent = `Halaman ${currentPage}`;
  }

  // Toolbar page indicator
  if (pageNavInfoEl) {
    if (pageFlipInstance && pageFlipInstance.getOrientation() === "landscape" && currentPage > 1 && currentPage < totalPages) {
      const leftP = currentPage % 2 === 0 ? currentPage : currentPage - 1;
      const rightP = Math.min(totalPages, leftP + 1);
      pageNavInfoEl.textContent = `Hal. ${leftP}–${rightP} / ${totalPages}`;
    } else {
      pageNavInfoEl.textContent = `Hal. ${currentPage} / ${totalPages}`;
    }
  }

  // Duration
  const readingDurationEl = document.getElementById("readingDuration");
  if (readingDurationEl) {
    readingDurationEl.textContent = `⏱️ ${getTotalDuration()} Menit`;
  }
}

function updateBookInfoUi(book) {
  document.getElementById("bookTitle").textContent = book.title || "Membaca Buku";

  const titleEl = document.getElementById("sidebarBookTitle");
  if (titleEl) titleEl.textContent = book.title || "Judul Buku";

  const authorEl = document.getElementById("sidebarBookAuthor");
  if (authorEl) authorEl.textContent = book.author || "Tim Edukasi";

  // Literacy Level Badge
  const levelEl = document.getElementById("sidebarBookLevel");
  if (levelEl) {
    const rawLevel = String(book.literacy_level || "").toUpperCase();
    const isTinggi = rawLevel === "TINGGI" || rawLevel === "LANJUTAN";
    levelEl.textContent = isTinggi ? "Tingkat: Tinggi (Lanjutan)" : "Tingkat: Rendah (Dasar)";
    levelEl.className = isTinggi ? "level-badge level-tinggi" : "level-badge level-rendah";
  }

  // Category Badge
  const catEl = document.getElementById("sidebarBookCategory");
  if (catEl) {
    catEl.textContent = book.category || "Umum";
  }

  // Thumbnail cover
  const coverEl = document.getElementById("sidebarBookCover");
  if (coverEl && book.cover) {
    coverEl.innerHTML = `<img src="${book.cover}" alt="Cover" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">`;
  }
}

function updateControlsState() {
  const currentP = getActivePageNumber();
  const isFirst = currentP <= 1;
  const isLast = currentP >= totalPages;

  if (prevBtn) prevBtn.disabled = isFirst;
  if (stagePrevBtn) stagePrevBtn.disabled = isFirst;
  if (nextBtn) nextBtn.disabled = isLast;
  if (stageNextBtn) stageNextBtn.disabled = isLast;
}

function getActivePageNumber() {
  if (!pageFlipInstance) return 1;
  const pageIdx = pageFlipInstance.getCurrentPageIndex();
  return clampPage(pageIdx + 1);
}

function clampPage(page) {
  return Math.min(Math.max(1, Number(page) || 1), totalPages);
}

function getTotalDuration() {
  return baseDuration + Math.max(1, Math.floor((Date.now() - readingStartedAt) / 60000));
}

/**
 * Flipbook Actions
 */
function handleFlipPrev() {
  if (pageFlipInstance) {
    pageFlipInstance.flipPrev();
  }
}

function handleFlipNext() {
  if (pageFlipInstance) {
    pageFlipInstance.flipNext();
  }
}

window.handleFlipPrev = handleFlipPrev;
window.handleFlipNext = handleFlipNext;

/**
 * Zoom Controls
 */
function handleZoomIn() {
  zoomLevel = Math.min(1.5, Number((zoomLevel + 0.1).toFixed(2)));
  applyZoom();
}

function handleZoomOut() {
  zoomLevel = Math.max(0.7, Number((zoomLevel - 0.1).toFixed(2)));
  applyZoom();
}

window.resetZoom = function() {
  zoomLevel = 1.0;
  applyZoom();
};

function applyZoom() {
  if (zoomPercentEl) {
    zoomPercentEl.textContent = `${Math.round(zoomLevel * 100)}%`;
  }
  if (flipbookViewport) {
    flipbookViewport.style.transformOrigin = "center center";
    flipbookViewport.style.transform = `scale(${zoomLevel})`;
  }
}

/**
 * Fullscreen Toggle & Event Listeners
 */
window.toggleFullscreen = function() {
  const stage = document.getElementById("readerMainStage") || document.getElementById("readerStage");
  if (!stage) return;

  if (!document.fullscreenElement) {
    stage.requestFullscreen().catch(() => {});
  } else {
    document.exitFullscreen().catch(() => {});
  }
};

document.addEventListener("fullscreenchange", () => {
  const btn = document.getElementById("fullscreenBtn");
  if (btn) {
    btn.innerHTML = document.fullscreenElement ? "🗗 Keluar Penuh" : "⛶ Layar Penuh";
  }
  handleContainerResize(true);
});

let resizeDebounceTimer = null;

function handleContainerResize(force = false) {
  clearTimeout(resizeDebounceTimer);
  resizeDebounceTimer = setTimeout(() => {
    const stage = document.getElementById("readerStage") || document.getElementById("readerMainStage");
    const currentW = stage ? stage.clientWidth : window.innerWidth;
    const currentH = window.innerHeight;
    const wDiff = Math.abs(currentW - lastRenderedStageWidth);
    const hDiff = Math.abs(currentH - lastRenderedStageHeight);

    if (force || wDiff > 25 || hDiff > 25 || document.fullscreenElement) {
      if (pageFlipInstance && pdfDocument) {
        const currentPage = getActivePageNumber();
        try {
          pageFlipInstance.destroy();
        } catch {}
        renderedPages.clear();
        buildAndMountFlipbook(currentPage);
      }
    }
  }, 200);
}

window.addEventListener("resize", () => handleContainerResize(false));

// Observe layout changes dynamically (e.g. sidebar toggle or viewport shift)
if (window.ResizeObserver) {
  const stageEl = document.getElementById("readerStage") || document.getElementById("readerMainStage");
  if (stageEl) {
    const ro = new ResizeObserver(() => {
      handleContainerResize(false);
    });
    ro.observe(stageEl);
  }
}

/**
 * Mobile Sidebar Collapse Toggle
 */
window.toggleMobileSidebar = function() {
  const sidebar = document.getElementById("readerSidebar");
  const btn = document.getElementById("sidebarToggleBtn");
  if (!sidebar) return;

  sidebar.classList.toggle("collapsed-mobile");
  const isCollapsed = sidebar.classList.contains("collapsed-mobile");
  if (btn) {
    btn.textContent = isCollapsed ? "📊 Lihat Progres" : "✕ Tutup Progres";
  }
  setTimeout(() => handleContainerResize(true), 80);
};

/**
 * Save Reading Progress to Server (Debounced)
 */
function triggerDebouncedProgressSync(progress, lastPage) {
  clearTimeout(saveDebounceTimer);
  saveDebounceTimer = setTimeout(() => {
    sendProgressToServer(progress, lastPage);
  }, 1200);
}

async function sendProgressToServer(progress, lastPage) {
  if (auth.role !== "SISWA" || isSavingProgress) return;

  isSavingProgress = true;
  try {
    const session = await apiPost("/reading/progress", {
      bookId: Number(bookId),
      duration: getTotalDuration(),
      progress: progress,
      lastPage: lastPage
    });
    if (session && session.duration) {
      baseDuration = session.duration;
    }
  } catch (error) {
    console.warn("Auto-save reading progress failed:", error);
  } finally {
    isSavingProgress = false;
  }
}

function startDurationTracker() {
  setInterval(() => {
    const durEl = document.getElementById("readingDuration");
    if (durEl) {
      durEl.textContent = `⏱️ ${getTotalDuration()} Menit`;
    }
  }, 60000);
}

/**
 * Realistic physical paper turn sound effect using Web Audio API synthesis
 */
function playPageTurnSound() {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    const ctx = new AudioContext();

    // White noise buffer for paper texture
    const bufferSize = ctx.sampleRate * 0.12;
    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
    const data = buffer.getChannelData(0);
    for (let i = 0; i < bufferSize; i++) {
      data[i] = Math.random() * 2 - 1;
    }

    const noise = ctx.createBufferSource();
    noise.buffer = buffer;

    // Filter simulating frequency profile of turning book paper
    const filter = ctx.createBiquadFilter();
    filter.type = "bandpass";
    filter.frequency.setValueAtTime(1200, ctx.currentTime);
    filter.frequency.exponentialRampToValueAtTime(500, ctx.currentTime + 0.12);
    filter.Q.setValueAtTime(2.5, ctx.currentTime);

    // Gain envelope with rapid attack and smooth exponential decay
    const gain = ctx.createGain();
    gain.gain.setValueAtTime(0.06, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.12);

    noise.connect(filter);
    filter.connect(gain);
    gain.connect(ctx.destination);

    noise.start();
    noise.stop(ctx.currentTime + 0.13);
  } catch {}
}

/**
 * Kuis Pemahaman Integration
 * Uses existing backend:
 * - GET /api/books/{bookId}/quiz
 * - POST /api/quizzes/submit
 */
window.openBookQuiz = async function() {
  const modal = document.getElementById("quizModal");
  const modalTitle = document.getElementById("modalQuizTitle");
  const modalDesc = document.getElementById("modalQuizDesc");
  const container = document.getElementById("quizQuestionsContainer");
  const form = document.getElementById("quizForm");
  const resultPanel = document.getElementById("quizResultPanel");

  if (!modal) return;
  modal.classList.remove("hidden");
  form.classList.remove("hidden");
  resultPanel.classList.add("hidden");

  container.innerHTML = `
    <div style="text-align:center;padding:30px 10px;">
      <div class="page-spinner" style="margin:0 auto 12px;"></div>
      <p style="color:var(--text-secondary);font-size:0.88rem;">Mengambil soal Kuis Pemahaman dari sistem...</p>
    </div>
  `;

  try {
    const quiz = await apiGet(`/books/${bookId}/quiz`);
    currentLoadedQuiz = quiz;

    modalTitle.textContent = quiz.title || "Kuis Pemahaman Buku";
    modalDesc.textContent = quiz.description || "Uji pemahamanmu setelah membaca tuntas buku ini.";

    if (!quiz.questions || quiz.questions.length === 0) {
      container.innerHTML = `
        <div style="text-align:center;padding:30px 10px;">
          <div style="font-size:2.5rem;margin-bottom:8px;">📚</div>
          <h4 style="font-weight:700;color:var(--text-primary);">Belum Ada Pertanyaan</h4>
          <p style="color:var(--text-secondary);font-size:0.85rem;max-width:380px;margin:0 auto 16px;">
            Guru belum menambahkan butir soal untuk kuis buku ini. Catatan selesai membacamu tetap tersimpan 100%!
          </p>
          <a href="dashboard-siswa.html" class="btn btn-outline btn-sm">Buka Dashboard Siswa</a>
        </div>
      `;
      document.getElementById("quizSubmitBtn").style.display = "none";
      return;
    }

    document.getElementById("quizSubmitBtn").style.display = "inline-flex";

    // Render questions matching the existing format
    container.innerHTML = quiz.questions.map((q, idx) => {
      const fieldName = `question-${q.questionId}`;
      const options = Array.isArray(q.options) ? q.options : [];

      if (q.type === "MCQ") {
        return `
          <div class="quiz-active-card" style="margin:16px 0;padding:20px;box-shadow:none;">
            <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
              <span class="quiz-badge quiz-badge-notstarted">Soal #${idx + 1}</span>
              <span style="font-size:0.8rem;color:var(--text-secondary);font-weight:700;">${q.points || 1} Poin</span>
            </div>
            <div class="quiz-active-question-text" style="font-size:1.05rem;margin-bottom:16px;">
              ${escapeHtml(q.question)}
            </div>
            <div class="quiz-options-container">
              ${options.map((opt, oIdx) => {
                const letter = String.fromCharCode(65 + oIdx);
                return `
                  <label class="quiz-option-item" style="padding:12px 16px;">
                    <input type="radio" name="${fieldName}" class="quiz-option-radio-input" value="${escapeHtml(opt)}" required />
                    <span class="quiz-option-badge-letter" style="width:30px;height:30px;font-size:0.85rem;">${letter}</span>
                    <span class="quiz-option-item-text" style="font-size:0.92rem;">${escapeHtml(opt)}</span>
                  </label>
                `;
              }).join("")}
            </div>
          </div>
        `;
      }

      return `
        <div class="quiz-active-card" style="margin:16px 0;padding:20px;box-shadow:none;">
          <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
            <span class="quiz-badge quiz-badge-notstarted">Soal #${idx + 1} (Esai)</span>
            <span style="font-size:0.8rem;color:var(--text-secondary);font-weight:700;">${q.points || 1} Poin</span>
          </div>
          <div class="quiz-active-question-text" style="font-size:1.05rem;margin-bottom:16px;">
            ${escapeHtml(q.question)}
          </div>
          <textarea class="quiz-essay-textarea" name="${fieldName}" rows="3" placeholder="Tuliskan jawaban pemahamanmu di sini..." required></textarea>
        </div>
      `;
    }).join("");
  } catch (err) {
    console.warn("Quiz load error:", err);
    container.innerHTML = `
      <div style="text-align:center;padding:30px 10px;">
        <div style="font-size:2.5rem;margin-bottom:8px;">📝</div>
        <h4 style="font-weight:700;color:var(--text-primary);">Kuis Belum Dibuat oleh Guru</h4>
        <p style="color:var(--text-secondary);font-size:0.85rem;max-width:400px;margin:0 auto 16px;">
          Guru belum menyiapkan kuis untuk buku ini. Selamat, progres membaca 100% dan durasi belajarmu telah berhasil dicatat di sistem!
        </p>
        <div style="display:flex;gap:10px;justify-content:center;">
          <button type="button" class="btn btn-outline btn-sm" onclick="closeQuizModal()">Lanjut Membaca</button>
          <a href="dashboard-siswa.html" class="btn btn-primary btn-sm">Ke Dashboard Siswa</a>
        </div>
      </div>
    `;
    const submitBtn = document.getElementById("quizSubmitBtn");
    if (submitBtn) submitBtn.style.display = "none";
  }
};

window.closeQuizModal = function() {
  const modal = document.getElementById("quizModal");
  if (modal) modal.classList.add("hidden");
};

window.handleQuizSubmit = async function(event) {
  event.preventDefault();
  if (!currentLoadedQuiz) return;

  const form = document.getElementById("quizForm");
  const formData = new FormData(form);
  const answers = currentLoadedQuiz.questions.map(q => ({
    questionId: q.questionId,
    response: String(formData.get(`question-${q.questionId}`) || "")
  }));

  if (answers.some(a => !a.response.trim())) {
    alert("Harap jawab semua pertanyaan kuis terlebih dahulu.");
    return;
  }

  const submitBtn = document.getElementById("quizSubmitBtn");
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Mengirim...";
  }

  try {
    const result = await apiPost("/quizzes/submit", {
      quizId: currentLoadedQuiz.quizId,
      answers: answers
    });

    form.classList.add("hidden");
    const resultPanel = document.getElementById("quizResultPanel");
    resultPanel.classList.remove("hidden");
    resultPanel.innerHTML = `
      <div style="font-size:3.5rem;margin-bottom:12px;">🎉</div>
      <h3 style="font-size:1.3rem;font-weight:800;color:var(--text-primary);margin-bottom:6px;">Jawaban Berhasil Dikirim!</h3>
      <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:16px;">
        Nilai akhir Anda: <strong style="color:var(--primary);font-size:1.5rem;">${result.score ?? 100} / 100</strong>
        <br />
        <span style="font-size:0.84rem;color:#64748B;">Benar: ${result.correctCount || 0} Soal &bull; Salah: ${result.wrongCount || 0} Soal</span>
      </p>
      <div style="display:flex;gap:12px;justify-content:center;margin-top:20px;flex-wrap:wrap;">
        <button type="button" class="btn btn-outline" onclick="closeQuizModal()">Tutup</button>
        <a href="dashboard-siswa.html" class="btn btn-primary">Buka Dashboard Siswa (Lihat Rincian & Pembahasan) &rarr;</a>
      </div>
    `;
  } catch (err) {
    alert(`Gagal mengirim kuis: ${err.message}`);
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = "🚀 Kirim Jawaban Kuis";
    }
  }
};

function ensurePdfJsReady() {
  if (!window.pdfjsLib) {
    throw new Error("pdf.js belum termuat. Periksa koneksi internet atau script asset.");
  }
}
