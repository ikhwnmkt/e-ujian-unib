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
}
