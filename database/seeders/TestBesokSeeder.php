<?php

namespace Database\Seeders;

use App\Models\{Question, Test, TestSchedule, User};
use Illuminate\Database\Seeder;

class TestBesokSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = User::where('role', 'dosen')->first();

        if (!$dosen) {
            $this->call(UserSeeder::class);
            $dosen = User::where('role', 'dosen')->first();
        }

        // Hapus test besok demo sebelumnya jika ada agar idempotent saat re-seed
        Test::where('judul', 'Ujian Akhir Semester: Pemrograman Web & Basis Data')->delete();

        $testBesok = Test::create([
            'dosen_id' => $dosen->id,
            'judul' => 'Ujian Akhir Semester: Pemrograman Web & Basis Data',
            'mata_kuliah' => 'Pemrograman Web & Basis Data',
            'materi' => 'Pemrograman Web & Basis Data',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '00:01',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 20,
            'kkm' => 70,
        ]);

        // Jadwal per kelas (besok)
        TestSchedule::create([
            'test_id' => $testBesok->id,
            'prodi' => 'Informatika',
            'kelas' => 'A',
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '00:01',
            'jam_selesai' => '23:59',
        ]);
        TestSchedule::create([
            'test_id' => $testBesok->id,
            'prodi' => 'Informatika',
            'kelas' => 'B',
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '00:01',
            'jam_selesai' => '23:59',
        ]);

        $soalList = [
            [
                'pertanyaan' => 'Metode HTTP yang paling tepat digunakan untuk memperbarui (update) sebagian data resource adalah...',
                'opsi_a' => 'GET',
                'opsi_b' => 'POST',
                'opsi_c' => 'PATCH',
                'opsi_d' => 'DELETE',
                'jawaban_benar' => 'c',
            ],
            [
                'pertanyaan' => 'Status code HTTP yang menandakan bahwa request berhasil dibuat dan resource baru telah tercipta pada server adalah...',
                'opsi_a' => '200 OK',
                'opsi_b' => '201 Created',
                'opsi_c' => '204 No Content',
                'opsi_d' => '301 Moved Permanently',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Dalam arsitektur MVC (Model-View-Controller) pada Laravel, peran utama Model adalah...',
                'opsi_a' => 'Menangani tampilan antarmuka pengguna (HTML/Blade)',
                'opsi_b' => 'Menerima HTTP request dan mengembalikan HTTP response',
                'opsi_c' => 'Mengatur routing URL aplikasi',
                'opsi_d' => 'Mengelola interaksi data dan business logic database',
                'jawaban_benar' => 'd',
            ],
            [
                'pertanyaan' => 'Direktif Blade yang digunakan untuk mewarisi master layout pada view child adalah...',
                'opsi_a' => '@include',
                'opsi_b' => '@extends',
                'opsi_c' => '@section',
                'opsi_d' => '@yield',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Jenis relasi Eloquent yang menghubungkan satu record User yang memiliki banyak data Test adalah...',
                'opsi_a' => 'hasOne',
                'opsi_b' => 'hasMany',
                'opsi_c' => 'belongsTo',
                'opsi_d' => 'belongsToMany',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Normalisasi database bentuk pertama (1NF) mensyaratkan setiap kolom pada tabel harus...',
                'opsi_a' => 'Tidak memiliki ketergantungan transitif',
                'opsi_b' => 'Memiliki relasi many-to-many',
                'opsi_c' => 'Bernilai atomik (tidak dapat dibagi lagi) dan tidak ada repeating group',
                'opsi_d' => 'Memiliki foreign key ke seluruh tabel terkait',
                'jawaban_benar' => 'c',
            ],
            [
                'pertanyaan' => 'Klausul SQL yang digunakan untuk menggabungkan dua tabel dan hanya menampilkan baris yang memiliki kecocokan di kedua tabel adalah...',
                'opsi_a' => 'LEFT JOIN',
                'opsi_b' => 'RIGHT JOIN',
                'opsi_c' => 'FULL OUTER JOIN',
                'opsi_d' => 'INNER JOIN',
                'jawaban_benar' => 'd',
            ],
            [
                'pertanyaan' => 'Tujuan utama pembuatan INDEX (misalnya B-Tree index) pada kolom database adalah...',
                'opsi_a' => 'Mempercepat eksekusi query pencarian dan pemfilteran data',
                'opsi_b' => 'Memperkecil ukuran penyimpanan disk tabel database',
                'opsi_c' => 'Mempercepat operasi INSERT dan BULK LOAD data',
                'opsi_d' => 'Mencegah terjadinya duplikasi data secara otomatis',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Serangan web di mana penyerang memanfaatkan sesi login pengguna yang sah untuk mengirimkan request tidak sah disebut...',
                'opsi_a' => 'Cross-Site Scripting (XSS)',
                'opsi_b' => 'Cross-Site Request Forgery (CSRF)',
                'opsi_c' => 'SQL Injection',
                'opsi_d' => 'Denial of Service (DoS)',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Teknik paling efektif untuk mencegah kerentanan SQL Injection pada query aplikasi web adalah...',
                'opsi_a' => 'Mengenkripsi database menggunakan AES-256',
                'opsi_b' => 'Menggunakan Prepared Statements dan Parameterized Queries',
                'opsi_c' => 'Mengganti port database default',
                'opsi_d' => 'Menghapus hak akses DROP TABLE pada user database',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Perbedaan utama antara Session dan Cookie pada aplikasi web adalah...',
                'opsi_a' => 'Session disimpan di sisi server, sedangkan Cookie disimpan di sisi client/browser',
                'opsi_b' => 'Session hanya untuk data numerik, Cookie untuk teks',
                'opsi_c' => 'Cookie tidak pernah kedaluwarsa, Session selalu 1 menit',
                'opsi_d' => 'Session dikirim otomatis pada setiap HTTP header request',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Prinsip Stateless pada arsitektur RESTful API berarti...',
                'opsi_a' => 'Server tidak menyimpan context sesi client; setiap request harus memuat seluruh informasi yang diperlukan',
                'opsi_b' => 'Database tidak boleh menyimpan status user',
                'opsi_c' => 'Client tidak boleh menyimpan token otentikasi',
                'opsi_d' => 'Setiap endpoint API harus menggunakan metode GET',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Komponen runtime JavaScript yang bertugas mengelola eksekusi asynchronous callback dan task queue disebut...',
                'opsi_a' => 'Call Stack',
                'opsi_b' => 'Memory Heap',
                'opsi_c' => 'Event Loop',
                'opsi_d' => 'Garbage Collector',
                'jawaban_benar' => 'c',
            ],
            [
                'pertanyaan' => 'Pada CSS Flexbox, properti yang digunakan untuk mengatur perataan elemen anak di sepanjang sumbu utama (main axis) adalah...',
                'opsi_a' => 'align-items',
                'opsi_b' => 'justify-content',
                'opsi_c' => 'flex-direction',
                'opsi_d' => 'align-content',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Peran komponen Middleware pada pipeline request Laravel adalah...',
                'opsi_a' => 'Menghubungkan view Blade dengan file CSS eksternal',
                'opsi_b' => 'Melakukan inspeksi, modifikasi, atau filtering request sebelum mencapai controller',
                'opsi_c' => 'Menghasilkan migrasi tabel database baru secara otomatis',
                'opsi_d' => 'Menyimpan cache query SQL di dalam memori Redis',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Prinsip Atomicity dalam karakteristik ACID database transaction menjamin bahwa...',
                'opsi_a' => 'Semua operasi dalam satu transaksi berhasil seluruhnya atau dibatalkan tanpa sisa jika terjadi kegagalan',
                'opsi_b' => 'Data selalu berada dalam format string terenkripsi',
                'opsi_c' => 'Transaksi berjalan paralel tanpa saling mengunci row data',
                'opsi_d' => 'Perubahan data tersimpan permanen bahkan jika server mati mendadak',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Tipe data format pertukaran data ringan yang terstruktur berbasis pasangan key-value dan didukung oleh hampir semua bahasa pemrograman adalah...',
                'opsi_a' => 'XML',
                'opsi_b' => 'YAML',
                'opsi_c' => 'JSON',
                'opsi_d' => 'CSV',
                'jawaban_benar' => 'c',
            ],
            [
                'pertanyaan' => 'Fitur version control skema database pada Laravel yang memungkinkan developer mendefinisikan dan memodifikasi tabel database via kode PHP disebut...',
                'opsi_a' => 'Eloquent Model',
                'opsi_b' => 'Migration',
                'opsi_c' => 'Seeder',
                'opsi_d' => 'Factory',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Protokol HTTPS mengamankan transmisi data antara browser dan web server dengan menambahkan lapisan enkripsi...',
                'opsi_a' => 'SSH',
                'opsi_b' => 'TLS/SSL',
                'opsi_c' => 'FTP',
                'opsi_d' => 'SMTP',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Fungsi agregat SQL yang digunakan untuk menghitung rata-rata nilai numerik pada suatu kolom adalah...',
                'opsi_a' => 'COUNT()',
                'opsi_b' => 'SUM()',
                'opsi_c' => 'AVG()',
                'opsi_d' => 'MAX()',
                'jawaban_benar' => 'c',
            ],
            [
                'pertanyaan' => 'Mekanisme HTTP Caching header yang memberi instruksi batas waktu maksimum (dalam detik) respon boleh disimpan oleh browser adalah...',
                'opsi_a' => 'Cache-Control: max-age',
                'opsi_b' => 'Content-Type: application/json',
                'opsi_c' => 'Accept-Encoding: gzip',
                'opsi_d' => 'Access-Control-Allow-Origin',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Keyword JavaScript yang digunakan untuk menunggu penyelesaian dari sebuah Promise secara sinkron di dalam async function adalah...',
                'opsi_a' => 'defer',
                'opsi_b' => 'yield',
                'opsi_c' => 'await',
                'opsi_d' => 'then',
                'jawaban_benar' => 'c',
            ],
            [
                'pertanyaan' => 'Tujuan utama implementasi Rate Limiting (misal: throttle:60,1) pada endpoint API publik adalah...',
                'opsi_a' => 'Membatasi ukuran upload file gambar',
                'opsi_b' => 'Mencegah abuse, brute force, dan DoS dengan membatasi jumlah request per menit',
                'opsi_c' => 'Mempercepat kecepatan internet client',
                'opsi_d' => 'Menghapus cookie expired secara otomatis',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Perbedaan localStorage dan sessionStorage pada Web Storage API adalah...',
                'opsi_a' => 'localStorage persisten tanpa batas waktu, sedangkan sessionStorage terhapus ketika tab/browser ditutup',
                'opsi_b' => 'sessionStorage disimpan di server, localStorage di client',
                'opsi_c' => 'localStorage hanya berkapasitas 4KB seperti cookie',
                'opsi_d' => 'sessionStorage hanya dapat diakses melalui CSS',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Klausul SQL yang digunakan untuk memfilter baris hasil pengelompokan yang dihasilkan oleh GROUP BY adalah...',
                'opsi_a' => 'WHERE',
                'opsi_b' => 'HAVING',
                'opsi_c' => 'ORDER BY',
                'opsi_d' => 'LIMIT',
                'jawaban_benar' => 'b',
            ],
        ];

        foreach ($soalList as $index => $item) {
            $nomor = $index + 1;
            Question::create([
                'test_id' => $testBesok->id,
                'tipe' => 'pilihan_ganda',
                'pertanyaan' => "Soal #{$nomor}: {$item['pertanyaan']}",
                'opsi_a' => $item['opsi_a'],
                'opsi_b' => $item['opsi_b'],
                'opsi_c' => $item['opsi_c'],
                'opsi_d' => $item['opsi_d'],
                'jawaban_benar' => $item['jawaban_benar'],
            ]);
        }
    }
}
