<?php

namespace Database\Seeders;

use App\Models\{Answer, Attempt, Question, Test, User};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Pengguna (Admin, Dosen, 3 Mahasiswa)
        $admin = User::create([
            'name' => 'Administrator UNIB',
            'email' => 'admin@unib.ac.id',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
            'no_hp' => '081234567890',
        ]);

        $dosen = User::create([
            'name' => 'Dr. Ir. Dosen Pengampu, M.Kom.',
            'email' => 'dosen@unib.ac.id',
            'password' => 'password',
            'role' => 'dosen',
            'nomor_induk' => '198001012005012001',
            'status' => 'aktif',
            'fakultas' => 'Teknik',
            'prodi' => 'Informatika',
            'no_hp' => '081234567891',
            'tempat_lahir' => 'Bengkulu',
            'tanggal_lahir' => '1980-01-01',
        ]);

        $mhs1 = User::create([
            'name' => 'Aditya Pratama (Mhs 1 - Fresh)',
            'email' => 'mhs1@unib.ac.id',
            'password' => 'password',
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021001',
            'status' => 'aktif',
            'fakultas' => 'Teknik',
            'prodi' => 'Informatika',
            'no_hp' => '082100000001',
            'tempat_lahir' => 'Bengkulu',
            'tanggal_lahir' => '2003-05-10',
        ]);

        $mhs2 = User::create([
            'name' => 'Budi Santoso (Mhs 2 - Remedial & Live)',
            'email' => 'mhs2@unib.ac.id',
            'password' => 'password',
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021002',
            'status' => 'aktif',
            'fakultas' => 'Teknik',
            'prodi' => 'Informatika',
            'no_hp' => '082100000002',
            'tempat_lahir' => 'Curup',
            'tanggal_lahir' => '2003-08-17',
        ]);

        $mhs3 = User::create([
            'name' => 'Citra Lestari (Mhs 3 - Selesai Essai)',
            'email' => 'mhs3@unib.ac.id',
            'password' => 'password',
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021003',
            'status' => 'aktif',
            'fakultas' => 'Teknik',
            'prodi' => 'Informatika',
            'no_hp' => '082100000003',
            'tempat_lahir' => 'Manna',
            'tanggal_lahir' => '2003-11-20',
        ]);

        // 2. Ujian 1: Pilihan Ganda (Bank Soal 50 -> Tampil 30, Sifat Fleksibel, Aktif Hari Ini)
        $testPilgan = Test::create([
            'dosen_id' => $dosen->id,
            'judul' => 'Ujian Akhir Semester: Struktur Data & Algoritma',
            'materi' => 'Struktur Data & Algoritma',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:01',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 30,
            'kkm' => 70,
        ]);

        // Bank Soal 50 butir pilihan ganda
        $topik = [
            ['FIFO (First In First Out)', 'Queue', 'Stack', 'Tree', 'Graph', 'a'],
            ['LIFO (Last In First Out)', 'Queue', 'Stack', 'Array', 'Heap', 'b'],
            ['Worst-case time complexity Quick Sort', 'O(n log n)', 'O(n)', 'O(n^2)', 'O(log n)', 'c'],
            ['Best-case time complexity Bubble Sort dengan flag swap', 'O(n)', 'O(n^2)', 'O(log n)', 'O(1)', 'a'],
            ['Pencarian elemen pada Array terurut yang paling efisien', 'Linear Search', 'Binary Search', 'BFS', 'DFS', 'b'],
            ['Traversal pohon biner yang mengunjungi Left -> Root -> Right', 'Pre-order', 'In-order', 'Post-order', 'Level-order', 'b'],
            ['Algoritma penentuan Shortest Path pada graf berbobot non-negatif', 'Dijkstra', 'Kruskal', 'Prim', 'Bellman-Ford', 'a'],
            ['Struktur data yang digunakan untuk mengimplementasikan Breadth-First Search (BFS)', 'Stack', 'Queue', 'Heap', 'Disjoint Set', 'b'],
            ['Struktur data yang digunakan untuk mengimplementasikan Depth-First Search (DFS)', 'Stack', 'Queue', 'Priority Queue', 'Hash Table', 'a'],
            ['Kompleksitas akses elemen pada Array berdasarkan indeks', 'O(n)', 'O(log n)', 'O(1)', 'O(n log n)', 'c'],
        ];

        $pilganQuestions = [];
        for ($i = 1; $i <= 50; $i++) {
            $item = $topik[($i - 1) % count($topik)];
            $q = Question::create([
                'test_id' => $testPilgan->id,
                'tipe' => 'pilihan_ganda',
                'pertanyaan' => "Soal #{$i}: Konsep dasar mengenai {$item[0]}. Manakah jawaban yang paling tepat?",
                'opsi_a' => $item[1],
                'opsi_b' => $item[2],
                'opsi_c' => $item[3],
                'opsi_d' => $item[4],
                'jawaban_benar' => $item[5],
            ]);
            $pilganQuestions[] = $q;
        }

        // Contoh Attempt mhs2 pada Ujian 1:
        // Attempt 1: Nilai 40 (< KKM 70) - Gagal (Siap Remedial)
        $subset1 = array_slice($pilganQuestions, 0, 30);
        $subsetIds1 = array_map(fn ($q) => $q->id, $subset1);
        $attempt1 = Attempt::create([
            'test_id' => $testPilgan->id,
            'user_id' => $mhs2->id,
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHours(1)->subMinutes(15),
            'nilai' => 40.0,
            'question_ids' => $subsetIds1,
        ]);

        // Buat 12 benar, 18 salah (12/30 * 100 = 40.0)
        foreach ($subset1 as $idx => $q) {
            $isCorrect = ($idx < 12);
            $ans = $isCorrect ? $q->jawaban_benar : ($q->jawaban_benar === 'a' ? 'b' : 'a');
            Answer::create([
                'attempt_id' => $attempt1->id,
                'question_id' => $q->id,
                'jawaban' => $ans,
                'benar' => $isCorrect,
            ]);
        }

        // Attempt 2: Nilai 80 (>= KKM 70) - Lulus
        $subset2 = array_slice($pilganQuestions, 10, 30);
        $subsetIds2 = array_map(fn ($q) => $q->id, $subset2);
        $attempt2 = Attempt::create([
            'test_id' => $testPilgan->id,
            'user_id' => $mhs2->id,
            'started_at' => now()->subMinutes(50),
            'finished_at' => now()->subMinutes(10),
            'nilai' => 80.0,
            'question_ids' => $subsetIds2,
        ]);

        // Buat 24 benar, 6 salah (24/30 * 100 = 80.0)
        foreach ($subset2 as $idx => $q) {
            $isCorrect = ($idx < 24);
            $ans = $isCorrect ? $q->jawaban_benar : ($q->jawaban_benar === 'a' ? 'b' : 'a');
            Answer::create([
                'attempt_id' => $attempt2->id,
                'question_id' => $q->id,
                'jawaban' => $ans,
                'benar' => $isCorrect,
            ]);
        }

        // Catatan: mhs1 sengaja belum memiliki attempt agar fresh untuk demo "Mulai Ujian".

        // 3. Ujian 2: Essai (Live Monitoring & Penilaian Manual, Aktif Hari Ini)
        $testEssai = Test::create([
            'dosen_id' => $dosen->id,
            'judul' => 'Ujian Tengah Semester: Analisis Kompleksitas Algoritma',
            'materi' => 'Analisis Kompleksitas Algoritma',
            'tipe_ujian' => 'essai',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:01',
            'jam_selesai' => '23:59',
            'durasi' => 90,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 5,
            'kkm' => 70,
        ]);

        $essaiQuestions = [
            Question::create([
                'test_id' => $testEssai->id,
                'tipe' => 'essai',
                'pertanyaan' => 'Jelaskan perbedaan mendasar antara kompleksitas waktu O(1), O(log n), dan O(n^2) beserta contoh kasusnya pada algoritma sorting!',
            ]),
            Question::create([
                'test_id' => $testEssai->id,
                'tipe' => 'essai',
                'pertanyaan' => 'Bagaimana cara kerja algoritma Dijkstra dalam menemukan lintasan terpendek (shortest path) pada sebuah directed weighted graph?',
            ]),
            Question::create([
                'test_id' => $testEssai->id,
                'tipe' => 'essai',
                'pertanyaan' => 'Jelaskan prinsip kerja Binary Search Tree (BST) dan bagaimana skenario worst-case pohon menjadi skewed list (seperti linked list)?',
            ]),
            Question::create([
                'test_id' => $testEssai->id,
                'tipe' => 'essai',
                'pertanyaan' => 'Jelaskan konsep Dynamic Programming (DP) serta bandingkan pendekatan Memoization (Top-Down) vs Tabulation (Bottom-Up)!',
            ]),
            Question::create([
                'test_id' => $testEssai->id,
                'tipe' => 'essai',
                'pertanyaan' => 'Apa yang dimaksud dengan paradigma Divide and Conquer? Jelaskan implementasinya pada algoritma Merge Sort!',
            ]),
        ];

        $essaiQuestionIds = array_map(fn ($q) => $q->id, $essaiQuestions);

        // Contoh Attempt Ujian 2 untuk mhs2: Sedang Berlangsung (finished_at = null, nilai = null)
        $attemptEssaiLive = Attempt::create([
            'test_id' => $testEssai->id,
            'user_id' => $mhs2->id,
            'started_at' => now()->subMinutes(25),
            'finished_at' => null,
            'nilai' => null,
            'question_ids' => $essaiQuestionIds,
        ]);

        // 2 Jawaban parsial seolah sedang diketik live (terlihat di liveMonitoring)
        Answer::create([
            'attempt_id' => $attemptEssaiLive->id,
            'question_id' => $essaiQuestions[0]->id,
            'jawaban_essai' => 'Kompleksitas O(1) berarti waktu eksekusi konstan independen terhadap ukuran input n, misalnya array indexing. O(log n) membagi ruang pencarian menjadi setengah tiap langkah seperti binary search. Sedangkan O(n^2) biasanya terjadi pada nested loop seperti Bubble Sort...',
            'benar' => false,
        ]);
        Answer::create([
            'attempt_id' => $attemptEssaiLive->id,
            'question_id' => $essaiQuestions[1]->id,
            'jawaban_essai' => 'Algoritma Dijkstra menggunakan greedy approach dengan priority queue. Pada setiap langkah, algoritma mengekstrak simpul dengan jarak tentatif terkecil yang belum dikunjungi, lalu melakukan proses relaksasi (relaxation) pada semua sisi tetangganya...',
            'benar' => false,
        ]);

        // Contoh Attempt Ujian 2 untuk mhs3: Sudah Selesai (Menunggu Penilaian Manual Dosen)
        $attemptEssaiFinished = Attempt::create([
            'test_id' => $testEssai->id,
            'user_id' => $mhs3->id,
            'started_at' => now()->subMinutes(60),
            'finished_at' => now()->subMinutes(10),
            'nilai' => null,
            'question_ids' => $essaiQuestionIds,
        ]);

        Answer::create([
            'attempt_id' => $attemptEssaiFinished->id,
            'question_id' => $essaiQuestions[0]->id,
            'jawaban_essai' => 'O(1) merepresentasikan konstanta waktu (misal akses array index). O(log n) waktu bertambah secara logaritmik (misal Binary Search). O(n^2) waktu sebanding dengan kuadrat input (misal Bubble Sort, Selection Sort).',
            'benar' => false,
        ]);
        Answer::create([
            'attempt_id' => $attemptEssaiFinished->id,
            'question_id' => $essaiQuestions[1]->id,
            'jawaban_essai' => 'Dijkstra menentukan jarak minimum dari satu simpul asal ke semua simpul lain menggunakan himpunan simpul dan relaksasi bobot sisi.',
            'benar' => false,
        ]);
        Answer::create([
            'attempt_id' => $attemptEssaiFinished->id,
            'question_id' => $essaiQuestions[2]->id,
            'jawaban_essai' => 'BST menempatkan simpul anak kiri lebih kecil dan kanan lebih besar dari root. Skenario terburuk terjadi ketika elemen dimasukkan dalam urutan terurut, sehingga pohon berbentuk linked list dengan tinggi n dan pencarian menjadi O(n).',
            'benar' => false,
        ]);
        Answer::create([
            'attempt_id' => $attemptEssaiFinished->id,
            'question_id' => $essaiQuestions[3]->id,
            'jawaban_essai' => 'Dynamic Programming memecahkan masalah dengan menguraikan submasalah tumpang tindih. Memoization bersifat top-down dengan rekursi dan caching, sedangkan Tabulation bersifat bottom-up dengan iterasi tabel.',
            'benar' => false,
        ]);
        Answer::create([
            'attempt_id' => $attemptEssaiFinished->id,
            'question_id' => $essaiQuestions[4]->id,
            'jawaban_essai' => 'Divide and Conquer memecah masalah menjadi submasalah serupa (Divide), menyelesaikannya secara rekursif (Conquer), lalu menggabungkan solusinya (Combine). Pada Merge Sort, array dibagi 2 hingga ukuran 1, lalu digabung kembali dalam keadaan terurut.',
            'benar' => false,
        ]);
    }
}
