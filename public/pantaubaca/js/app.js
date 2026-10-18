const API_BASE = "/api";
const DEMO_MODE_KEY = "demoMode";
const AUTH_KEYS = ["token", "role", "userId", "name", DEMO_MODE_KEY];

function getAuth() {
  return {
    token: localStorage.getItem("token"),
    role: localStorage.getItem("role"),
    userId: localStorage.getItem("userId"),
    name: localStorage.getItem("name")
  };
}

function setAuth(data) {
  localStorage.setItem("token", data.token);
  localStorage.setItem("role", data.role);
  localStorage.setItem("userId", String(data.userId));
  localStorage.setItem("name", data.name);
}

function clearAuth() {
  AUTH_KEYS.forEach(key => localStorage.removeItem(key));
}

function authHeaders(extra = {}) {
  const { token } = getAuth();

  return {
    "Accept": "application/json",
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...extra,
  };
}

function getApiError(responseJson, status) {
  if (!responseJson) {
    return status >= 500 ? "Terjadi kesalahan server." : "Request gagal";
  }

  if (typeof responseJson.error === "string" && responseJson.error) {
    return responseJson.error;
  }

  if (typeof responseJson.message === "string" && responseJson.message) {
    return responseJson.message;
  }

  if (responseJson.errors && typeof responseJson.errors === "object") {
    return Object.values(responseJson.errors)
      .flat()
      .filter(Boolean)
      .join(" ") || "Request gagal";
  }

  return status >= 500 ? "Terjadi kesalahan server." : "Request gagal";
}

async function apiGet(path) {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: authHeaders()
  });
  if (!res.ok) {
    const errorJson = await res.json().catch(() => null);
    throw new Error(getApiError(errorJson, res.status));
  }
  return res.json();
}

async function apiPost(path, body, isFormData = false) {
  const headers = isFormData
    ? (getAuth().token ? { Authorization: `Bearer ${getAuth().token}` } : {})
    : authHeaders();

  const res = await fetch(`${API_BASE}${path}`, {
    method: "POST",
    headers,
    body: isFormData ? body : JSON.stringify(body)
  });
  if (!res.ok) {
    const errorJson = await res.json().catch(() => null);
    throw new Error(getApiError(errorJson, res.status));
  }
  return res.json();
}

async function apiPatch(path, body) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: "PATCH",
    headers: authHeaders(),
    body: JSON.stringify(body)
  });
  if (!res.ok) {
    const errorJson = await res.json().catch(() => null);
    throw new Error(getApiError(errorJson, res.status));
  }
  return res.json();
}

async function apiDelete(path) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: "DELETE",
    headers: authHeaders()
  });
  if (!res.ok) {
    const errorJson = await res.json().catch(() => null);
    throw new Error(getApiError(errorJson, res.status));
  }
  return res.json();
}

