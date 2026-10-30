<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamEdgeCasesAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dosen = User::create([
            'name' => 'Dosen Penguji Edge Cases',
            'email' => 'dosen_edge@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'dosen',
            'nomor_induk' => '19850101',
        ]);

        $this->mahasiswa = User::create([
            'name' => 'Mahasiswa Edge Cases',
            'email' => 'mhs_edge@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => '240509099',
        ]);
    }

    public function test_calling_mulai_twice_consecutively_reuses_same_active_attempt(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Concurrency Check',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        Question::create([
            'test_id' => $test->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => 'Soal 1',
            'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D',
            'jawaban_benar' => 'a',
        ]);
        Question::create([
            'test_id' => $test->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => 'Soal 2',
            'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D',
            'jawaban_benar' => 'b',
        ]);

        // Panggilan mulai pertama
        $response1 = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $response1->assertOk();

        $attemptsAfterFirst = Attempt::where('test_id', $test->id)
            ->where('user_id', $this->mahasiswa->id)
            ->get();
        $this->assertCount(1, $attemptsAfterFirst);
        $firstAttempt = $attemptsAfterFirst->first();
        $this->assertNull($firstAttempt->finished_at);

        // Panggilan mulai kedua (simulasi request berulang / tab ganda)
        $response2 = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $response2->assertOk();

        $attemptsAfterSecond = Attempt::where('test_id', $test->id)
            ->where('user_id', $this->mahasiswa->id)
            ->get();
        // Harus tetap 1 attempt saja, tidak boleh ada attempt kedua
        $this->assertCount(1, $attemptsAfterSecond);
        $this->assertEquals($firstAttempt->id, $attemptsAfterSecond->first()->id);
        $this->assertEquals($firstAttempt->question_ids, $attemptsAfterSecond->first()->question_ids);
    }

    public function test_kirim_is_idempotent_and_does_not_regrade_or_error_on_second_submit(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Idempotensi Kirim',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        $q1 = Question::create([
            'test_id' => $test->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => 'Soal 1',
            'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D',
            'jawaban_benar' => 'a',
        ]);
        $q2 = Question::create([
            'test_id' => $test->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => 'Soal 2',
            'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D',
            'jawaban_benar' => 'b',
        ]);

        // Mulai ujian
        $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $attempt = Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->first();

        // Submit pertama: 1 benar, 1 salah -> nilai 50.0
        $res1 = $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [
                $q1->id => 'a', // benar
                $q2->id => 'c', // salah
            ],
        ]);
        $res1->assertRedirect(route('mhs.hasil', $test));

        $attempt->refresh();
        $this->assertNotNull($attempt->finished_at);
        $this->assertEquals(50.0, (float) $attempt->nilai);

        // Submit kedua pada attempt yang sama (misal jaringan lemot atau klik dobel) dengan jawaban berbeda
        $res2 = $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [
                $q1->id => 'a',
                $q2->id => 'b', // mencoba manipulasi
            ],
        ]);
        $res2->assertRedirect(route('mhs.hasil', $test));

        $attempt->refresh();
        // Nilai tetap 50.0, tidak tertimpa dan tidak terjadi error
        $this->assertEquals(50.0, (float) $attempt->nilai);
    }

    public function test_mulai_rejected_when_test_has_zero_questions(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Tanpa Soal',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 10,
            'kkm' => 70,
        ]);

        // Tes ini belum punya soal sama sekali
        $this->assertEquals(0, $test->questions()->count());

        $response = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));

        // Harus ditolak dan diredirect ke dashboard dengan error
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('err', 'Soal ujian belum tersedia. Silakan hubungi dosen pengampu.');

        // Tidak boleh ada attempt yang terbentuk
        $this->assertEquals(0, Attempt::where('test_id', $test->id)->count());
    }

    public function test_divider_adapts_to_deleted_questions_without_error(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Soal Terhapus',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 4,
            'kkm' => 70,
        ]);

        $q1 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        $q2 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q2', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        $q3 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q3', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        $q4 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q4', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);

        // Mahasiswa mulai ujian -> question_ids berisi 4 id
        $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $attempt = Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->first();
        $this->assertCount(4, $attempt->question_ids);

        // Dosen menghapus 1 soal (q4) dari database saat attempt mahasiswa sedang berjalan
        $q4->delete();

        // Mahasiswa submit jawaban untuk 3 soal yang tersisa (semua benar)
        $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [
                $q1->id => 'a',
                $q2->id => 'a',
                $q3->id => 'a',
            ],
        ]);

        $attempt->refresh();
        // Pembagi turun menjadi 3 (hanya soal yang masih ada di DB).
        // Karena 3 benar dari 3 soal yang tersisa -> nilai = 100.0 (bukan 75.0)
        $this->assertEquals(100.0, (float) $attempt->nilai);
    }

    public function test_attempt_past_deadline_is_automatically_finalized_on_mulai(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Expired Deadline Check',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60, // 60 menit
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        $q1 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        $q2 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q2', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);

        // Simulasi attempt yang dimulai 2 jam lalu (sudah lewat durasi 60 menit) dan belum selesai
        $expiredAttempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now()->subHours(2),
            'finished_at' => null,
            'question_ids' => [$q1->id, $q2->id],
        ]);

        // Jawaban q1 tersimpan benar via auto-save sebelumnya
        Answer::create([
            'attempt_id' => $expiredAttempt->id,
            'question_id' => $q1->id,
            'jawaban' => 'a',
            'benar' => true,
        ]);

        // Mahasiswa kembali membuka halaman mulai ujian
        $response = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $response->assertOk();

        // Attempt lama harus sudah ter-finalize otomatis dengan nilai 50.0 (1 benar dari 2 soal)
        $expiredAttempt->refresh();
        $this->assertNotNull($expiredAttempt->finished_at);
        $this->assertEquals(50.0, (float) $expiredAttempt->nilai);

        // Karena nilainya 50.0 (< KKM 70) dan jadwal tes masih berlangsung, dibuatkan attempt baru (remedial)
        $newAttempt = Attempt::where('test_id', $test->id)
            ->where('user_id', $this->mahasiswa->id)
            ->latest('id')
            ->first();

        $this->assertNotEquals($expiredAttempt->id, $newAttempt->id);
        $this->assertNull($newAttempt->finished_at);
    }
}
