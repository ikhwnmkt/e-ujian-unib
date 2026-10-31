<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTipeAndLiveMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dosen = User::create([
            'name' => 'Dosen Penguji',
            'email' => 'dosen_test@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'dosen',
            'nomor_induk' => '19800101',
        ]);

        $this->mahasiswa = User::create([
            'name' => 'Mahasiswa Ujian',
            'email' => 'mhs_test@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => '240509001',
        ]);
    }

    public function test_pilihan_ganda_test_calculates_score_automatically(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Pilgan',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        $q1 = Question::create([
            'test_id' => $test->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => '1 + 1 = ?',
            'opsi_a' => '1',
            'opsi_b' => '2',
            'opsi_c' => '3',
            'opsi_d' => '4',
            'jawaban_benar' => 'b',
        ]);

        $q2 = Question::create([
            'test_id' => $test->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => 'Ibu kota Indonesia?',
            'opsi_a' => 'Bandung',
            'opsi_b' => 'Jakarta',
            'opsi_c' => 'Medan',
            'opsi_d' => 'Surabaya',
            'jawaban_benar' => 'b',
        ]);

        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [
                $q1->id => 'b', // benar
                $q2->id => 'a', // salah
            ],
        ]);

        $response->assertRedirect(route('mhs.hasil', $test));

        $attempt->refresh();
        $this->assertNotNull($attempt->finished_at);
        $this->assertEquals(50.0, (float) $attempt->nilai);
    }

    public function test_essai_test_leaves_score_null_on_submit(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Essai',
            'tipe_ujian' => 'essai',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        $q1 = Question::create([
            'test_id' => $test->id,
            'tipe' => 'essai',
            'pertanyaan' => 'Jelaskan konsep OOP!',
        ]);

        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban_essai' => [
                $q1->id => 'OOP adalah object oriented programming...',
            ],
        ]);

        $response->assertRedirect(route('mhs.hasil', $test));

        $attempt->refresh();
        $this->assertNotNull($attempt->finished_at);
        $this->assertNull($attempt->nilai); // Status: Menunggu Penilaian
    }

    public function test_mahasiswa_can_autosave_essay_answer(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Essai AutoSave',
            'tipe_ujian' => 'essai',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        $q = Question::create([
            'test_id' => $test->id,
            'tipe' => 'essai',
            'pertanyaan' => 'Jelaskan MVC!',
        ]);

        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->mahasiswa)->postJson('/mahasiswa/ujian/' . $test->id . '/autosave', [
            'question_id' => $q->id,
            'jawaban_essai' => 'Model View Controller memisahkan logic dan presentation...',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'saved',
            ])
            ->assertJsonStructure(['status', 'updated_at']);

        $this->assertDatabaseHas('answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $q->id,
            'jawaban_essai' => 'Model View Controller memisahkan logic dan presentation...',
        ]);
    }

    public function test_dosen_can_view_live_monitoring(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Live Monitoring',
            'tipe_ujian' => 'essai',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        $q = Question::create([
            'test_id' => $test->id,
            'tipe' => 'essai',
            'pertanyaan' => 'Deskripsikan arsitektur microservices!',
        ]);

        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now(),
        ]);

        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q->id,
            'jawaban_essai' => 'Sedang mengetik poin pertama...',
        ]);

        $response = $this->actingAs($this->dosen)->getJson('/dosen/ujian/' . $test->id . '/live-monitoring');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'test' => [
                    'id' => $test->id,
                    'tipe_ujian' => 'essai',
                ],
            ])
            ->assertJsonStructure([
                'status',
                'test',
                'server_time',
                'attempts' => [
                    '*' => [
                        'attempt_id',
                        'user' => ['id', 'name', 'nomor_induk'],
                        'answers' => [
                            '*' => ['question_id', 'jawaban_essai', 'updated_at'],
                        ],
                    ],
                ],
            ]);
    }

    public function test_dosen_can_input_essay_grade(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Penilaian Essai',
            'tipe_ujian' => 'essai',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now(),
            'finished_at' => now(),
            'nilai' => null,
        ]);

        // JSON submission
        $response = $this->actingAs($this->dosen)->postJson('/dosen/ujian/' . $test->id . '/nilai/' . $attempt->id, [
            'nilai' => 88.5,
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'attempt_id' => $attempt->id,
                'nilai' => 88.5,
            ]);

        $attempt->refresh();
        $this->assertEquals(88.5, (float) $attempt->nilai);
    }

    public function test_randomization_subset_questions_with_jumlah_soal_tampil(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Subset Pilgan',
            'materi' => 'Struktur Data',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 4,
            'kkm' => 70,
        ]);

        // Buat 10 soal di bank soal
        $questions = [];
        for ($i = 1; $i <= 10; $i++) {
            $questions[] = Question::create([
                'test_id' => $test->id,
                'tipe' => 'pilihan_ganda',
                'pertanyaan' => "Soal nomor {$i}",
                'opsi_a' => 'A',
                'opsi_b' => 'B',
                'opsi_c' => 'C',
                'opsi_d' => 'D',
                'jawaban_benar' => 'a',
            ]);
        }

        // Mahasiswa memulai ujian
        $response = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $response->assertOk();

        $attempt = Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->first();
        $this->assertNotNull($attempt);
        $this->assertCount(4, $attempt->question_ids);

        // Refresh halaman: pastikan soal tidak ter-acak ulang
        $responseRefresh = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $responseRefresh->assertOk();
        $attempt->refresh();
        $this->assertCount(4, $attempt->question_ids);

        // Jawab 3 benar, 1 salah
        $firstThree = array_slice($attempt->question_ids, 0, 3);
        $lastOne = array_slice($attempt->question_ids, 3, 1)[0];

        $jawaban = [];
        foreach ($firstThree as $qid) {
            $jawaban[$qid] = 'a'; // benar
        }
        $jawaban[$lastOne] = 'b'; // salah

        $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => $jawaban,
        ]);

        $attempt->refresh();
        // 3 dari 4 soal benar = 75.0
        $this->assertEquals(75.0, (float) $attempt->nilai);
        $this->assertTrue($attempt->isPassed());
    }

    public function test_remedial_flow_when_score_below_kkm(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Remedial Test',
            'materi' => 'Matematika Diskrit',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 75,
        ]);

        $q1 = Question::create([
            'test_id' => $test->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => '2 x 2 = ?',
            'opsi_a' => '4', 'opsi_b' => '5', 'opsi_c' => '6', 'opsi_d' => '7',
            'jawaban_benar' => 'a',
        ]);
        $q2 = Question::create([
            'test_id' => $test->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => '3 x 3 = ?',
            'opsi_a' => '9', 'opsi_b' => '10', 'opsi_c' => '11', 'opsi_d' => '12',
            'jawaban_benar' => 'a',
        ]);

        // Attempt 1: Mahasiswa ikut ujian
        $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $attempt1 = Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->latest('id')->first();

        // Submit 1 benar 1 salah = nilai 50.0 (< KKM 75)
        $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [
                $q1->id => 'a',
                $q2->id => 'b',
            ],
        ]);

        $attempt1->refresh();
        $this->assertEquals(50.0, (float) $attempt1->nilai);
        $this->assertFalse($attempt1->isPassed());
        $this->assertTrue($attempt1->canRemedial());

        // Mahasiswa mengulang ujian (Remedial) saat jadwal masih berlangsung
        $responseRemedial = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $responseRemedial->assertOk();

        $attempt2 = Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->latest('id')->first();
        $this->assertNotEquals($attempt1->id, $attempt2->id);
        $this->assertNull($attempt2->finished_at);

        // Submit Attempt 2 dengan jawaban benar semua = 100.0 (>= KKM 75)
        $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [
                $q1->id => 'a',
                $q2->id => 'a',
            ],
        ]);

        $attempt2->refresh();
        $this->assertEquals(100.0, (float) $attempt2->nilai);
        $this->assertTrue($attempt2->isPassed());
        $this->assertFalse($attempt2->canRemedial());

        // Jika mahasiswa mencoba akses mulai lagi setelah lulus, diarahkan ke hasil (tidak boleh buat attempt ke-3)
        $responseAfterPass = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $responseAfterPass->assertRedirect(route('mhs.hasil', $test));

        $totalAttempts = Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->count();
        $this->assertEquals(2, $totalAttempts);
    }

    public function test_essai_cannot_remedial(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Essai No Remedial',
            'materi' => 'Filsafat Ilmu',
            'tipe_ujian' => 'essai',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'kkm' => 70,
        ]);

        $q = Question::create([
            'test_id' => $test->id,
            'tipe' => 'essai',
            'pertanyaan' => 'Jelaskan epistemologi!',
        ]);

        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now(),
            'finished_at' => now(),
            'nilai' => 40.0, // di bawah KKM tapi tipe essai
        ]);

        // Essai tidak boleh remedial, harus langsung redirect ke hasil
        $response = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $response->assertRedirect(route('mhs.hasil', $test));

        $this->assertEquals(1, Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->count());
    }
}
