function showAlert(message, type = "danger") {
  const alertEl = document.getElementById("loginAlert");
  if (!alertEl) {
    alert(message);
    return;
  }
  alertEl.style.display = "block";
  if (type === "danger") {
    alertEl.style.background = "#FEF2F2";
    alertEl.style.color = "#991B1B";
    alertEl.style.border = "1.5px solid #FCA5A5";
  } else {
    alertEl.style.background = "#ECFDF5";
    alertEl.style.color = "#065F46";
    alertEl.style.border = "1.5px solid #6EE7B7";
  }
  alertEl.textContent = message;
}

function fillAccount(email, password) {
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");
  if (emailInput) emailInput.value = email;
  if (passwordInput) passwordInput.value = password;
  const alertEl = document.getElementById("loginAlert");
  if (alertEl) alertEl.style.display = "none";
}

async function handleLogin() {
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");
  const loginBtn = document.getElementById("loginBtn");
  const alertEl = document.getElementById("loginAlert");
  if (alertEl) alertEl.style.display = "none";

  const email = emailInput ? emailInput.value.trim() : "";
  const password = passwordInput ? passwordInput.value : "";

  if (!email || !password) {
    showAlert("Email/username dan password wajib diisi.", "danger");
    return;
  }

  const originalBtnHtml = loginBtn ? loginBtn.innerHTML : "Masuk Sekarang";
  if (loginBtn) {
    loginBtn.disabled = true;
    loginBtn.innerHTML = `<span>Memproses...</span>`;
  }

  try {
    const data = await apiPost("/login", { email, password });
    setDemoMode(false);
    setAuth(data);
    showAlert("Berhasil masuk! Mengalihkan...", "success");

    setTimeout(() => {
      if (data.role === "SISWA") {
        window.location.href = "dashboard-siswa.html";
      } else if (data.role === "GURU") {
        window.location.href = "dashboard-guru.html";
      } else {
        window.location.href = "dashboard-admin.html";
      }
    }, 400);
  } catch (e) {
    const emailLower = email.toLowerCase();
    const isAdminInput = emailLower === "admin" || emailLower === "admin@pantaubaca.com" || emailLower === "sulthantegarnaraja" || emailLower === "sulthantegarnaraja@admin.local";
    if (isNetworkError(e) && isAdminInput && (password === "admin123" || password === "kanadabismillah")) {
      setDemoMode(true);
      setAuth({ token: "demo-token", userId: 1, name: "Admin PantauBaca", role: "ADMIN" });
      showAlert("Masuk dalam mode offline admin...", "success");
      setTimeout(() => {
        window.location.href = "dashboard-admin.html";
      }, 400);
      return;
    }
    showAlert(e.message || "Gagal masuk. Periksa kembali akun Anda.", "danger");
  } finally {
    if (loginBtn) {
      loginBtn.disabled = false;
      loginBtn.innerHTML = originalBtnHtml;
    }
  }
}

const loginBtn = document.getElementById("loginBtn");
if (loginBtn) {
  loginBtn.addEventListener("click", handleLogin);
}

["email", "password"].forEach(id => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        handleLogin();
      }
    });
  }
});

