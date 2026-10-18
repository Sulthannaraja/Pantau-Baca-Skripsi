requireAuth();

let allBooks = [];
(async function () {
  try {
    const books = await apiGet("/books");
    allBooks = books;
    fillCategories(books);
    render(books);
  } catch (e) {
    if (!isNetworkError(e)) throw e;
    allBooks = demoBooks();
    fillCategories(allBooks);
    render(allBooks);
    showInfo("Backend belum terhubung. Menampilkan katalog demo.");
  }
})();

const searchEl = document.getElementById("search");
const catEl = document.getElementById("categoryFilter");
searchEl.addEventListener("input", filter);
catEl.addEventListener("change", filter);

function fillCategories(books) {
  const defaults = ["Novel", "Cerpen", "Buku Pembelajaran"];
  const categories = [...new Set([...defaults, ...books.map(b => b.category).filter(Boolean)])];
  catEl.innerHTML += categories.map(c => `<option value="${c}">${c}</option>`).join("");
}

function filter() {
  const q = searchEl.value.toLowerCase();
  const c = catEl.value;
  const filtered = allBooks.filter(b => {
    const inText = `${b.title} ${b.author}`.toLowerCase().includes(q);
    const inCategory = !c || b.category === c;
    return inText && inCategory;
  });
  render(filtered);
}

function render(books) {
  const grid = document.getElementById("bookGrid");
  grid.innerHTML = books.map(b => `
    <article class="card book-card">
      <img class="book-cover" src="${coverSrc(b.cover)}" alt="cover ${escapeHtml(b.title)}" />
      <div class="book-meta">
        <h3>${escapeHtml(b.title)}</h3>
        <p>${escapeHtml(b.author)}</p>
        <a class="btn btn-primary" href="reader.html?id=${b.id}">Baca</a>
      </div>
    </article>
  `).join("");
}
