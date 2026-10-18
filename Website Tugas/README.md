# E-Perpustakaan Digital

## Struktur Folder

- `backend/` : Spring Boot API + streaming PDF protected route
- `frontend/` : HTML, CSS, JavaScript UI
- `database.sql` : Skema MySQL

## Menjalankan Backend

Mode default saat ini memakai database lokal embedded H2 (langsung jalan tanpa install MySQL).

1. Jalankan:

```bash
cd backend
.\run-local.ps1
```

Backend aktif di `http://localhost:8080`.

Jika ingin pakai MySQL, jalankan dengan profile mysql:

```bash
cd backend
.\mvnw.cmd spring-boot:run -Dspring-boot.run.profiles=mysql
```

## Menjalankan Frontend

Buka folder `frontend/` via local server (misalnya Live Server VS Code) di `http://localhost:5500`.

## Endpoint Utama

- `POST /api/login`
- `POST /api/register`
- `GET /api/books`
- `GET /api/books/{id}`
- `POST /api/books` (admin)
- `POST /api/books/upload` (admin, upload PDF)
- `DELETE /api/books/{id}` (admin)
- `POST /api/reading/start` (siswa)
- `POST /api/reading/progress` (siswa)
- `GET /api/reading/report` (siswa: own report, guru/admin: all report)
- `GET /api/books/{id}/stream` (protected)

## Catatan Keamanan

- Buku hanya dibaca melalui web reader (`reader.html`) dengan endpoint stream backend.
- Tidak ada tombol download di UI reader.
- Akses stream memerlukan token login.
- Klik kanan dan shortcut download/print dasar dinonaktifkan di reader.

## Alur Uji Cepat

1. Register akun guru dan siswa dari `register.html`.
2. Login admin -> `dashboard-admin.html` -> upload PDF.
3. Login siswa -> `katalog.html` -> buka `reader.html`.
4. Login guru -> `dashboard-guru.html` untuk melihat monitoring.

## Akun Admin Default

- Nama: `sulthantegarnaraja`
- Email login: `sulthantegarnaraja@admin.local`
- Username login: `sulthantegarnaraja`
- Password: `kanadabismillah`

Admin dibuat otomatis saat backend pertama kali dijalankan. Registrasi publik hanya untuk role guru dan siswa.
