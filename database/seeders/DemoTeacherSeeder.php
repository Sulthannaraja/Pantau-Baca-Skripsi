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
use Illuminate\Support\Facades\Hash;

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

        // 2. Buat Kelas yang Dikelola Guru
        $classA = Classroom::query()->updateOrCreate(
            ['code' => 'PB-IPA1'],
            [
                'name' => 'Kelas XI IPA 1',
                'teacher_id' => $teacher->id,
            ]
        );

        $classB = Classroom::query()->updateOrCreate(
            ['code' => 'PB-IPS2'],
            [
                'name' => 'Kelas XI IPS 2',
                'teacher_id' => $teacher->id,
            ]
        );

        // 3. Daftar Siswa SMAN 3 Bandar Lampung yang Realistis
        $studentsData = [
            ['name' => 'Aditya Pratama', 'email' => 'aditya.sman3@pantaubaca.com', 'class' => 'A', 'score' => 36, 'level' => 'TINGGI'],
            ['name' => 'Dinda Rahmawati', 'email' => 'dinda.sman3@pantaubaca.com', 'class' => 'A', 'score' => 34, 'level' => 'TINGGI'],
            ['name' => 'Fajar Nugraha', 'email' => 'fajar.sman3@pantaubaca.com', 'class' => 'A', 'score' => 22, 'level' => 'RENDAH'],
            ['name' => 'Siti Nurhaliza', 'email' => 'siti.sman3@pantaubaca.com', 'class' => 'A', 'score' => 24, 'level' => 'RENDAH'],
            ['name' => 'Ahmad Fauzi', 'email' => 'ahmad.fauzi@pantaubaca.com', 'class' => 'A', 'score' => 38, 'level' => 'TINGGI'],
            ['name' => 'Bagas Wicaksono', 'email' => 'bagas.sman3@pantaubaca.com', 'class' => 'A', 'score' => 25, 'level' => 'RENDAH'],
            ['name' => 'Nabila Putri', 'email' => 'nabila.sman3@pantaubaca.com', 'class' => 'A', 'score' => 35, 'level' => 'TINGGI'],
            ['name' => 'Rizky Ramadhan', 'email' => 'rizky.ramadhan@pantaubaca.com', 'class' => 'A', 'score' => 23, 'level' => 'RENDAH'],
            ['name' => 'Putri Ayu Wandira', 'email' => 'putri.ayu@pantaubaca.com', 'class' => 'B', 'score' => 33, 'level' => 'TINGGI'],
            ['name' => 'Muhammad Farhan', 'email' => 'm.farhan@pantaubaca.com', 'class' => 'B', 'score' => 26, 'level' => 'RENDAH'],
            ['name' => 'Zahra Amalia', 'email' => 'zahra.amalia@pantaubaca.com', 'class' => 'B', 'score' => 37, 'level' => 'TINGGI'],
            ['name' => 'Dimas Prasetyo', 'email' => 'dimas.prasetyo@pantaubaca.com', 'class' => 'B', 'score' => 21, 'level' => 'RENDAH'],
            ['name' => 'Anisa Safitri', 'email' => 'anisa.safitri@pantaubaca.com', 'class' => 'B', 'score' => 32, 'level' => 'TINGGI'],
            ['name' => 'Rafi Alamsyah', 'email' => 'rafi.alamsyah@pantaubaca.com', 'class' => 'B', 'score' => 24, 'level' => 'RENDAH'],
            ['name' => 'Tiara Maharani', 'email' => 'tiara.maharani@pantaubaca.com', 'class' => 'B', 'score' => 36, 'level' => 'TINGGI'],
        ];

        $studentModels = [];

        foreach ($studentsData as $st) {
            $user = User::query()->updateOrCreate(
                ['email' => $st['email']],
                [
                    'name' => $st['name'],
                    'password' => Hash::make('siswa123'),
                    'role' => 'SISWA',
                ]
            );

            $studentModels[$st['email']] = $user;

            // Gabungkan siswa ke kelas
            $targetClass = $st['class'] === 'A' ? $classA : $classB;
            $targetClass->users()->syncWithoutDetaching([$user->id]);

            // Buat hasil survei diagnostik awal (10 butir)
            LiteracySurvey::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'responses' => json_encode(array_fill(0, 10, ($st['level'] === 'TINGGI' ? 4 : 2))),
                    'total_score' => $st['score'],
                    'level' => $st['level'],
                ]
            );
        }

        // Ambil beberapa buku untuk log & resume
        $books = Book::all();
        if ($books->isEmpty()) {
            return;
        }

        $bookDigital = $books->firstWhere('title', 'Literasi Digital') ?? $books[0];
        $bookRemaja = $books->firstWhere('title', 'Remaja dan Literasi') ?? $books[1] ?? $books[0];
        $bookMaung = $books->firstWhere('title', 'Maung dan Hujan Terakhir') ?? $books[2] ?? $books[0];
        $bookSekolah = $books->firstWhere('title', 'Cerita dari Halaman Sekolah 2019 BBY') ?? $books[3] ?? $books[0];
        $bookLangit = $books->firstWhere('title', 'Menggapai Langit, Anyologi Cerpen') ?? $books[4] ?? $books[0];

        // 4. Buat Rekam Jejak Aktivitas Membaca (Reading Logs)
        $readingSamples = [
            [$studentModels['aditya.sman3@pantaubaca.com']->id, $bookDigital->id, 35, 100, 24],
            [$studentModels['dinda.sman3@pantaubaca.com']->id, $bookLangit->id, 28, 100, 18],
            [$studentModels['fajar.sman3@pantaubaca.com']->id, $bookMaung->id, 15, 100, 12],
            [$studentModels['siti.sman3@pantaubaca.com']->id, $bookRemaja->id, 20, 100, 15],
            [$studentModels['ahmad.fauzi@pantaubaca.com']->id, $bookSekolah->id, 42, 100, 30],
            [$studentModels['bagas.sman3@pantaubaca.com']->id, $bookRemaja->id, 18, 92, 14],
            [$studentModels['nabila.sman3@pantaubaca.com']->id, $bookDigital->id, 30, 100, 24],
            [$studentModels['rizky.ramadhan@pantaubaca.com']->id, $bookMaung->id, 12, 65, 8],
            [$studentModels['putri.ayu@pantaubaca.com']->id, $bookLangit->id, 25, 85, 15],
            [$studentModels['zahra.amalia@pantaubaca.com']->id, $bookDigital->id, 40, 100, 24],
            [$studentModels['m.farhan@pantaubaca.com']->id, $bookSekolah->id, 16, 55, 10],
            [$studentModels['dimas.prasetyo@pantaubaca.com']->id, $bookMaung->id, 10, 45, 5],
        ];

        foreach ($readingSamples as [$uid, $bid, $dur, $prog, $lp]) {
            ReadingLog::query()->updateOrCreate(
                ['user_id' => $uid, 'book_id' => $bid],
                [
                    'duration' => $dur,
                    'progress' => $prog,
                    'last_page' => $lp,
                    'started_at' => now()->subDays(rand(1, 4)),
                    'updated_at' => now()->subHours(rand(1, 24)),
                ]
            );
        }

        // 5. Buat Data Isian Resume Siswa (Book Resumes)
        // A. RESUME MENUNGGU NILAI (⏳ Guru dapat mencoba tombol "Beri Nilai")
        BookResume::query()->updateOrCreate(
            ['book_id' => $bookDigital->id, 'user_id' => $studentModels['aditya.sman3@pantaubaca.com']->id, 'category' => 'LANJUTAN'],
            [
                'tujuan_penyajian' => 'Menganalisis dampak literasi digital dalam memfilter hoaks di media sosial.',
                'kedalaman_materi' => 'Buku ini menguraikan tahapan verifikasi sumber berita, perlindungan data pribadi, dan etika berinteraksi daring.',
                'bahasa' => 'Bahasa baku dan akademis dengan istilah teknologi yang relevan.',
                'penyajian_materi' => 'Sistematis, dimulai dari fakta permasalahan hoaks hingga solusi pencegahan terstruktur.',
                'contoh' => 'Studi kasus penyebaran berita palsu saat pemilu dan tips memeriksa keaslian foto digital.',
                'content' => "Buku 'Literasi Digital' karya Daniel Pandapotan memberikan wawasan mendalam mengenai tantangan komunikasi di era internet. Penulis menekankan bahwa kemampuan membaca saja tidak cukup, melainkan harus disertai daya kritis dalam menyaring validitas informasi.\n\n"
                    ."Analisis Utama:\n"
                    ."1. Era keterbukaan informasi menuntut pembaca memiliki kemampuan membedakan opini dan fakta empiris.\n"
                    ."2. Keamanan jejak digital dan etika komunikasi merupakan pilar penting bagi generasi muda.\n\n"
                    ."Refleksi Mandiri:\n"
                    ."Saya menyadari pentingnya melakukan verifikasi silang (cross-check) terhadap berita sebelum membagikannya kepada orang lain.",
                'score' => null,
                'teacher_feedback' => null,
                'graded_by' => null,
                'graded_at' => null,
            ]
        );

        BookResume::query()->updateOrCreate(
            ['book_id' => $bookRemaja->id, 'user_id' => $studentModels['siti.sman3@pantaubaca.com']->id, 'category' => 'DASAR'],
            [
                'tujuan_penyajian' => 'Mengetahui manfaat membaca buku bagi perkembangan pola pikir remaja.',
                'kedalaman_materi' => 'Materi menjelaskan pentingnya menyisihkan waktu membaca 15 menit setiap hari.',
                'bahasa' => 'Kalimatnya sederhana, akrab, dan mudah dipahami.',
                'penyajian_materi' => 'Menggunakan contoh percakapan sehari-hari remaja di sekolah.',
                'contoh' => 'Tips memilih buku sesuai hobi dan cara membuat jadwal membaca santai.',
                'content' => "Buku 'Remaja dan Literasi' sangat menyenangkan dibaca. Buku ini menceritakan bagaimana membaca bisa membuka masa depan yang cerah dan membantu kita lebih percaya diri saat berbicara di depan kelas.\n\n"
                    ."Poin Penting yang Saya Pelajari:\n"
                    ."- Membaca 15 menit setiap hari menambah banyak kosakata baru.\n"
                    ."- Membiasakan diri merangkum membantu mengingat pelajaran lebih lama.\n\n"
                    ."Kesimpulan:\n"
                    ."Saya bertekad untuk membaca minimal satu buku cerita setiap minggu di perpustakaan sekolah.",
                'score' => null,
                'teacher_feedback' => null,
                'graded_by' => null,
                'graded_at' => null,
            ]
        );

        BookResume::query()->updateOrCreate(
            ['book_id' => $bookMaung->id, 'user_id' => $studentModels['fajar.sman3@pantaubaca.com']->id, 'category' => 'DASAR'],
            [
                'tujuan_penyajian' => 'Memahami nilai pelestarian satwa langka dan kearifan hutan lokal.',
                'kedalaman_materi' => 'Kisah petualangan seorang pemuda desa yang bersahabat dengan penjaga hutan lindung.',
                'bahasa' => 'Bahasa naratif sastra yang penuh ungkapan keindahan alam.',
                'penyajian_materi' => 'Alur maju dengan konflik perburuan liar yang menegangkan.',
                'contoh' => 'Konflik saat warga desa bergotong royong menyelamatkan anak harimau yang terluka.',
                'content' => "Buku 'Maung dan Hujan Terakhir' menceritakan kepedulian terhadap kelestarian alam. Cerita ini mengajarkan bahwa merusak hutan akan membawa bencana bagi manusia sendiri.\n\n"
                    ."Pelajaran Berharga:\n"
                    ."1. Kita harus hidup selaras dengan alam dan tidak merusak habitat satwa langka.\n"
                    ."2. Keberanian membela kebenaran harus dimulai dari lingkungan terdekat kita.",
                'score' => null,
                'teacher_feedback' => null,
                'graded_by' => null,
                'graded_at' => null,
            ]
        );

        BookResume::query()->updateOrCreate(
            ['book_id' => $bookLangit->id, 'user_id' => $studentModels['dinda.sman3@pantaubaca.com']->id, 'category' => 'LANJUTAN'],
            [
                'tujuan_penyajian' => 'Menelaah kritik sosial dan eksplorasi psikologis karakter pemuda desa.',
                'kedalaman_materi' => 'Menyoroti dinamika ketimpangan akses pendidikan dan tekad pantang menyerah anak pelosok.',
                'bahasa' => 'Gaya bahasa kaya metafora dan diksi sastrawi yang indah.',
                'penyajian_materi' => 'Disusun dalam bentuk antologi cerpen tematik bergradasi.',
                'contoh' => 'Tokoh Danu yang gigih belajar di bawah lampu minyak demi lulus ujian beasiswa.',
                'content' => "Antologi cerpen 'Menggapai Langit' karya Drs. Widada menyuguhkan cermin kehidupan nyata. Setiap cerpen memperlihatkan bahwa keterbatasan fisik maupun ekonomi bukanlah penghalang bagi tekad yang kuat.\n\n"
                    ."Nilai Filosofis:\n"
                    ."Pendidikan adalah jembatan pembebasan dari kebodohan dan kepasrahan nasib.",
                'score' => null,
                'teacher_feedback' => null,
                'graded_by' => null,
                'graded_at' => null,
            ]
        );

        // B. RESUME TELAH DINILAI (⭐ Menampilkan contoh hasil penilaian & catatan guru)
        BookResume::query()->updateOrCreate(
            ['book_id' => $bookSekolah->id, 'user_id' => $studentModels['ahmad.fauzi@pantaubaca.com']->id, 'category' => 'DASAR'],
            [
                'tujuan_penyajian' => 'Menceritakan kembali nilai persahabatan dan kejujuran di sekolah.',
                'kedalaman_materi' => 'Menceritakan interaksi siswa dengan guru saat persiapan ujian sekolah.',
                'bahasa' => 'Bahasa percakapan santun dan runtut.',
                'penyajian_materi' => 'Alur kronologis yang hangat.',
                'contoh' => 'Saat tokoh mengakui kesalahannya menjatuhkan vas bunga kelas.',
                'content' => "Cerita dari Halaman Sekolah menggambarkan suka duka kehidupan siswa. Kejujuran adalah hal paling utama yang dijunjung dalam setiap bab cerita ini.",
                'score' => 92,
                'teacher_feedback' => 'Rangkuman sangat rapi, pesan moral diulas dengan kalimat mandiri yang sangat baik. Pertahankan prestasimu, Fauzi!',
                'graded_by' => $teacher->id,
                'graded_at' => now()->subDay(),
            ]
        );

        BookResume::query()->updateOrCreate(
            ['book_id' => $bookDigital->id, 'user_id' => $studentModels['nabila.sman3@pantaubaca.com']->id, 'category' => 'LANJUTAN'],
            [
                'tujuan_penyajian' => 'Mengevaluasi kesiapan generasi muda menghadapi era kecerdasan buatan.',
                'kedalaman_materi' => 'Menganalisis dampak algoritma media sosial terhadap fokus membaca anak.',
                'bahasa' => 'Lugas, kritis, dan persuasif.',
                'penyajian_materi' => 'Pola deduktif dengan data penguat.',
                'contoh' => 'Eksperimen pengurangan durasi layar pada kelompok pelajar SMA.',
                'content' => "Literasi digital di era kecerdasan buatan menuntut kita menjadi subjek aktif, bukan konsumen pasif algoritma. Kita perlu melatih daya baca mendalam (deep reading).",
                'score' => 88,
                'teacher_feedback' => 'Analisis kritis yang luar biasa! Gaya bahasa sangat baik dan argumentatif. Sangat membanggakan.',
                'graded_by' => $teacher->id,
                'graded_at' => now()->subDays(2),
            ]
        );

        BookResume::query()->updateOrCreate(
            ['book_id' => $bookRemaja->id, 'user_id' => $studentModels['bagas.sman3@pantaubaca.com']->id, 'category' => 'DASAR'],
            [
                'tujuan_penyajian' => 'Menemukan cara asyik memulai hobi membaca buku.',
                'kedalaman_materi' => 'Membahas trik mengatasi rasa kantuk dan bosan saat membaca buku non-fiksi.',
                'bahasa' => 'Bahasa komunikatif dan santai.',
                'penyajian_materi' => 'Poin-poin tips praktis.',
                'contoh' => 'Membuat target membaca 5 halaman per hari bersama teman sebangku.',
                'content' => "Buku ini sangat bermanfaat bagi siswa yang malas membaca. Kuncinya adalah memulai dari bahan bacaan bergambar dan disukai terlebih dahulu.",
                'score' => 85,
                'teacher_feedback' => 'Ulasan yang sangat jujur dan aplikatif. Terus tingkatkan jumlah bacaanmu setiap bulan ya!',
                'graded_by' => $teacher->id,
                'graded_at' => now()->subDays(3),
            ]
        );

        // 6. Buat Nilai Kuis Pemahaman Siswa
        $quizzes = Quiz::all();
        if ($quizzes->isNotEmpty()) {
            $quiz = $quizzes->first();
            $quizStudents = [
                [$studentModels['aditya.sman3@pantaubaca.com']->id, 100, 180],
                [$studentModels['dinda.sman3@pantaubaca.com']->id, 90, 210],
                [$studentModels['ahmad.fauzi@pantaubaca.com']->id, 100, 150],
                [$studentModels['nabila.sman3@pantaubaca.com']->id, 90, 240],
                [$studentModels['siti.sman3@pantaubaca.com']->id, 80, 260],
                [$studentModels['fajar.sman3@pantaubaca.com']->id, 80, 290],
                [$studentModels['bagas.sman3@pantaubaca.com']->id, 85, 230],
                [$studentModels['zahra.amalia@pantaubaca.com']->id, 95, 175],
            ];

            foreach ($quizStudents as [$uid, $scr, $durSec]) {
                QuizSubmission::query()->updateOrCreate(
                    ['quiz_id' => $quiz->id, 'user_id' => $uid],
                    [
                        'score' => $scr,
                        'duration_seconds' => $durSec,
                        'submitted_at' => now()->subDays(rand(1, 3)),
                    ]
                );
            }
        }
    }
}
