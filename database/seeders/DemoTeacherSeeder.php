<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookResume;
use App\Models\Classroom;
use App\Models\LiteracySurvey;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoTeacherSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Guru Demo
        $teacher = User::query()->updateOrCreate(
            ['email' => 'guru@pantaubaca.com'],
            [
                'name' => 'Budi Guru, S.Pd.',
                'password' => Hash::make('guru123'),
                'role' => 'GURU',
            ]
        );

        // Hapus kelas lama milik guru ini agar bersih (hanya ada XI 1 s/d XI 7)
        $oldClassrooms = Classroom::query()->where('teacher_id', $teacher->id)->get();
        foreach ($oldClassrooms as $oldClass) {
            $oldClass->users()->detach();
            $oldClass->delete();
        }

        // 2. Buat 7 Kelas: Kelas XI 1 sampai Kelas XI 7
        $classrooms = [];
        for ($i = 1; $i <= 7; $i++) {
            $classrooms[$i] = Classroom::query()->create([
                'name' => "Kelas XI {$i}",
                'code' => "PB-XI{$i}",
                'teacher_id' => $teacher->id,
            ]);
        }

        // 3. Daftar 35 Siswa Asli Sampel SMAN 3 Bandar Lampung
        $studentsList = [
            // Kelas XI 1 (5 Siswa)
            ['name' => 'Abdoel Hakim', 'class_num' => 1, 'level' => 'RENDAH', 'score' => 22],
            ['name' => 'Alisia Fitri Andini', 'class_num' => 1, 'level' => 'RENDAH', 'score' => 24],
            ['name' => 'Allya Nawang Sari', 'class_num' => 1, 'level' => 'TINGGI', 'score' => 36],
            ['name' => 'Alwin Alvaro AD.', 'class_num' => 1, 'level' => 'RENDAH', 'score' => 20],
            ['name' => 'Andrey Arshavin', 'class_num' => 1, 'level' => 'TINGGI', 'score' => 34],

            // Kelas XI 2 (5 Siswa)
            ['name' => 'Apipah', 'class_num' => 2, 'level' => 'RENDAH', 'score' => 23],
            ['name' => 'Azka Nur Cantika', 'class_num' => 2, 'level' => 'TINGGI', 'score' => 35],
            ['name' => 'David Wardani', 'class_num' => 2, 'level' => 'RENDAH', 'score' => 21],
            ['name' => 'Devina Salsabila', 'class_num' => 2, 'level' => 'TINGGI', 'score' => 37],
            ['name' => 'Fathir AlKamil N.', 'class_num' => 2, 'level' => 'RENDAH', 'score' => 25],

            // Kelas XI 3 (5 Siswa)
            ['name' => 'Faza Fauzan Azima', 'class_num' => 3, 'level' => 'TINGGI', 'score' => 38],
            ['name' => 'Fikrian Ikbar Zulfa', 'class_num' => 3, 'level' => 'RENDAH', 'score' => 22],
            ['name' => 'Fitrah Fata N.', 'class_num' => 3, 'level' => 'RENDAH', 'score' => 24],
            ['name' => 'Ibnu Abi Reyhan', 'class_num' => 3, 'level' => 'TINGGI', 'score' => 36],
            ['name' => 'Jovan Rafiq Hawwari', 'class_num' => 3, 'level' => 'RENDAH', 'score' => 20],

            // Kelas XI 4 (5 Siswa)
            ['name' => "Karin Amirah 'A", 'class_num' => 4, 'level' => 'TINGGI', 'score' => 35],
            ['name' => 'Lionel Messi Ardana', 'class_num' => 4, 'level' => 'RENDAH', 'score' => 23],
            ['name' => 'M. Fahry Kurniawan C.', 'class_num' => 4, 'level' => 'TINGGI', 'score' => 34],
            ['name' => 'M. Rafli', 'class_num' => 4, 'level' => 'RENDAH', 'score' => 22],
            ['name' => 'M. Ramadhan Banuwa', 'class_num' => 4, 'level' => 'RENDAH', 'score' => 25],

            // Kelas XI 5 (5 Siswa)
            ['name' => 'M. Trisna Pratama', 'class_num' => 5, 'level' => 'TINGGI', 'score' => 33],
            ['name' => 'Maritza Audrey Dewi', 'class_num' => 5, 'level' => 'TINGGI', 'score' => 38],
            ['name' => 'Meifia Labuna F.', 'class_num' => 5, 'level' => 'RENDAH', 'score' => 24],
            ['name' => 'Moch Syahriel R.', 'class_num' => 5, 'level' => 'RENDAH', 'score' => 21],
            ['name' => 'Mutiara Abelia Aswin', 'class_num' => 5, 'level' => 'TINGGI', 'score' => 36],

            // Kelas XI 6 (5 Siswa)
            ['name' => 'Nabilla Aurelia Putri', 'class_num' => 6, 'level' => 'TINGGI', 'score' => 37],
            ['name' => 'Nauren Vania R.', 'class_num' => 6, 'level' => 'RENDAH', 'score' => 23],
            ['name' => 'Naysya Jihan P.', 'class_num' => 6, 'level' => 'TINGGI', 'score' => 35],
            ['name' => 'Nizar Pratama', 'class_num' => 6, 'level' => 'RENDAH', 'score' => 22],
            ['name' => 'Nurjihan Fajarina', 'class_num' => 6, 'level' => 'TINGGI', 'score' => 36],

            // Kelas XI 7 (5 Siswa)
            ['name' => 'Rahma Razuqi', 'class_num' => 7, 'level' => 'TINGGI', 'score' => 34],
            ['name' => 'Raka Ilham R.', 'class_num' => 7, 'level' => 'RENDAH', 'score' => 25],
            ['name' => 'Ricky Kurniawan', 'class_num' => 7, 'level' => 'RENDAH', 'score' => 21],
            ['name' => 'Ririn Padila', 'class_num' => 7, 'level' => 'TINGGI', 'score' => 33],
            ['name' => 'Rosela', 'class_num' => 7, 'level' => 'RENDAH', 'score' => 26],
        ];

        $studentModels = [];

        foreach ($studentsList as $index => $st) {
            $slug = Str::slug($st['name'], '.');
            if (empty($slug)) {
                $slug = 'siswa.' . ($index + 1);
            }
            $email = "{$slug}@pantaubaca.com";

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $st['name'],
                    'password' => Hash::make('siswa123'),
                    'role' => 'SISWA',
                ]
            );

            $studentModels[] = $user;

            // Masukkan siswa ke kelasnya (5 siswa per kelas)
            $class = $classrooms[$st['class_num']];
            $class->users()->syncWithoutDetaching([$user->id]);

            // Buat hasil evaluasi survei diagnostik awal (10 butir)
            LiteracySurvey::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'responses' => json_encode(array_fill(0, 10, ($st['level'] === 'TINGGI' ? 4 : 2))),
                    'total_score' => $st['score'],
                    'level' => $st['level'],
                ]
            );
        }

        // Ambil buku yang ada di database
        $books = Book::all();
        if ($books->isEmpty()) {
            return;
        }

        $bookDigital = $books->firstWhere('title', 'Literasi Digital') ?? $books[0];
        $bookRemaja = $books->firstWhere('title', 'Remaja dan Literasi') ?? $books[1] ?? $books[0];
        $bookMaung = $books->firstWhere('title', 'Maung dan Hujan Terakhir') ?? $books[2] ?? $books[0];
        $bookSekolah = $books->firstWhere('title', 'Cerita dari Halaman Sekolah 2019 BBY') ?? $books[3] ?? $books[0];
        $bookLangit = $books->firstWhere('title', 'Menggapai Langit, Anyologi Cerpen') ?? $books[4] ?? $books[0];

        // 4. Rekam Aktivitas Membaca Siswa (Reading Logs)
        $selectedBooks = [$bookDigital, $bookRemaja, $bookMaung, $bookSekolah, $bookLangit];
        foreach ($studentModels as $idx => $student) {
            $book = $selectedBooks[$idx % count($selectedBooks)];
            $dur = 12 + (($idx * 3) % 35);
            $prog = ($idx % 3 === 0) ? 100 : (($idx % 2 === 0) ? 85 : 65);
            $lastP = max(1, (int) round(($prog / 100) * 24));

            ReadingLog::query()->updateOrCreate(
                ['user_id' => $student->id, 'book_id' => $book->id],
                [
                    'duration' => $dur,
                    'progress' => $prog,
                    'last_page' => $lastP,
                    'started_at' => now()->subDays(rand(1, 4)),
                    'updated_at' => now()->subHours(rand(1, 18)),
                ]
            );
        }

        // 5. Data Isian Resume Siswa (Book Resumes)
        // Bersihkan resume sebelumnya untuk demo yang rapi
        BookResume::query()->truncate();

        // A. 5 RESUME MENUNGGU NILAI (⏳ Siap dinilai langsung oleh guru demo)
        $pendingResumes = [
            [
                'student' => $studentModels[0], // Abdoel Hakim (Kelas XI 1)
                'book' => $bookSekolah,
                'category' => 'DASAR',
                'tujuan' => 'Menemukan pesan moral tentang arti persahabatan sejati di masa sekolah.',
                'kedalaman' => 'Menceritakan kebersamaan anak-anak sekolah saat mempersiapkan ujian dan saling membantu.',
                'bahasa' => 'Bahasa lugas, santun, dan mudah dicerna.',
                'penyajian' => 'Alur maju runtut mulai dari hari pertama sekolah hingga perpisahan.',
                'contoh' => 'Saat tokoh utama meminjamkan catatan pelajaran kepada sahabatnya yang sakit.',
                'content' => "Buku 'Cerita dari Halaman Sekolah' mengisahkan indahnya masa remaja dan pentingnya sikap saling peduli antar sesama kawan. Pelajaran berharga yang saya dapatkan adalah kebersamaan dan tolong-menolong dapat meringankan beban terberat sekalipun.",
            ],
            [
                'student' => $studentModels[2], // Allya Nawang Sari (Kelas XI 1)
                'book' => $bookDigital,
                'category' => 'LANJUTAN',
                'tujuan' => 'Menganalisis urgensi verifikasi kebenaran informasi sebelum menyebarkannya.',
                'kedalaman' => 'Membedah kriteria sumber berita yang valid dan bahaya berita palsu di ruang publik.',
                'bahasa' => 'Bahasa formal dengan istilah literasi digital terstandar.',
                'penyajian' => 'Pola berpikir deduktif, berawal dari fenomena misinformasi hingga langkah verifikasi.',
                'contoh' => 'Metode pengecekan fakta (fact-checking) lewat situs resmi pemeriksa fakta.',
                'content' => "Penulis 'Literasi Digital' Daniel Pandapotan menegaskan bahwa literasi di era siber menuntut kecakapan kognitif tingkat tinggi. Membaca kritis merupakan benteng utama dalam mencegah polarisasi informasi di kalangan pelajar.",
            ],
            [
                'student' => $studentModels[6], // Azka Nur Cantika (Kelas XI 2)
                'book' => $bookLangit,
                'category' => 'LANJUTAN',
                'tujuan' => 'Menelaah nilai kegigihan dalam meraih pendidikan tinggi.',
                'kedalaman' => 'Menguraikan perjuangan pemuda desa menghadapi keterbatasan fasilitas sekolah.',
                'bahasa' => 'Sastrawi, sarat perumpamaan dan pesan tersirat.',
                'penyajian' => 'Antologi cerpen dengan puncak klimaks dramatik.',
                'contoh' => 'Kisah seorang anak petani yang rela menyeberang sungai demi mengikuti bimbingan belajar.',
                'content' => "Buku ini membuktikan bahwa keterbatasan bukan alasan untuk menyerah. Tekad belajar yang kuat selalu menemukan jalan keluar menuju masa depan yang lebih baik.",
            ],
            [
                'student' => $studentModels[8], // Devina Salsabila (Kelas XI 2)
                'book' => $bookRemaja,
                'category' => 'DASAR',
                'tujuan' => 'Memahami tips membentuk kebiasaan membaca harian yang efektif.',
                'kedalaman' => 'Menjelaskan teknik membaca skimming dan scanning serta pembuatan resume ringkas.',
                'bahasa' => 'Komunikatif dan memotivasi remaja.',
                'penyajian' => 'Disusun dalam bentuk bab-bab tanya jawab praktis.',
                'contoh' => 'Target membaca 10 lembar sebelum tidur setiap malam.',
                'content' => "Remaja dan Literasi karya Zakiyah memberikan panduan praktis agar kita tidak malas membaca buku tebal. Kuncinya adalah konsistensi sedikit demi sedikit setiap harinya.",
            ],
            [
                'student' => $studentModels[10], // Faza Fauzan Azima (Kelas XI 3)
                'book' => $bookMaung,
                'category' => 'DASAR',
                'tujuan' => 'Mengetahui pentingnya menjaga keseimbangan ekosistem hutan dan satwa liar.',
                'kedalaman' => 'Menceritakan kepunahan satwa langka akibat perburuan ilegal dan deforestasi.',
                'bahasa' => 'Bahasa petualangan fiksi yang penuh imajinasi.',
                'penyajian' => 'Alur campuran dengan kilas balik sejarah hutan lindung.',
                'contoh' => 'Tokoh pemburu yang bertobat setelah diselamatkan oleh harimau dari tanah longsor.',
                'content' => "Cerita Maung dan Hujan Terakhir sangat menggugah hati. Menjaga kelestarian lingkungan bukan sekadar tugas polisi hutan, melainkan tanggung jawab kita semua.",
            ],
        ];

        foreach ($pendingResumes as $pr) {
            BookResume::query()->create([
                'book_id' => $pr['book']->id,
                'user_id' => $pr['student']->id,
                'category' => $pr['category'],
                'tujuan_penyajian' => $pr['tujuan'],
                'kedalaman_materi' => $pr['kedalaman'],
                'bahasa' => $pr['bahasa'],
                'penyajian_materi' => $pr['penyajian'],
                'contoh' => $pr['contoh'],
                'content' => $pr['content'],
                'score' => null,
                'teacher_feedback' => null,
                'graded_by' => null,
                'graded_at' => null,
            ]);
        }

        // B. 3 RESUME TELAH DINILAI (⭐ Contoh hasil penilaian guru dan catatan rubrik)
        $gradedResumes = [
            [
                'student' => $studentModels[15], // Karin Amirah (Kelas XI 4)
                'book' => $bookDigital,
                'category' => 'LANJUTAN',
                'tujuan' => 'Mengevaluasi dampak etika digital bagi masa depan generasi muda.',
                'kedalaman' => 'Menyajikan analisis komparatif antara pemanfaatan teknologi positif dan negatif.',
                'bahasa' => 'Bahasa argumentatif ilmiah yang sistematis.',
                'penyajian' => 'Paparan fakta, analisis kritis, dan solusi nyata.',
                'contoh' => 'Pemanfaatan media sosial untuk kampanye sosial pelestarian budaya.',
                'content' => "Di era serba digital, kecerdasan memilih konten sama pentingnya dengan kecerdasan akademis di sekolah.",
                'score' => 95,
                'feedback' => 'Analisis yang luar biasa tajam dan pemilihan contoh kasusnya sangat tepat. Pertahankan daya kritis ini, Karin!',
                'graded_at' => now()->subDay(),
            ],
            [
                'student' => $studentModels[21], // Maritza Audrey (Kelas XI 5)
                'book' => $bookSekolah,
                'category' => 'DASAR',
                'tujuan' => 'Menghargai perjuangan dan jasa para guru di sekolah.',
                'kedalaman' => 'Kisah interaksi antara murid yang nakal dengan guru bimbingan konseling yang sabar.',
                'bahasa' => 'Bahasa naratif santun dan mengharukan.',
                'penyajian' => 'Kronologis berbasis pengalaman pribadi tokoh.',
                'contoh' => 'Saat guru membimbing siswa yang hampir putus sekolah sampai berhasil lulus.',
                'content' => "Buku ini mengingatkan kita untuk selalu menghormati guru yang telah mendidik kita dengan tulus tanpa lelah.",
                'score' => 90,
                'feedback' => 'Rangkuman disusun dengan bahasa yang sangat menyentuh dan runtut. Struktur penyajiannya sangat baik.',
                'graded_at' => now()->subDays(2),
            ],
            [
                'student' => $studentModels[25], // Nabilla Aurelia (Kelas XI 6)
                'book' => $bookLangit,
                'category' => 'LANJUTAN',
                'tujuan' => 'Menemukan makna filosofis dari ketabahan menghadapi cobaan hidup.',
                'kedalaman' => 'Eksplorasi emosi dan dialog mendalam tentang keadilan sosial.',
                'bahasa' => 'Bahasa sastra tingkat tinggi dengan pilihan kata puitis.',
                'penyajian' => 'Tema-tema berurutan dari penderitaan menuju harapan.',
                'contoh' => 'Tokoh yang memilih tetap jujur meskipun dalam kesulitan ekonomi yang berat.',
                'content' => "Keberhasilan sejati bukan diukur dari materi, melainkan dari seberapa kokoh kita memegang prinsip kejujuran.",
                'score' => 88,
                'feedback' => 'Penyampaian intisari cerita sangat filosofis dan berbobot. Perhatikan konsistensi tanda petik dialog.',
                'graded_at' => now()->subDays(3),
            ],
        ];

        foreach ($gradedResumes as $gr) {
            BookResume::query()->create([
                'book_id' => $gr['book']->id,
                'user_id' => $gr['student']->id,
                'category' => $gr['category'],
                'tujuan_penyajian' => $gr['tujuan'],
                'kedalaman_materi' => $gr['kedalaman'],
                'bahasa' => $gr['bahasa'],
                'penyajian_materi' => $gr['penyajian'],
                'contoh' => $gr['contoh'],
                'content' => $gr['content'],
                'score' => $gr['score'],
                'teacher_feedback' => $gr['feedback'],
                'graded_by' => $teacher->id,
                'graded_at' => $gr['graded_at'],
            ]);
        }

        // 6. Nilai Kuis Pemahaman Siswa
        $quizzes = Quiz::all();
        if ($quizzes->isNotEmpty()) {
            $quiz = $quizzes->first();
            foreach (array_slice($studentModels, 0, 15) as $i => $st) {
                $scr = 75 + (($i * 5) % 30);
                QuizSubmission::query()->updateOrCreate(
                    ['quiz_id' => $quiz->id, 'user_id' => $st->id],
                    [
                        'score' => min(100, $scr),
                        'duration_seconds' => 150 + ($i * 12),
                        'submitted_at' => now()->subDays(rand(1, 3)),
                    ]
                );
            }
        }
    }
}
