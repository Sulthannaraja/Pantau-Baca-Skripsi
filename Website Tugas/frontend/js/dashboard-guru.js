if (!requireAuth(["GURU", "ADMIN"])) {
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

  const byStudent = {};
  data.forEach(r => {
    const key = r.userId;
    if (!byStudent[key]) byStudent[key] = { name: r.userName, duration: 0, count: 0, progress: 0 };
    byStudent[key].duration += r.duration;
    byStudent[key].count += 1;
    byStudent[key].progress += r.progress;
  });

  const students = Object.values(byStudent);
  const avgDuration = students.length ? Math.round(students.reduce((a, s) => a + s.duration, 0) / students.length) : 0;

  document.getElementById("activeStudents").textContent = students.length;
  document.getElementById("bookReads").textContent = data.length;
  document.getElementById("avgDuration").textContent = `${avgDuration} menit`;

  document.getElementById("teacherTableBody").innerHTML = data.length ? data.map(r => `
    <tr>
      <td>${escapeHtml(r.userName)}</td>
      <td>${escapeHtml(r.bookTitle)}</td>
      <td>${r.duration} menit</td>
      <td>${r.progress}%</td>
      <td>${r.progress > 50 ? 'Aktif' : 'Perlu perhatian'}</td>
    </tr>
  `).join("") : '<tr><td colspan="5">Belum ada aktivitas membaca.</td></tr>';

  const groupedByDay = {};
  data.forEach(r => {
    const day = new Date(r.updatedAt).toLocaleDateString();
    groupedByDay[day] = (groupedByDay[day] || 0) + 1;
  });

  const labels = Object.keys(groupedByDay);
  const values = Object.values(groupedByDay);
  new Chart(document.getElementById("activityChart"), {
    type: "line",
    data: {
      labels,
      datasets: [{
        label: "Aktivitas Membaca",
        data: values,
        borderColor: "#0077B6",
        backgroundColor: "rgba(144, 224, 239, 0.3)",
        tension: 0.3,
        fill: true
      }]
    }
  });
})();
