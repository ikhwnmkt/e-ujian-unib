<?php

namespace Database\Seeders;

use App\Models\{Answer, Attempt, Question, Test, User};
use Illuminate\Database\Seeder;

class TestPilganSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = User::where('role', 'dosen')->first();
        $mhs2 = User::where('email', 'mhs2@unib.ac.id')->first();

        if (!$dosen) {
            $this->call(UserSeeder::class);
            $dosen = User::where('role', 'dosen')->first();
            $mhs2 = User::where('email', 'mhs2@unib.ac.id')->first();
        }

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

        if ($mhs2) {
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
        }
    }
}
