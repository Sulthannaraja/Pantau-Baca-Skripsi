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
    const category = document.getElementById("category").value;
    const cover = document.getElementById("cover").value.trim();
    const file = fileInput.files[0];

    if (!title || !author || !file) {
      throw new Error("Judul, penulis, dan file PDF wajib diisi.");
    }

    const form = new FormData();
    form.append("title", title);
    form.append("author", author);
    form.append("category", category);
    form.append("cover", cover);
    form.append("file", file);
    await apiPost("/books/upload", form, true);
    alert("Buku berhasil diupload");
    document.getElementById("title").value = "";
    document.getElementById("author").value = "";
    document.getElementById("cover").value = "";
    fileInput.value = "";
    await loadBooks();
  } catch (e) {
    alert(e.message);
  }
}

async function loadBooks() {
  const books = await apiGet("/books");
  document.getElementById("adminBookBody").innerHTML = books.map(b => `
    <tr>
      <td>${escapeHtml(b.title)}</td><td>${escapeHtml(b.author)}</td><td>${escapeHtml(b.category || "-")}</td>
      <td>
        <button class="btn btn-outline" onclick="editBook('${encodeURIComponent(JSON.stringify(b))}')">Edit</button>
        <button class="btn btn-outline" onclick="deleteBook(${b.id})">Hapus</button>
      </td>
    </tr>
  `).join("");
}

async function loadUsers() {
  const users = await apiGet("/users");
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
  if (!confirm("Hapus buku ini?")) return;
  await apiDelete(`/books/${id}`);
  await loadBooks();
};

window.editBook = async function(encodedBook) {
  const book = JSON.parse(decodeURIComponent(encodedBook));
  const title = prompt("Judul buku", book.title);
  if (title === null) return;
  const author = prompt("Penulis buku", book.author);
  if (author === null) return;
  const category = prompt("Kategori buku", book.category || "");
  if (category === null) return;
  const cover = prompt("URL cover", book.cover || "");
  if (cover === null) return;

  await apiPatch(`/books/${book.id}`, {
    title: title.trim(),
    author: author.trim(),
    category: category.trim() || null,
    cover: cover.trim() || null
  });
  await loadBooks();
};

window.editUser = async function(encodedUser) {
  const user = JSON.parse(decodeURIComponent(encodedUser));
  const name = prompt("Nama user", user.name);
  if (name === null) return;
  const email = prompt("Email user", user.email);
  if (email === null) return;
  const role = prompt("Role user: ADMIN, GURU, atau SISWA", user.role);
  if (role === null) return;

  await apiPatch(`/users/${user.id}`, {
    name: name.trim(),
    email: email.trim(),
    role: role.trim().toUpperCase()
  });
  await loadUsers();
};

window.deleteUser = async function(id) {
  if (Number(id) === Number(getAuth().userId)) {
    alert("Akun yang sedang dipakai tidak bisa dihapus.");
    return;
  }
  if (!confirm("Hapus user ini?")) return;
  await apiDelete(`/users/${id}`);
  await loadUsers();
};

function renderBooksDemo() {
  const books = demoBooks();
  document.getElementById("adminBookBody").innerHTML = books.map(b => `
    <tr>
      <td>${escapeHtml(b.title)}</td><td>${escapeHtml(b.author)}</td><td>${escapeHtml(b.category || "-")}</td>
      <td><button class="btn btn-outline" disabled>Hapus</button></td>
    </tr>
  `).join("");
}

function renderUsersDemo() {
  const users = demoUsers();
  document.getElementById("adminUserBody").innerHTML = users.map(u => `
    <tr>
      <td>${escapeHtml(u.name)}</td><td>${escapeHtml(u.email)}</td><td>${escapeHtml(u.role)}</td>
      <td><button class="btn btn-outline" disabled>Hapus</button></td>
    </tr>
  `).join("");
}
