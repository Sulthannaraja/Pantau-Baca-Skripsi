if (!requireAuth(["SISWA", "ADMIN"])) {
  throw new Error("Unauthorized");
}

(async function () {
  let data = [];
  try {
    data = await apiGet("/reading/report");
  } catch (e) {
    if (!isNetworkError(e)) throw e;
    data = demoReadingReport();
    showInfo("Backend belum terhubung. Menampilkan data demo sementara.");
  }

  const totalDuration = data.reduce((acc, i) => acc + i.duration, 0);
  const uniqueBooks = new Set(data.map(i => i.bookId));

  document.getElementById("totalDuration").textContent = `${totalDuration} menit`;
  document.getElementById("totalBooks").textContent = uniqueBooks.size;
  document.getElementById("targetInfo").textContent = `${uniqueBooks.size}/10`;

  document.getElementById("historyBody").innerHTML = data.length ? data.map(i => `
    <tr>
      <td>${escapeHtml(i.bookTitle)}</td>
      <td>${i.duration} menit</td>
      <td>${i.progress}%</td>
      <td>${i.lastPage}</td>
      <td>${new Date(i.updatedAt).toLocaleString()}</td>
    </tr>
  `).join("") : '<tr><td colspan="5">Belum ada riwayat membaca.</td></tr>';
})();
