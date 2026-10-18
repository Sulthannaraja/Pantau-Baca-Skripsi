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
      <div class="book-cover-wrap" style="position:relative;width:100%;aspect-ratio:3/4;overflow:hidden;border-top-left-radius:var(--radius-xl);border-top-right-radius:var(--radius-xl);">
        <img class="book-cover" src="${coverSrc(b.cover, b)}" alt="cover ${escapeHtml(b.title)}" onerror="handleCoverError(this, '${escapeHtml(b.title)}', '${escapeHtml(b.author)}', '${escapeHtml(b.category)}', '${escapeHtml(b.literacy_level)}')" style="width:100%;height:100%;object-fit:cover;" />
      </div>
      <div class="book-meta">
        <h3>${escapeHtml(b.title)}</h3>
        <p>${escapeHtml(b.author)}</p>
        <a class="btn btn-primary" href="reader.html?id=${b.id}">Baca Sekarang</a>
      </div>
    </article>
  `).join("");
}
