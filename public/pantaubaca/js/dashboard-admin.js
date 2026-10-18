if (!requireAuth(["ADMIN"])) {
  throw new Error("Unauthorized");
}

const uploadBtn = document.getElementById("uploadBtn");
uploadBtn.addEventListener("click", uploadBook);

(async function init() {
  try {
    await Promise.all([loadBooks(), loadUsers()]);
  } catch (e) {
    if (!isNetworkError(e)) throw e;
    renderBooksDemo();
    renderUsersDemo();
    uploadBtn.disabled = true;
    showInfo("Backend belum terhubung. Dashboard admin berjalan dalam mode demo.");
  }
})();

async function uploadBook() {
  try {
    const fileInput = document.getElementById("file");
    const title = document.getElementById("title").value.trim();
    const author = document.getElementById("author").value.trim();
    const year = document.getElementById("year")?.value.trim();
    const category = document.getElementById("category").value;
    const cover = document.getElementById("cover").value.trim();
    const file = fileInput.files[0];

    if (!title || !author || !file) {
      throw new Error("Judul, penulis, dan file PDF wajib diisi.");
    }

    const form = new FormData();
    form.append("title", title);
    form.append("author", author);
    if (year) {
      form.append("year", year);
    }
    form.append("category", category);

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
    alert("Buku berhasil diupload");
    document.getElementById("title").value = "";
    document.getElementById("author").value = "";
    if (document.getElementById("year")) document.getElementById("year").value = "";
    document.getElementById("cover").value = "";
    fileInput.value = "";
    await loadBooks();
  } catch (e) {
    alert(e.message);
  }
}

async function loadBooks() {
  const books = await apiGet("/books");
  const countEl = document.getElementById("adminBookCount");
  if (countEl) countEl.textContent = `${books.length} buku`;
  document.getElementById("adminBookBody").innerHTML = books.map(b => `
    <tr>
      <td>${escapeHtml(b.title)}</td><td>${escapeHtml(b.author)}</td><td>${escapeHtml(b.year ? String(b.year) : "-")}</td><td>${escapeHtml(b.category || "-")}</td>
      <td>
        <button class="btn btn-outline" onclick="editBook('${encodeURIComponent(JSON.stringify(b))}')">Edit</button>
        <button class="btn btn-outline" onclick="deleteBook(${b.id})">Hapus</button>
      </td>
    </tr>
  `).join("");
}

async function loadUsers() {
  const users = await apiGet("/users");
  const countEl = document.getElementById("adminUserCount");
  if (countEl) countEl.textContent = `${users.length} user`;
  document.getElementById("adminUserBody").innerHTML = users.map(u => `
    <tr>
      <td>${escapeHtml(u.name)}</td><td>${escapeHtml(u.email)}</td><td>${escapeHtml(u.role)}</td>
      <td>
        <button class="btn btn-outline" onclick="editUser('${encodeURIComponent(JSON.stringify(u))}')">Edit</button>
        ${Number(u.id) === Number(getAuth().userId) ? '<span class="text-link">Akun aktif</span>' : `<button class="btn btn-outline" onclick="deleteUser(${u.id})">Hapus</button>`}
      </td>
    </tr>
  `).join("");
}

window.deleteBook = async function(id) {
  const ok = await appConfirm("Hapus buku ini?", { isDanger: true, confirmText: "Hapus" });
  if (!ok) return;
  await apiDelete(`/books/${id}`);
  showToast("Buku berhasil dihapus.", "success");
  await loadBooks();
};

window.editBook = async function(encodedBook) {
  const book = JSON.parse(decodeURIComponent(encodedBook));
  const title = await appPrompt("Judul buku", "Masukkan judul...", book.title);
  if (title === null) return;
  const author = await appPrompt("Penulis buku", "Nama penulis...", book.author);
  if (author === null) return;
  const year = await appPrompt("Tahun terbit", "Contoh: 2024", book.year ? String(book.year) : "");
  if (year === null) return;
  const category = await appPrompt("Kategori buku", "Kategori...", book.category || "");
  if (category === null) return;
  const cover = await appPrompt("URL cover", "https://...", book.cover || "");
  if (cover === null) return;

  await apiPatch(`/books/${book.id}`, {
    title: title.trim(),
    author: author.trim(),
    year: year.trim() ? parseInt(year.trim(), 10) : null,
    category: category.trim() || null,
    cover: cover.trim() || null
  });
  showToast("Data buku berhasil diperbarui.", "success");
  await loadBooks();
};

window.editUser = async function(encodedUser) {
  const user = JSON.parse(decodeURIComponent(encodedUser));
  const name = await appPrompt("Nama user", "Nama lengkap...", user.name);
  if (name === null) return;
  const email = await appPrompt("Email user", "Email...", user.email);
  if (email === null) return;
  const role = await appPrompt("Role user (ADMIN, GURU, atau SISWA)", "ADMIN / GURU / SISWA", user.role);
  if (role === null) return;

  await apiPatch(`/users/${user.id}`, {
    name: name.trim(),
    email: email.trim(),
    role: role.trim().toUpperCase()
  });
  showToast("Data pengguna berhasil diperbarui.", "success");
  await loadUsers();
};

window.deleteUser = async function(id) {
  if (Number(id) === Number(getAuth().userId)) {
    showToast("Akun yang sedang dipakai tidak bisa dihapus.", "error");
    return;
  }
  const ok = await appConfirm("Hapus user ini?", { isDanger: true, confirmText: "Hapus" });
  if (!ok) return;
  await apiDelete(`/users/${id}`);
  showToast("User berhasil dihapus.", "success");
  await loadUsers();
};

function renderBooksDemo() {
  const books = demoBooks();
  const countEl = document.getElementById("adminBookCount");
  if (countEl) countEl.textContent = `${books.length} buku`;
  document.getElementById("adminBookBody").innerHTML = books.map(b => `
    <tr>
      <td>${escapeHtml(b.title)}</td><td>${escapeHtml(b.author)}</td><td>${escapeHtml(b.year ? String(b.year) : "-")}</td><td>${escapeHtml(b.category || "-")}</td>
      <td><button class="btn btn-outline" disabled>Hapus</button></td>
    </tr>
  `).join("");
}

function renderUsersDemo() {
  const users = demoUsers();
  const countEl = document.getElementById("adminUserCount");
  if (countEl) countEl.textContent = `${users.length} user`;
  document.getElementById("adminUserBody").innerHTML = users.map(u => `
    <tr>
      <td>${escapeHtml(u.name)}</td><td>${escapeHtml(u.email)}</td><td>${escapeHtml(u.role)}</td>
      <td><button class="btn btn-outline" disabled>Hapus</button></td>
    </tr>
  `).join("");
}
