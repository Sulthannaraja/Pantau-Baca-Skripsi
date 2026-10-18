(async function () {
  try {
    const books = await apiGet("/books");
    const featuredBooks = books.slice(0, 5);
    document.getElementById("heroBookCount").textContent = `${books.length}+`;
    renderBooks(featuredBooks);
  } catch {
    const books = demoBooks();
    document.getElementById("heroBookCount").textContent = `${books.length}+`;
    renderBooks(books);
  }
})();

function renderBooks(books) {
  const wrap = document.getElementById("popularBooks");
  wrap.innerHTML = books.map(b => `
    <article class="book-card">
      <img class="book-cover" src="${coverSrc(b.cover)}" alt="cover ${escapeHtml(b.title)}" />
      <div class="book-meta">
        <h3>${escapeHtml(b.title)}</h3>
        <p>${escapeHtml(b.author)}</p>
        <a class="btn btn-primary" href="reader.html?id=${b.id}">Baca Sekarang</a>
      </div>
    </article>
  `).join("");
}