function escapeHtml(str) {
  return String(str ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function defaultCover(title = "PantauBaca", author = "Penulis", category = "Buku", level = "RENDAH") {
  const safeTitle = escapeHtml(String(title || "Buku PantauBaca").slice(0, 50));
  const safeAuthor = escapeHtml(String(author || "Penulis").slice(0, 30));
  const safeCategory = escapeHtml(String(category || "PantauBaca").slice(0, 24));
  const isLanjutan = String(level || "").toUpperCase() === "TINGGI" || String(level || "").toUpperCase() === "LANJUTAN";

  const bg1 = isLanjutan ? "#064E3B" : "#1E3A8A";
  const bg2 = isLanjutan ? "#022C22" : "#0F172A";
  const gold1 = "#F59E0B";
  const gold2 = "#FDE68A";

  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 400" width="100%" height="100%">
    <defs>
      <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="${bg1}"/>
        <stop offset="100%" stop-color="${bg2}"/>
      </linearGradient>
      <linearGradient id="gold" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" stop-color="${gold1}"/>
        <stop offset="100%" stop-color="${gold2}"/>
      </linearGradient>
      <linearGradient id="spine" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.22"/>
        <stop offset="50%" stop-color="#000000" stop-opacity="0.32"/>
        <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0.06"/>
      </linearGradient>
    </defs>
    <rect width="300" height="400" fill="url(#bg)" rx="6"/>
    <rect x="0" y="0" width="22" height="400" fill="url(#spine)"/>
    <line x1="22" y1="0" x2="22" y2="400" stroke="#000000" stroke-opacity="0.4" stroke-width="1.5"/>
    <line x1="23.5" y1="0" x2="23.5" y2="400" stroke="#FFFFFF" stroke-opacity="0.15" stroke-width="1"/>
    <rect x="34" y="24" width="236" height="352" fill="none" stroke="url(#gold)" stroke-width="1.5" stroke-opacity="0.6" rx="4"/>
    <rect x="39" y="29" width="226" height="342" fill="none" stroke="#FFFFFF" stroke-width="0.75" stroke-opacity="0.2" rx="2"/>
    <rect x="75" y="46" width="150" height="22" rx="11" fill="rgba(255,255,255,0.12)"/>
    <text x="150" y="61" fill="#E2E8F0" font-family="'Plus Jakarta Sans', system-ui, sans-serif" font-size="9" font-weight="700" letter-spacing="1.5" text-anchor="middle">${safeCategory}</text>
    <circle cx="150" cy="116" r="32" fill="rgba(255,255,255,0.06)" stroke="url(#gold)" stroke-width="1.5"/>
    <path d="M136 109c5-3 9-3 14 1 5-4 9-4 14-1v17c-5-3-9-3-14 1-5-4-9-4-14-1z" fill="#FBBF24"/>
    <path d="M150 110v18" stroke="#B45309" stroke-width="1.5"/>
    <foreignObject x="45" y="162" width="210" height="110">
      <div xmlns="http://www.w3.org/1999/xhtml" style="color:#FFFFFF;font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:800;line-height:1.35;text-align:center;word-break:break-word;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;padding:0 4px;">
        ${safeTitle}
      </div>
    </foreignObject>
    <line x1="110" y1="288" x2="190" y2="288" stroke="url(#gold)" stroke-width="1" stroke-opacity="0.8"/>
    <text x="150" y="310" fill="#CBD5E1" font-family="'Plus Jakarta Sans', system-ui, sans-serif" font-size="11" font-weight="600" text-anchor="middle">✍️ ${safeAuthor}</text>
    <rect x="70" y="340" width="160" height="22" rx="11" fill="rgba(15,23,42,0.6)"/>
    <text x="150" y="355" fill="#FCD34D" font-family="'Plus Jakarta Sans', system-ui, sans-serif" font-size="9.5" font-weight="700" letter-spacing="0.5" text-anchor="middle">📖 PantauBaca Digital</text>
  </svg>`;

  return "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svg);
}

function coverSrc(cover, book = null) {
  if (cover && typeof cover === "string" && cover.trim() && !cover.includes("dominant-baseline='middle' text-anchor='middle' fill='%234361EE'")) {
    const trimmed = cover.trim();
    if (trimmed.startsWith("http") || trimmed.startsWith("data:")) {
      return escapeHtml(trimmed);
    }
    return escapeHtml("/" + trimmed.replace(/^\/+/, ""));
  }

  if (book && book.id) {
    return `/covers/cover_${book.id}.jpg`;
  }

  if (book) {
    return defaultCover(book.title, book.author, book.category, book.literacy_level);
  }

  return defaultCover();
}

function handleCoverError(imgEl, title, author, category, level) {
  if (!imgEl) return;
  imgEl.onerror = null;
  imgEl.src = defaultCover(title, author, category, level);
}

async function renderBookCoverWithPdfJs(book, onRendered) {
  if (!book || !book.id) return null;
  const pdfjs = window.pdfjsLib;
  if (!pdfjs) return null;

  try {
    if (!pdfjs.GlobalWorkerOptions.workerSrc) {
      pdfjs.GlobalWorkerOptions.workerSrc = "/pantaubaca/js/pdf.worker.min.js";
    }

    const auth = getAuth() || {};
    const streamUrl = `/api/books/${book.id}/stream${auth.token ? '?token=' + encodeURIComponent(auth.token) : ''}`;

    const loadingTask = pdfjs.getDocument({
      url: streamUrl,
      httpHeaders: auth.token ? { Authorization: `Bearer ${auth.token}` } : {},
      cMapUrl: "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/",
      cMapPacked: true,
    });

    const pdf = await loadingTask.promise;
    const page = await pdf.getPage(1);
    const viewport = page.getViewport({ scale: 1.5 });

    const canvas = document.createElement("canvas");
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    const ctx = canvas.getContext("2d");

    await page.render({ canvasContext: ctx, viewport }).promise;
    const dataUrl = canvas.toDataURL("image/jpeg", 0.88);

    if (onRendered && typeof onRendered === "function") {
      onRendered(dataUrl);
    }

    // Persist to backend asynchronously
    try {
      await apiPost(`/books/${book.id}/cover`, { cover_data: dataUrl });
    } catch (saveErr) {
      console.warn("Could not save rendered cover to backend:", saveErr);
    }

    return dataUrl;
  } catch (err) {
    console.warn(`PDF.js failed to render cover for book ${book.id}:`, err);
    return null;
  }
}

function requireAuth(allowedRoles = []) {
  const auth = getAuth();
  if (!auth.token || !auth.role) {
    window.location.href = "login.html";
    return false;
  }
  if (allowedRoles.length && !allowedRoles.includes(auth.role)) {
    window.location.href = "login.html";
    return false;
  }
  return true;
}

function logout() {
  clearAuth();
  window.location.href = "login.html";
}

function isNetworkError(error) {
  if (!error) return false;
  const msg = String(error.message || "").toLowerCase();
  return msg.includes("failed to fetch") || msg.includes("networkerror") || msg.includes("load failed") || msg.includes("network request failed");
}

function setDemoMode(enabled) {
  localStorage.setItem(DEMO_MODE_KEY, enabled ? "1" : "0");
}

function isDemoMode() {
  return localStorage.getItem(DEMO_MODE_KEY) === "1";
}

function showInfo(message) {
  if (!message) return;
  const existing = document.getElementById("global-info-banner");
  if (existing) existing.remove();
  const banner = document.createElement("div");
  banner.id = "global-info-banner";
  banner.style.cssText = "position:sticky;top:0;z-index:9999;background:rgba(238,242,255,.96);color:#4361EE;padding:10px 16px;border-bottom:1.5px solid #C7D2FE;font-family:'Plus Jakarta Sans',sans-serif;font-size:13.5px;font-weight:600;backdrop-filter:blur(10px);text-align:center;";
  banner.textContent = message;
  document.body.prepend(banner);
}

function showToast(message, type = "info") {
  const toast = document.createElement("div");
  toast.style.cssText = `
    position: fixed; bottom: 24px; right: 24px; z-index: 10000;
    padding: 14px 22px; border-radius: 12px; font-weight: 600; font-size: 14px;
    background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#4361EE'};
    color: #FFFFFF; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
    display: flex; align-items: center; gap: 10px;
    animation: slideInToast 0.3s ease;
  `;
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

window.showToast = showToast;
window.alert = function(msg) {
  const message = String(msg).toLowerCase();
  const type = (message.includes('gagal') || message.includes('error') || message.includes('wajib')) ? 'error' : 'info';
  showToast(msg, type);
};

window.appConfirm = function(message, options = {}) {
  const confirmText = options.confirmText || "OK";
  const cancelText = options.cancelText || "Batal";
  const isDanger = Boolean(options.isDanger);

  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.style.cssText = "position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:99999;display:flex;align-items:center;justify-content:center;animation:fadeIn 0.2s;";

    const modal = document.createElement("div");
    modal.style.cssText = "background:#fff;border-radius:16px;padding:24px;width:90%;max-width:400px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.2);transform:scale(0.95);animation:zoomIn 0.2s forwards;";

    const title = document.createElement("h3");
    title.style.cssText = "margin:0 0 16px 0;font-size:1.15rem;color:#1E293B;font-weight:700;line-height:1.4;";
    title.textContent = message;

    const actionDiv = document.createElement("div");
    actionDiv.style.cssText = "display:flex;justify-content:flex-end;gap:10px;margin-top:20px;";

    const btnCancel = document.createElement("button");
    btnCancel.type = "button";
    btnCancel.textContent = cancelText;
    btnCancel.style.cssText = "padding:8px 18px;border:1.5px solid #CBD5E1;background:transparent;border-radius:6px;cursor:pointer;font-weight:600;color:#64748B;font-size:0.95rem;transition:all 0.15s;";
    btnCancel.onmouseenter = () => btnCancel.style.background = "#F1F5F9";
    btnCancel.onmouseleave = () => btnCancel.style.background = "transparent";

    const btnOk = document.createElement("button");
    btnOk.type = "button";
    btnOk.textContent = confirmText;
    const btnBg = isDanger ? "#EF4444" : "#4361EE";
    btnOk.style.cssText = `padding:8px 22px;border:none;background:${btnBg};border-radius:6px;cursor:pointer;font-weight:600;color:#fff;font-size:0.95rem;box-shadow:0 2px 8px ${isDanger ? 'rgba(239,68,68,0.3)' : 'rgba(67,97,238,0.3)'};transition:all 0.15s;`;
    btnOk.onmouseenter = () => btnOk.style.opacity = "0.9";
    btnOk.onmouseleave = () => btnOk.style.opacity = "1";

    const cleanup = (val) => {
      document.removeEventListener("keydown", onKeyDown);
      overlay.remove();
      resolve(val);
    };

    btnCancel.onclick = () => cleanup(false);
    btnOk.onclick = () => cleanup(true);

    const onKeyDown = (e) => {
      if (e.key === "Escape") cleanup(false);
      if (e.key === "Enter") cleanup(true);
    };
    document.addEventListener("keydown", onKeyDown);

    overlay.onclick = (e) => {
      if (e.target === overlay) cleanup(false);
    };

    actionDiv.append(btnCancel, btnOk);
    modal.append(title, actionDiv);
    overlay.append(modal);
    document.body.append(overlay);

    setTimeout(() => btnOk.focus(), 50);
  });
};

