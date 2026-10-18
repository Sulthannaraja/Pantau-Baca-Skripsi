<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookResume;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookResumeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guru = User::query()->where('role', 'GURU')->first();
        $guruId = $guru?->id;

        $books = Book::all();

        foreach ($books as $book) {
            $isLaut = str_contains(strtolower($book->title), 'laut');

            // 1. Resume Kategori DASAR (Untuk Siswa Tingkat Rendah)
            $contentDasar = $isLaut
                ? "Buku 'Laut Bercerita' karya Leila S. Chudori menceritakan tentang seorang mahasiswa bernama Biru Laut yang sangat gemar membaca buku dan berdiskusi bersama teman-temannya di Yogyakarta pada masa akhir 1990-an.\n\n".
                  "Poin-poin penting dalam cerita:\n".
                  "1. Biru Laut dan sahabat-sahabatnya mendirikan kelompok diskusi untuk menyuarakan hak rakyat kecil.\n".
                  "2. Karena situasi politik saat itu sangat ketat, mereka diburu dan ditangkap secara sembunyi-sembunyi oleh aparat.\n".
                  "3. Di bagian kedua buku, adiknya yang bernama Asmara Jati terus berjuang mencari keberadaan kakaknya yang hilang bersama keluarga korban lainnya setiap hari Kamis di depan istana.\n\n".
                  "Pesan Moral & Nilai Kehidupan:\n".
                  'Pentingnya rasa empati, kesetiakawanan, dan keberanian memperjuangkan kebenaran serta kasih sayang keluarga yang tidak pernah padam.'
                : "Buku '{$book->title}' menyajikan cerita yang menarik dan penuh hikmah kehidupan.\n\n".
                  "Intisari Cerita:\n".
                  "- Tokoh utama menghadapi tantangan dalam menjaga lingkungan dan keharmonisan hidup berdampingan dengan alam.\n".
                  "- Kisah ini mengajarkan bahwa setiap tindakan manusia terhadap alam memiliki dampak bagi kehidupan generasi berikutnya.\n\n".
                  "Pesan Moral:\n".
                  'Mari kita jaga kelestarian alam sekitar, lindungi satwa yang terancam punah, dan hargai kearifan lokal para sesepuh.';

            BookResume::query()->updateOrCreate(
                [
                    'book_id' => $book->id,
                    'category' => 'DASAR',
                ],
                [
                    'user_id' => $guruId,
                    'tujuan_penyajian' => 'Membantu memahami konsep dasar & alur cerita',
                    'kedalaman_materi' => 'Konsep-konsep utama & garis besar plot cerita',
                    'bahasa' => 'Sederhana, komunikatif, dan mudah dipahami siswa',
                    'penyajian_materi' => 'Bertahap, runtut dengan poin-poin jelas',
                    'contoh' => 'Contoh konkret nilai empati, keluarga, dan persahabatan',
                    'content' => $contentDasar,
                ]
            );

            // 2. Resume Kategori LANJUTAN (Untuk Siswa Tingkat Tinggi)
            $contentLanjutan = $isLaut
                ? "Buku 'Laut Bercerita' karya Leila S. Chudori merupakan novel fiksi historis yang membedah luka traumatik bangsa akibat represi negara menjelang runtuhnya rezim Orde Baru pada tahun 1998.\n\n".
                  "Analisis Kritis & Struktur Tematis:\n".
                  "1. Dualitas Sudut Pandang (Bifurcated Narrative):\n".
                  "   Bagian pertama dinarasikan oleh Biru Laut dari dasar laut sunyi, melambangkan suara para korban penghilangan paksa (enforced disappearances) yang berusaha dibungkam negara namun menolak dilupakan.\n".
                  "   Bagian kedua dinarasikan oleh Asmara Jati, sang adik, yang mewakili perjuangan keluarga korban di darat (Aksi Kamisan) dalam menuntut keadilan dan menghadapi ambiguïtas duka (ambiguous grief).\n\n".
                  "2. Metafora Laut dan Trauma Kolektif:\n".
                  "   Laut tidak hanya berfungsi sebagai latar fisik pembuangan korban, tetapi juga metafora kedalaman memori kolektif yang menyimpan kebenaran sejarah yang belum tuntas.\n\n".
                  "3. Relevansi Literasi Kritis bagi Pelajar SMA:\n".
                  '   Novel ini mengasah kepekaan literasi digital siswa untuk menelisik narasi sejarah secara kritis, memvalidasi fakta historis, dan menjunjung tinggi nilai-nilai hak asasi manusia dalam kehidupan berbangsa.'
                : "Buku '{$book->title}' merupakan karya sastra yang mengangkat diskursus ekokritisisme dan dinamika sosio-ekologis kontemporer.\n\n".
                  "Fokus Telaah Kritis:\n".
                  "1. Dialektika Manusia dan Ekosistem: Menyoroti dampak fragmentasi habitat dan perburuan liar terhadap kepunahan satwa kunci (keystone species).\n".
                  "2. Rekonseptualisasi Kearifan Lokal: Bagaimana mitologi dan folklore tradisional berfungsi sebagai mekanisme proteksi ekologis alami yang mulai tergerus modernitas.\n".
                  '3. Pendekatan Analitis: Mendorong siswa melakukan sintesis antara kajian sastra dengan isu lingkungan global seperti perubahan iklim dan keanekaragaman hayati.';

            BookResume::query()->updateOrCreate(
                [
                    'book_id' => $book->id,
                    'category' => 'LANJUTAN',
                ],
                [
                    'user_id' => $guruId,
                    'tujuan_penyajian' => 'Memperdalam pemahaman materi & telaah kritis sastra',
                    'kedalaman_materi' => 'Analisis mendalam, konteks historis-sosiologis, & simbolisme',
                    'bahasa' => 'Formal, akademis, dan analitis tingkat lanjut',
                    'penyajian_materi' => 'Ringkas, tematis, dan langsung pada sintesis kritis',
                    'contoh' => 'Contoh studi kasus historis 1998 dan diskursus HAM',
                    'content' => $contentLanjutan,
                ]
            );
        }
    }
}
