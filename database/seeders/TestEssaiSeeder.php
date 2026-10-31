<?php

namespace Database\Seeders;

use App\Models\{Answer, Attempt, Question, Test, User};
use Illuminate\Database\Seeder;

class TestEssaiSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = User::where('role', 'dosen')->first();
        $mhs2 = User::where('email', 'mhs2@unib.ac.id')->first();
        $mhs3 = User::where('email', 'mhs3@unib.ac.id')->first();

        if (!$dosen) {
            $this->call(UserSeeder::class);
            $dosen = User::where('role', 'dosen')->first();
            $mhs2 = User::where('email', 'mhs2@unib.ac.id')->first();
            $mhs3 = User::where('email', 'mhs3@unib.ac.id')->first();
        }

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

        if ($mhs2) {
            // Contoh Attempt Ujian 2 untuk mhs2: Sedang Berlangsung (finished_at = null, nilai = null)
            $attemptEssaiLive = Attempt::create([
                'test_id' => $testEssai->id,
                'user_id' => $mhs2->id,
                'started_at' => now()->subMinutes(25),
                'finished_at' => null,
                'nilai' => null,
                'question_ids' => $essaiQuestionIds,
            ]);

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
        }

        if ($mhs3) {
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
}
