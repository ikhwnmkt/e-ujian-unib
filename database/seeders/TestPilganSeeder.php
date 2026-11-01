<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestPilganSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = User::where('role', 'dosen')->first();
        $mhs2 = User::where('email', 'mhs2@unib.ac.id')->first();

        if (! $dosen || ! $mhs2) {
            $this->call(UserSeeder::class);
            $dosen = User::where('role', 'dosen')->first();
            $mhs2 = User::where('email', 'mhs2@unib.ac.id')->first();
        }

        if (! $dosen) {
            throw new \RuntimeException('User dosen untuk data demo tidak ditemukan.');
        }

        $testPilgan = Test::updateOrCreate(
            [
                'dosen_id' => $dosen->id,
                'judul' => 'Ujian Akhir Semester: Struktur Data & Algoritma',
            ],
            [
                'materi' => 'Struktur Data & Algoritma',
                'tipe_ujian' => 'pilihan_ganda',
                'tanggal' => now()->toDateString(),
                'jam_mulai' => '00:01',
                'jam_selesai' => '23:59',
                'durasi' => 60,
                'sifat' => 'fleksibel',
                'jumlah_soal_tampil' => 30,
                'kkm' => 70,
            ]
        );

        $soalList = [
            [
                'pertanyaan' => 'Prinsip kerja struktur data Antrean (Queue) adalah...',
                'opsi_a' => 'First In First Out (FIFO)',
                'opsi_b' => 'Last In First Out (LIFO)',
                'opsi_c' => 'Random Access',
                'opsi_d' => 'Highest Priority First Out',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Prinsip kerja struktur data Tumpukan (Stack) adalah...',
                'opsi_a' => 'First In First Out (FIFO)',
                'opsi_b' => 'Last In First Out (LIFO)',
                'opsi_c' => 'First In Last Out (FILO) tidak sama dengan LIFO',
                'opsi_d' => 'Sorted Order Insertion',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Berapa kompleksitas waktu pencarian Linear Search pada array tak terurut berukuran n?',
                'opsi_a' => 'O(1)',
                'opsi_b' => 'O(log n)',
                'opsi_c' => 'O(n)',
                'opsi_d' => 'O(n^2)',
                'jawaban_benar' => 'c',
            ],
            [
                'pertanyaan' => 'Berapa kompleksitas waktu algoritma Binary Search pada array yang sudah terurut?',
                'opsi_a' => 'O(1)',
                'opsi_b' => 'O(log n)',
                'opsi_c' => 'O(n)',
                'opsi_d' => 'O(n log n)',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Kompleksitas waktu rata-rata (average case) dari algoritma Quick Sort adalah...',
                'opsi_a' => 'O(n)',
                'opsi_b' => 'O(n log n)',
                'opsi_c' => 'O(n^2)',
                'opsi_d' => 'O(log n)',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Kondisi terburuk (worst case) Quick Sort menghasilkan kompleksitas O(n^2) jika...',
                'opsi_a' => 'Elemen array selalu bernilai genap',
                'opsi_b' => 'Pivot yang dipilih selalu menghasilkan partisi yang sangat tidak seimbang (misal elemen terkecil/terbesar)',
                'opsi_c' => 'Array berukuran ganjil',
                'opsi_d' => 'Pivot selalu merupakan nilai median array',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Algoritma pengurutan stabil yang membagi array menjadi dua bagian sama besar lalu menggabungkannya adalah...',
                'opsi_a' => 'Selection Sort',
                'opsi_b' => 'Merge Sort',
                'opsi_c' => 'Heap Sort',
                'opsi_d' => 'Bubble Sort',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Berapa kompleksitas waktu Merge Sort pada kasus terbaik (best case) dan terburuk (worst case)?',
                'opsi_a' => 'Best: O(n), Worst: O(n^2)',
                'opsi_b' => 'Best: O(1), Worst: O(n log n)',
                'opsi_c' => 'Keduanya selalu O(n log n)',
                'opsi_d' => 'Best: O(n log n), Worst: O(n^2)',
                'jawaban_benar' => 'c',
            ],
            [
                'pertanyaan' => 'Dengan optimasi flag swap, berapakah kompleksitas terbaik (best case) Bubble Sort saat data sudah terurut sempurna?',
                'opsi_a' => 'O(n)',
                'opsi_b' => 'O(n log n)',
                'opsi_c' => 'O(n^2)',
                'opsi_d' => 'O(1)',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Operasi penambahan elemen baru ke posisi paling atas pada struktur data Stack dinamakan...',
                'opsi_a' => 'Enqueue',
                'opsi_b' => 'Push',
                'opsi_c' => 'InsertAtHead',
                'opsi_d' => 'Append',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Operasi pengambilan dan penghapusan elemen teratas pada Stack dinamakan...',
                'opsi_a' => 'Dequeue',
                'opsi_b' => 'Pop',
                'opsi_c' => 'DeleteTop',
                'opsi_d' => 'Shift',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Operasi memasukkan elemen baru ke bagian belakang antrean pada Queue disebut...',
                'opsi_a' => 'Push',
                'opsi_b' => 'Enqueue',
                'opsi_c' => 'Shift',
                'opsi_d' => 'Peek',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Operasi mengeluarkan elemen terdepan dari struktur data Queue disebut...',
                'opsi_a' => 'Pop',
                'opsi_b' => 'Dequeue',
                'opsi_c' => 'Unshift',
                'opsi_d' => 'Eject',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Struktur data non-linear berhierarki yang terdiri dari nodes dan sisi tanpa adanya sirkuit/cycle disebut...',
                'opsi_a' => 'Graph dengan cycle',
                'opsi_b' => 'Tree',
                'opsi_c' => 'Circular Queue',
                'opsi_d' => 'Hash Table',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Traversal pohon biner dengan urutan: Root -> Left Subtree -> Right Subtree disebut...',
                'opsi_a' => 'In-order Traversal',
                'opsi_b' => 'Pre-order Traversal',
                'opsi_c' => 'Post-order Traversal',
                'opsi_d' => 'Level-order Traversal',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Traversal pohon biner dengan urutan: Left Subtree -> Root -> Right Subtree disebut...',
                'opsi_a' => 'Pre-order Traversal',
                'opsi_b' => 'In-order Traversal',
                'opsi_c' => 'Post-order Traversal',
                'opsi_d' => 'Topological Sort',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Traversal pohon biner dengan urutan: Left Subtree -> Right Subtree -> Root disebut...',
                'opsi_a' => 'Post-order Traversal',
                'opsi_b' => 'Pre-order Traversal',
                'opsi_c' => 'In-order Traversal',
                'opsi_d' => 'Breadth First Search',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Struktur data apa yang digunakan untuk mengimplementasikan algoritma Breadth-First Search (BFS)?',
                'opsi_a' => 'Stack',
                'opsi_b' => 'Queue',
                'opsi_c' => 'Priority Queue berbasis Max-Heap',
                'opsi_d' => 'Disjoint Set Union',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Struktur data apa yang digunakan untuk mengimplementasikan algoritma Depth-First Search (DFS) secara iteratif?',
                'opsi_a' => 'Queue',
                'opsi_b' => 'Stack',
                'opsi_c' => 'Hash Map',
                'opsi_d' => 'B-Tree',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Algoritma yang digunakan untuk menemukan lintasan terpendek (Single Source Shortest Path) pada graf berbobot non-negatif adalah...',
                'opsi_a' => 'Algoritma Dijkstra',
                'opsi_b' => 'Algoritma Kruskal',
                'opsi_c' => 'Algoritma Prim',
                'opsi_d' => 'Algoritma Floyd-Warshall',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Algoritma Minimum Spanning Tree (MST) yang bekerja dengan mengurutkan seluruh sisi berdasarkan bobot terkecil adalah...',
                'opsi_a' => 'Algoritma Dijkstra',
                'opsi_b' => 'Algoritma Kruskal',
                'opsi_c' => 'Algoritma Bellman-Ford',
                'opsi_d' => 'Algoritma Tarjan',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Algoritma Minimum Spanning Tree (MST) yang dimulai dari satu simpul asal lalu menambahkan simpul terdekat secara bertahap adalah...',
                'opsi_a' => 'Algoritma Prim',
                'opsi_b' => 'Algoritma Kruskal',
                'opsi_c' => 'Algoritma A* (A-Star)',
                'opsi_d' => 'Algoritma DFS',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Karakteristik utama dari Binary Search Tree (BST) adalah...',
                'opsi_a' => 'Semua daun berada pada kedalaman yang sama persis',
                'opsi_b' => 'Subtree kiri berisi node bernilai lebih kecil dari root, dan subtree kanan bernilai lebih besar',
                'opsi_c' => 'Setiap node selalu memiliki tepat 2 anak',
                'opsi_d' => 'Data disimpan secara acak di setiap level',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Jika elemen dimasukkan ke dalam BST dalam keadaan terurut menaik (ascending), maka bentuk BST menjadi...',
                'opsi_a' => 'Pohon seimbang sempurna (Perfect Tree)',
                'opsi_b' => 'Skewed Tree (seperti linked list) dengan kompleksitas pencarian O(n)',
                'opsi_c' => 'Binary Heap',
                'opsi_d' => 'Trie',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Pohon biner pencarian yang secara mandiri menjaga keseimbangan tinggi (self-balancing) dengan faktor keseimbangan -1, 0, atau 1 adalah...',
                'opsi_a' => 'AVL Tree',
                'opsi_b' => 'Binary Heap',
                'opsi_c' => 'Spanning Tree',
                'opsi_d' => 'Huffman Tree',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Struktur data yang memetakan kunci (key) ke nilai (value) menggunakan fungsi matematis hash adalah...',
                'opsi_a' => 'Hash Table',
                'opsi_b' => 'Linked List',
                'opsi_c' => 'Binary Tree',
                'opsi_d' => 'Stack',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Metode penanganan tabrakan (collision resolution) pada Hash Table dengan membuat rantai linked list di setiap index array disebut...',
                'opsi_a' => 'Separate Chaining',
                'opsi_b' => 'Linear Probing',
                'opsi_c' => 'Quadratic Probing',
                'opsi_d' => 'Double Hashing',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Metode penanganan tabrakan pada Hash Table dengan mencari slot kosong berikutnya dalam array yang sama disebut...',
                'opsi_a' => 'Open Addressing (Linear Probing)',
                'opsi_b' => 'Separate Chaining',
                'opsi_c' => 'Dynamic Hashing',
                'opsi_d' => 'Rehashing',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Berapa kompleksitas waktu rata-rata (average case) operasi pencarian/lookup pada Hash Table yang baik?',
                'opsi_a' => 'O(1)',
                'opsi_b' => 'O(log n)',
                'opsi_c' => 'O(n)',
                'opsi_d' => 'O(n log n)',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Setiap node pada struktur data Singly Linked List menyimpan...',
                'opsi_a' => 'Hanya nilai data',
                'opsi_b' => 'Data dan pointer yang menunjuk ke node berikutnya (next)',
                'opsi_c' => 'Data dan dua pointer (next dan prev)',
                'opsi_d' => 'Index array dan panjang list',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Keuntungan utama Doubly Linked List dibandingkan Singly Linked List adalah...',
                'opsi_a' => 'Menggunakan memori lebih sedikit',
                'opsi_b' => 'Dapat melakukan traversal maju maupun mundur (dua arah)',
                'opsi_c' => 'Waktu akses elemen ke-i menjadi O(1)',
                'opsi_d' => 'Tidak memerlukan pointer sama sekali',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Struktur data Linked List di mana pointer node terakhir menunjuk kembali ke node pertama disebut...',
                'opsi_a' => 'Circular Linked List',
                'opsi_b' => 'Doubly Linked List',
                'opsi_c' => 'Skip List',
                'opsi_d' => 'Static List',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Pada struktur data Min-Heap, sifat utama dari setiap node parent adalah...',
                'opsi_a' => 'Selalu bernilai lebih besar dari semua child-nya',
                'opsi_b' => 'Selalu bernilai lebih kecil atau sama dengan child-child-nya',
                'opsi_c' => 'Harus memiliki tepat 3 anak',
                'opsi_d' => 'Bernilai negatif',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Kompleksitas waktu untuk menghapus elemen puncak (root) dan melakukan heapify pada Binary Heap adalah...',
                'opsi_a' => 'O(1)',
                'opsi_b' => 'O(log n)',
                'opsi_c' => 'O(n)',
                'opsi_d' => 'O(n^2)',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Struktur data apa yang paling efisien untuk mengimplementasikan antrean prioritas (Priority Queue)?',
                'opsi_a' => 'Array tidak terurut',
                'opsi_b' => 'Binary Heap',
                'opsi_c' => 'Singly Linked List',
                'opsi_d' => 'Stack',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Berapa kompleksitas waktu untuk mengakses elemen array berdasarkan indeks (random access)?',
                'opsi_a' => 'O(1)',
                'opsi_b' => 'O(log n)',
                'opsi_c' => 'O(n)',
                'opsi_d' => 'O(n log n)',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Struktur data yang paling hemat memori untuk merepresentasikan graf yang jarang (sparse graph) adalah...',
                'opsi_a' => 'Adjacency Matrix',
                'opsi_b' => 'Adjacency List',
                'opsi_c' => 'Dense Array',
                'opsi_d' => 'Incidence Matrix',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Representasi graf dengan matriks 2D berukuran V x V di mana V adalah jumlah simpul disebut...',
                'opsi_a' => 'Adjacency List',
                'opsi_b' => 'Adjacency Matrix',
                'opsi_c' => 'Edge List',
                'opsi_d' => 'Adjacency Map',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Algoritma pengurutan yang berulang kali membandingkan pasangan elemen bertetangga dan menukarnya jika urutannya salah adalah...',
                'opsi_a' => 'Bubble Sort',
                'opsi_b' => 'Merge Sort',
                'opsi_c' => 'Quick Sort',
                'opsi_d' => 'Counting Sort',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Algoritma pengurutan yang mencari elemen minimum dari bagian yang belum terurut dan menukarnya ke posisi awal adalah...',
                'opsi_a' => 'Selection Sort',
                'opsi_b' => 'Insertion Sort',
                'opsi_c' => 'Radix Sort',
                'opsi_d' => 'Bucket Sort',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Algoritma pengurutan yang membangun larik terurut satu per satu dengan menyisipkan elemen ke tempat yang tepat adalah...',
                'opsi_a' => 'Insertion Sort',
                'opsi_b' => 'Selection Sort',
                'opsi_c' => 'Heap Sort',
                'opsi_d' => 'Bubble Sort',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Algoritma sorting non-perbandingan yang mengelompokkan elemen berdasarkan nilai kunci integer dan menghitung frekuensinya adalah...',
                'opsi_a' => 'Counting Sort',
                'opsi_b' => 'Quick Sort',
                'opsi_c' => 'Merge Sort',
                'opsi_d' => 'Selection Sort',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Paradigma pemecahan masalah yang memecah persoalan besar menjadi sub-masalah independen, menyelesaikannya, dan menggabungkannya adalah...',
                'opsi_a' => 'Greedy Algorithm',
                'opsi_b' => 'Divide and Conquer',
                'opsi_c' => 'Brute Force',
                'opsi_d' => 'Backtracking',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Paradigma algoritma yang memecahkan masalah optimasi dengan menyimpan hasil dari sub-masalah yang tumpang tindih (overlapping) adalah...',
                'opsi_a' => 'Dynamic Programming',
                'opsi_b' => 'Divide and Conquer murni',
                'opsi_c' => 'Randomized Algorithm',
                'opsi_d' => 'Linear Search',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Pendekatan Dynamic Programming yang bersifat Top-Down dengan fungsi rekursif dan tabel memori (cache) disebut...',
                'opsi_a' => 'Memoization',
                'opsi_b' => 'Tabulation',
                'opsi_c' => 'Greedy Choice',
                'opsi_d' => 'Pruning',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Pendekatan Dynamic Programming yang bersifat Bottom-Up dengan mengisi tabel iteratif mulai dari kasus dasar (base cases) disebut...',
                'opsi_a' => 'Tabulation',
                'opsi_b' => 'Memoization',
                'opsi_c' => 'Branch and Bound',
                'opsi_d' => 'Simulated Annealing',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Masalah klasik penentuan rute terpendek yang mengunjungi setiap kota tepat satu kali dan kembali ke kota awal disebut...',
                'opsi_a' => 'Travelling Salesperson Problem (TSP)',
                'opsi_b' => 'Knapsack Problem',
                'opsi_c' => 'Tower of Hanoi',
                'opsi_d' => 'Longest Common Subsequence',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Algoritma pencocokan pola teks (string matching) efisien yang menggunakan pre-processing prefix function adalah...',
                'opsi_a' => 'Knuth-Morris-Pratt (KMP)',
                'opsi_b' => 'Dijkstra',
                'opsi_c' => 'Huffman Coding',
                'opsi_d' => 'Floyd-Warshall',
                'jawaban_benar' => 'a',
            ],
            [
                'pertanyaan' => 'Berapa kompleksitas ruang (space complexity) dari algoritma rekursif yang memanggil dirinya sendiri sedalam n tingkat?',
                'opsi_a' => 'O(1)',
                'opsi_b' => 'O(n) karena tumpukan pemanggilan (Call Stack)',
                'opsi_c' => 'O(n^2)',
                'opsi_d' => 'O(log n)',
                'jawaban_benar' => 'b',
            ],
            [
                'pertanyaan' => 'Algoritma penjelajahan graf yang dapat digunakan untuk mendeteksi siklus (cycle) pada graf berarah adalah...',
                'opsi_a' => 'DFS dengan status kunjungan simpul (White, Gray, Black)',
                'opsi_b' => 'Binary Search',
                'opsi_c' => 'Kruskal Algorithm',
                'opsi_d' => 'Selection Sort',
                'jawaban_benar' => 'a',
            ],
        ];

        $pilganQuestions = [];
        foreach ($soalList as $index => $item) {
            $nomor = $index + 1;
            $q = Question::updateOrCreate(
                [
                    'test_id' => $testPilgan->id,
                    'pertanyaan' => "Soal #{$nomor}: {$item['pertanyaan']}",
                ],
                [
                    'tipe' => 'pilihan_ganda',
                    'opsi_a' => $item['opsi_a'],
                    'opsi_b' => $item['opsi_b'],
                    'opsi_c' => $item['opsi_c'],
                    'opsi_d' => $item['opsi_d'],
                    'jawaban_benar' => $item['jawaban_benar'],
                ]
            );
            $pilganQuestions[] = $q;
        }

        if ($mhs2) {
            // Attempt 1: Nilai 40 (< KKM 70) - Gagal (Siap Remedial)
            $subset1 = array_slice($pilganQuestions, 0, 30);
            $subsetIds1 = array_map(fn ($q) => $q->id, $subset1);
            $attempt1 = Attempt::updateOrCreate(
                ['test_id' => $testPilgan->id, 'user_id' => $mhs2->id, 'nilai' => 40.0],
                [
                    'started_at' => now()->subHours(2),
                    'finished_at' => now()->subHours(1)->subMinutes(15),
                    'question_ids' => $subsetIds1,
                ]
            );

            foreach ($subset1 as $idx => $q) {
                $isCorrect = ($idx < 12);
                $ans = $isCorrect ? $q->jawaban_benar : ($q->jawaban_benar === 'a' ? 'b' : 'a');
                Answer::updateOrCreate(
                    ['attempt_id' => $attempt1->id, 'question_id' => $q->id],
                    ['jawaban' => $ans, 'benar' => $isCorrect]
                );
            }

            // Attempt 2: Nilai 80 (>= KKM 70) - Lulus
            $subset2 = array_slice($pilganQuestions, 10, 30);
            $subsetIds2 = array_map(fn ($q) => $q->id, $subset2);
            $attempt2 = Attempt::updateOrCreate(
                ['test_id' => $testPilgan->id, 'user_id' => $mhs2->id, 'nilai' => 80.0],
                [
                    'started_at' => now()->subMinutes(50),
                    'finished_at' => now()->subMinutes(10),
                    'question_ids' => $subsetIds2,
                ]
            );

            foreach ($subset2 as $idx => $q) {
                $isCorrect = ($idx < 24);
                $ans = $isCorrect ? $q->jawaban_benar : ($q->jawaban_benar === 'a' ? 'b' : 'a');
                Answer::updateOrCreate(
                    ['attempt_id' => $attempt2->id, 'question_id' => $q->id],
                    ['jawaban' => $ans, 'benar' => $isCorrect]
                );
            }
        }
    }
}