window.appPrompt = function(message, placeholder = "", defaultValue = "") {
  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.style.cssText = "position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:99999;display:flex;align-items:center;justify-content:center;animation:fadeIn 0.2s;";
    
    const modal = document.createElement("div");
    modal.style.cssText = "background:#fff;border-radius:16px;padding:24px;width:90%;max-width:400px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.2);transform:scale(0.95);animation:zoomIn 0.2s forwards;";
    
    const title = document.createElement("h3");
    title.style.cssText = "margin:0 0 12px 0;font-size:1.15rem;color:#1E293B;font-weight:700;";
    title.textContent = message;
    
    const input = document.createElement("input");
    input.type = "text";
    input.placeholder = placeholder;
    input.value = defaultValue || "";
    input.style.cssText = "width:100%;padding:10px 14px;border:1.5px solid #CBD5E1;border-radius:8px;font-size:1rem;margin-bottom:20px;outline:none;transition:all 0.2s;";
    input.onfocus = () => input.style.borderColor = "#4361EE";
    input.onblur = () => input.style.borderColor = "#CBD5E1";
    
    const actionDiv = document.createElement("div");
    actionDiv.style.cssText = "display:flex;justify-content:flex-end;gap:10px;";
    
    const btnCancel = document.createElement("button");
    btnCancel.type = "button";
    btnCancel.textContent = "Batal";
    btnCancel.style.cssText = "padding:8px 16px;border:1.5px solid #CBD5E1;background:transparent;border-radius:6px;cursor:pointer;font-weight:600;color:#64748B;";
    
    const btnOk = document.createElement("button");
    btnOk.type = "button";
    btnOk.textContent = "OK";
    btnOk.style.cssText = "padding:8px 20px;border:none;background:#4361EE;border-radius:6px;cursor:pointer;font-weight:600;color:#fff;";
    
    const cleanup = (val) => {
      document.removeEventListener("keydown", onKeyDown);
      overlay.remove();
      resolve(val);
    };

    btnCancel.onclick = () => cleanup(null);
    btnOk.onclick = () => cleanup(input.value);

    input.onkeydown = (e) => {
      if (e.key === "Enter") cleanup(input.value);
    };

    const onKeyDown = (e) => {
      if (e.key === "Escape") cleanup(null);
    };
    document.addEventListener("keydown", onKeyDown);

    overlay.onclick = (e) => {
      if (e.target === overlay) cleanup(null);
    };
    
    actionDiv.append(btnCancel, btnOk);
    modal.append(title, input, actionDiv);
    overlay.append(modal);
    document.body.append(overlay);
    
    setTimeout(() => {
      input.focus();
      if (input.value) input.select();
    }, 100);
  });
};

