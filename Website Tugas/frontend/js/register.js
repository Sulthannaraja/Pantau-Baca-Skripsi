document.getElementById("registerBtn").addEventListener("click", async () => {
  try {
    const body = {
      name: document.getElementById("name").value,
      email: document.getElementById("email").value,
      password: document.getElementById("password").value,
      role: document.getElementById("role").value
    };
    await apiPost("/register", body);
    clearAuth();
    alert("Registrasi berhasil. Silakan login dengan akun baru Anda.");
    window.location.href = "login.html";
  } catch (e) {
    alert(e.message);
  }
});
