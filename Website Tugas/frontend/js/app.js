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
  const res = await fetch(`${API_BASE}${path}`, { headers: authHeaders() });
  if (!res.ok) throw new Error(getApiError(await safeJson(res), res.status));
  return res.json();
}

async function apiPost(path, body, isForm = false) {
  const options = {
    method: "POST",
    headers: isForm
      ? { Authorization: `Bearer ${getAuth().token || ""}` }
      : authHeaders(),
    body: isForm ? body : JSON.stringify(body),
  };
  const res = await fetch(`${API_BASE}${path}`, options);
  if (!res.ok) throw new Error(getApiError(await safeJson(res), res.status));
  return res.json();
}

async function apiPatch(path, body) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: "PATCH",
    headers: authHeaders(),
    body: JSON.stringify(body),
  });
  if (!res.ok) throw new Error(getApiError(await safeJson(res), res.status));
  return res.json();
}

async function apiDelete(path) {
  const res = await fetch(`${API_BASE}${path}`, { method: "DELETE", headers: authHeaders() });
  if (!res.ok) throw new Error(getApiError(await safeJson(res), res.status));
}

async function safeJson(res) {
  try {
    return await res.json();
  } catch {
    const text = await res.text();
    return { error: text || "Response tidak valid." };
  }
}

function requireAuth(roles = []) {
  const auth = getAuth();
  if (!auth.token) {
    window.location.href = "login.html";
    return false;
  }
  if (roles.length > 0 && !roles.includes(auth.role)) {
    alert("Akses ditolak untuk role Anda.");
    window.location.href = "index.html";
    return false;
  }
  return true;
}

function logout() {
  clearAuth();
  window.location.href = "login.html";
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function coverSrc(cover) {
  if (!cover) return "https://via.placeholder.com/320x220?text=Cover";
  if (cover.startsWith("/api/")) return cover;
  return cover;
}

function isNetworkError(error) {
  return String(error?.message || "").toLowerCase().includes("failed to fetch");
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
  banner.style.cssText = "position:sticky;top:0;z-index:9999;background:rgba(202,240,248,.96);color:#03045e;padding:10px 14px;border-bottom:1px solid rgba(0,119,182,.18);font-family:Poppins,Open Sans,sans-serif;font-size:14px;backdrop-filter:blur(10px);";
  banner.textContent = message;
  document.body.prepend(banner);
}

function demoBooks() {
  return [
    { id: 1, title: "Literasi Digital Dasar", author: "Tim Edukasi", category: "Literasi", cover: "" },
    { id: 2, title: "Belajar Internet Aman", author: "Kementerian Edu", category: "Keamanan", cover: "" },
    { id: 3, title: "Pemrograman Untuk Pelajar", author: "Ruang Belajar", category: "Teknologi", cover: "" }
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