function demoBooks() {
  return [
    { id: 1, title: "Literasi Digital Dasar", author: "Tim Edukasi", year: 2023, category: "Literasi", cover: "" },
    { id: 2, title: "Belajar Internet Aman", author: "Kementerian Edu", year: 2022, category: "Keamanan", cover: "" },
    { id: 3, title: "Pemrograman Untuk Pelajar", author: "Ruang Belajar", year: 2024, category: "Teknologi", cover: "" }
  ];
}

function demoReadingReport() {
  const now = new Date().toISOString();
  return [
    { logId: 1, userId: 11, userName: "Alya", bookId: 1, bookTitle: "Literasi Digital Dasar", duration: 35, progress: 68, lastPage: 42, updatedAt: now },
    { logId: 2, userId: 12, userName: "Raka", bookId: 2, bookTitle: "Belajar Internet Aman", duration: 20, progress: 44, lastPage: 18, updatedAt: now },
    { logId: 3, userId: 11, userName: "Alya", bookId: 3, bookTitle: "Pemrograman Untuk Pelajar", duration: 55, progress: 87, lastPage: 90, updatedAt: now }
  ];
}

function demoUsers() {
  return [
    { id: 1, name: "sulthantegarnaraja", email: "sulthantegarnaraja@admin.local", role: "ADMIN" },
    { id: 2, name: "Bu Dini", email: "dini@guru.local", role: "GURU" },
    { id: 3, name: "Budi", email: "budi@siswa.local", role: "SISWA" }
  ];
}

/* Feature Section Switcher (Single-Feature View) */
function switchFeature(featureId, evt) {
  if (evt && typeof evt.preventDefault === "function") {
    evt.preventDefault();
  }

  // Close mobile sidebar if open
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  if (sidebar && sidebar.classList.contains('open')) {
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
  }

  const sections = document.querySelectorAll('.feature-section');
  if (!sections.length) return;

  let target = document.getElementById(featureId);
  if (!target) {
    target = sections[0];
    featureId = target.id;
  }

  sections.forEach(s => {
    s.classList.remove('active');
    s.style.display = 'none';
  });

  target.classList.add('active');
  target.style.display = 'block';

  // Update active state on sidebar navigation links
  document.querySelectorAll('.sidebar-nav a').forEach(a => {
    const dataFeat = a.getAttribute('data-feature');
    const href = a.getAttribute('href');
    if (dataFeat === featureId || href === '#' + featureId) {
      a.classList.add('active');
    } else if (dataFeat || (href && href.startsWith('#'))) {
      a.classList.remove('active');
    }
  });

  // Update URL hash without causing harsh jump
  if (window.history && window.history.replaceState) {
    window.history.replaceState(null, null, '#' + featureId);
  }

  // Hook for charts / page-specific re-renders
  if (typeof window.onFeatureSwitched === "function") {
    window.onFeatureSwitched(featureId);
  }

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.switchFeature = switchFeature;

function initFeatureTabs() {
  const sections = document.querySelectorAll('.feature-section');
  if (sections.length) {
    const rawHash = window.location.hash ? window.location.hash.replace('#', '') : '';
    const hash = rawHash.split('?')[0];

    if (hash && document.getElementById(hash) && document.getElementById(hash).classList.contains('feature-section')) {
      switchFeature(hash);
    } else {
      switchFeature(sections[0].id);
    }
  }

  // Also highlight external page links if active
  const currentPath = window.location.pathname.replace(/^\//, '');
  if (currentPath) {
    document.querySelectorAll('.sidebar-nav a').forEach(a => {
      const href = a.getAttribute('href');
      if (href && !href.startsWith('#') && (currentPath === href || currentPath.endsWith(href))) {
        a.classList.add('active');
      }
    });
  }
}

/* Mobile Sidebar Drawer Navigation Helpers */
function toggleMobileSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  if (sidebar) sidebar.classList.toggle('open');
  if (overlay) overlay.classList.toggle('active');
}

function closeMobileSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  if (sidebar) sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('active');
}

window.toggleMobileSidebar = toggleMobileSidebar;
window.closeMobileSidebar = closeMobileSidebar;

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeMobileSidebar();
  }
});

window.addEventListener('DOMContentLoaded', initFeatureTabs);


