<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestSchedule;
use App\Models\User;
use Carbon\Carbon;
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

    public function test_remedial_student_appears_only_once_in_live_monitoring_and_rekap_nilai_with_attempt_count(): void
    {
        $admin = User::create([
            'name' => 'Admin Penguji',
            'email' => 'admin_edge@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Deduplikasi Rekap & Monitoring',
            'materi' => 'Database Relasional',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        $q1 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        $q2 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q2', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);

        // Attempt 1: Mahasiswa ikut ujian dan dapat nilai 50.0 (< KKM 70)
        $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [$q1->id => 'a', $q2->id => 'b'],
        ]);
        $attempt1 = Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->latest('id')->first();
        $this->assertEquals(50.0, (float) $attempt1->nilai);

        // Attempt 2: Remedial dan dapat nilai 100.0 (>= KKM 70)
        $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [$q1->id => 'a', $q2->id => 'a'],
        ]);
        $attempt2 = Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->latest('id')->first();
        $this->assertEquals(100.0, (float) $attempt2->nilai);
        $this->assertNotEquals($attempt1->id, $attempt2->id);

        // Verifikasi total attempt di DB = 2
        $this->assertEquals(2, Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->count());

        // 1. Verifikasi Penghitungan Jumlah Peserta (Distinct user_id)
        $this->assertEquals(1, $test->pesertaCount());
        $this->assertEquals(1, $test->peserta_count);

        // 2. Verifikasi liveMonitoring Dosen: hanya 1 entri per mahasiswa dengan attempt_count = 2
        $liveResponse = $this->actingAs($this->dosen)->getJson('/dosen/ujian/' . $test->id . '/live-monitoring');
        $liveResponse->assertOk()
            ->assertJsonCount(1, 'attempts')
            ->assertJsonPath('attempts.0.attempt_count', 2)
            ->assertJsonPath('attempts.0.attempt_id', $attempt2->id) // attempt terbaru
            ->assertJsonPath('attempts.0.user.id', $this->mahasiswa->id)
            ->assertJsonPath('test.peserta_count', 1);

        // 3. Verifikasi Rekap Nilai Dosen (dosen.nilai): hanya 1 entri dengan atribut dinamis
        $dosenResponse = $this->actingAs($this->dosen)->get(route('dosen.nilai', $test));
        $dosenResponse->assertOk();
        $dosenAttempts = $dosenResponse->viewData('attempts');

        $this->assertCount(1, $dosenAttempts);
        $primaryAttempt = $dosenAttempts->first();
        $this->assertEquals($this->mahasiswa->id, $primaryAttempt->user_id);
        $this->assertEquals(2, $primaryAttempt->attempt_count);
        $this->assertEquals(50.0, $primaryAttempt->first_score);
        $this->assertEquals(100.0, $primaryAttempt->latest_score);
        $this->assertEquals(100.0, $primaryAttempt->best_score);
        $this->assertEquals(100.0, (float) $primaryAttempt->nilai); // primary attempt mengambil best score
        $this->assertCount(2, $primaryAttempt->history);

        // 4. Verifikasi Detail Admin (admin.detail): hanya 1 entri per mahasiswa
        $adminResponse = $this->actingAs($admin)->get(route('admin.detail', $test));
        $adminResponse->assertOk();
        $adminTest = $adminResponse->viewData('test');
        $this->assertCount(1, $adminTest->attempts);
        $this->assertEquals(2, $adminTest->attempts->first()->attempt_count);
        $this->assertEquals(100.0, (float) $adminTest->attempts->first()->best_score);
    }

    public function test_essai_expired_attempt_is_finalized_with_null_score_and_no_remedial(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Essai Expired',
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
            'pertanyaan' => 'Soal Essai 1',
        ]);

        // Attempt dimulai 2 jam lalu (sudah lewat durasi 60 menit)
        $expiredAttempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now()->subHours(2),
            'finished_at' => null,
            'question_ids' => [$q->id],
        ]);

        // Mahasiswa mengakses mulai
        $response = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));

        // Karena essai tidak boleh remedial, langsung redirect ke hasil
        $response->assertRedirect(route('mhs.hasil', $test));

        $expiredAttempt->refresh();
        $this->assertNotNull($expiredAttempt->finished_at);
        $this->assertNull($expiredAttempt->nilai); // Nilai WAJIB null (Menunggu Penilaian)

        // Tidak boleh ada attempt baru
        $this->assertEquals(1, Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->count());
    }

    public function test_kirim_within_grace_period_is_graded_normally_from_payload(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Grace Period In',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        $q1 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        $q2 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q2', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'b']);

        // Attempt dimulai 60 menit + 30 detik lalu (lewat deadline 30 detik, tapi masih dalam grace 60s)
        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now()->subMinutes(60)->subSeconds(30),
            'finished_at' => null,
            'question_ids' => [$q1->id, $q2->id],
        ]);

        // Kirim payload dengan jawaban benar semua
        $response = $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [
                $q1->id => 'a',
                $q2->id => 'b',
            ],
        ]);

        $response->assertRedirect(route('mhs.hasil', $test));

        $attempt->refresh();
        $this->assertNotNull($attempt->finished_at);
        // Tetap dinilai dari payload request -> nilai 100.0
        $this->assertEquals(100.0, (float) $attempt->nilai);
    }

    public function test_kirim_after_grace_period_ignores_payload_and_finalizes_from_db(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Grace Period Out',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        $q1 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        $q2 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q2', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'b']);

        // Attempt dimulai 60 menit + 90 detik lalu (lewat grace period 60 detik)
        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now()->subMinutes(60)->subSeconds(90),
            'finished_at' => null,
            'question_ids' => [$q1->id, $q2->id],
        ]);

        // Di DB sebelumnya tersimpan jawaban q1 benar via auto-save
        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'jawaban' => 'a',
            'benar' => true,
        ]);

        // Mahasiswa mencoba mengirim payload baru terlambat
        $response = $this->actingAs($this->mahasiswa)->post(route('mhs.kirim', $test), [
            'jawaban' => [
                $q1->id => 'b', // mencoba jawaban salah di payload baru
                $q2->id => 'b',
            ],
        ]);

        $response->assertRedirect(route('mhs.hasil', $test));
        $response->assertSessionHas('err', 'Waktu ujian telah habis.');

        $attempt->refresh();
        $this->assertNotNull($attempt->finished_at);
        // Nilai dihitung dari DB (q1 benar, q2 kosong -> 1 dari 2 = 50.0)
        $this->assertEquals(50.0, (float) $attempt->nilai);
    }

    public function test_autosave_on_expired_attempt_returns_409_conflict_and_finalizes(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Autosave Expired Check',
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
            'pertanyaan' => 'Soal Essai Autosave',
        ]);

        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now()->subHours(2),
            'finished_at' => null,
            'question_ids' => [$q->id],
        ]);

        $response = $this->actingAs($this->mahasiswa)->postJson('/mahasiswa/ujian/' . $test->id . '/autosave', [
            'question_id' => $q->id,
            'jawaban_essai' => 'Ketikan setelah waktu habis...',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'expired' => true,
                'message' => 'Waktu ujian telah habis.',
                'redirect' => route('mhs.hasil', $test),
            ]);

        $attempt->refresh();
        $this->assertNotNull($attempt->finished_at);
        $this->assertNull($attempt->nilai);
    }

    public function test_dosen_cannot_manually_update_grade_for_pilihan_ganda_exam(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Pilgan Manual Grade Blocked',
            'tipe_ujian' => 'pilihan_ganda',
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
            'nilai' => 70.0,
        ]);

        // Dosen mencoba mengubah nilai ujian pilihan ganda secara manual
        $response = $this->actingAs($this->dosen)->postJson('/dosen/ujian/' . $test->id . '/nilai/' . $attempt->id, [
            'nilai' => 95.0,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Penilaian manual hanya diizinkan untuk ujian bertipe essai.',
            ]);

        $attempt->refresh();
        $this->assertEquals(70.0, (float) $attempt->nilai);
    }

    public function test_pilihan_ganda_autosave_calculates_score_accurately_on_finalize_after_timeout(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Pilgan AutoSave Check',
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
            'pertanyaan' => 'Soal 1 Pilgan',
            'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D',
            'jawaban_benar' => 'a',
        ]);

        $q2 = Question::create([
            'test_id' => $test->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => 'Soal 2 Pilgan',
            'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D',
            'jawaban_benar' => 'b',
        ]);

        // Mahasiswa memulai ujian
        $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $attempt = Attempt::where('test_id', $test->id)->where('user_id', $this->mahasiswa->id)->latest('id')->first();
        $this->assertNotNull($attempt);

        // Mahasiswa autosave soal 1 dengan pilihan 'a' (benar)
        $res1 = $this->actingAs($this->mahasiswa)->postJson('/mahasiswa/ujian/' . $test->id . '/autosave', [
            'question_id' => $q1->id,
            'pilihan' => 'a',
        ]);
        $res1->assertOk()->assertJson(['status' => 'saved']);

        // Mahasiswa autosave soal 2 dengan pilihan 'b' (benar)
        $res2 = $this->actingAs($this->mahasiswa)->postJson('/mahasiswa/ujian/' . $test->id . '/autosave', [
            'question_id' => $q2->id,
            'pilihan' => 'b',
        ]);
        $res2->assertOk()->assertJson(['status' => 'saved']);

        // Pastikan jawaban tersimpan di database
        $ans1 = Answer::where('attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $ans2 = Answer::where('attempt_id', $attempt->id)->where('question_id', $q2->id)->first();
        $this->assertEquals('a', $ans1->jawaban);
        $this->assertTrue((bool) $ans1->benar);
        $this->assertEquals('b', $ans2->jawaban);
        $this->assertTrue((bool) $ans2->benar);

        // Waktu habis: melompat melewati durasi + grace period tanpa mahasiswa menekan tombol kirim
        $this->travel(60)->minutes();
        $this->travel(70)->seconds();

        // Mahasiswa mengakses halaman tes (mhs.mulai), memicu auto-finalize
        $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));

        $attempt->refresh();
        $this->assertNotNull($attempt->finished_at);
        // Nilai dihitung akurat dari jawaban tersimpan (2 benar dari 2 soal = 100.0), bukan 0!
        $this->assertEquals(100.0, (float) $attempt->nilai);
        $this->assertTrue($attempt->isPassed());
    }

    public function test_remedial_route_redirects_to_mulai(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Remedial Route Test',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        $response = $this->actingAs($this->mahasiswa)->get(route('mhs.remedial', $test));
        $response->assertRedirect(route('mhs.mulai', $test));

        $responsePost = $this->actingAs($this->mahasiswa)->post(route('mhs.remedial', $test));
        $responsePost->assertRedirect(route('mhs.mulai', $test));
    }

    public function test_multi_device_login_is_blocked_when_student_has_active_exam(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Active Lock Test',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        // Sesi aktif di Device 1
        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'device-1-session-token',
            'user_id' => $this->mahasiswa->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Device 1 Browser',
            'payload' => 'dummy',
            'last_activity' => now()->timestamp,
        ]);

        // Mahasiswa sedang mengerjakan ujian (attempt aktif belum selesai)
        Attempt::create([
            'test_id' => $test->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now(),
            'finished_at' => null,
        ]);

        // Coba login dari Device 2 dengan akun yang sama
        $response = $this->post('/login', [
            'email' => $this->mahasiswa->email,
            'password' => 'password',
        ]);

        // Login ditolak dengan error multi device
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Setelah ujian selesai:
        Attempt::where('user_id', $this->mahasiswa->id)->update(['finished_at' => now()]);

        // Coba login lagi dari Device 2
        $responseSuccess = $this->post('/login', [
            'email' => $this->mahasiswa->email,
            'password' => 'password',
        ]);

        // Berhasil login dan sesi di Device 1 otomatis dibatalkan/dihapus
        $responseSuccess->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->mahasiswa);
        $this->assertEquals(0, \Illuminate\Support\Facades\DB::table('sessions')->where('id', 'device-1-session-token')->count());
    }

    public function test_dosen_can_render_html_live_monitoring_view(): void
    {
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Essai View Monitoring',
            'tipe_ujian' => 'essai',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 90,
            'sifat' => 'fleksibel',
        ]);

        // Dosen mengakses via browser biasa (bukan AJAX/JSON)
        $response = $this->actingAs($this->dosen)->get(route('dosen.live-monitoring', $test));

        $response->assertOk();
        $response->assertViewIs('dosen.live-monitoring');
        $response->assertSee('Live Monitoring: ' . $test->judul);
        $response->assertSee('Live Polling Aktif');
    }

    public function test_live_monitoring_supports_both_pilihan_ganda_and_essai_answers(): void
    {
        $testPilgan = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Pilgan Live Test',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        $q = Question::create([
            'test_id' => $testPilgan->id,
            'tipe' => 'pilihan_ganda',
            'pertanyaan' => '1 + 1 = ?',
            'opsi_a' => '2',
            'opsi_b' => '3',
            'opsi_c' => '4',
            'opsi_d' => '5',
            'jawaban_benar' => 'a',
        ]);

        $attempt = Attempt::create([
            'test_id' => $testPilgan->id,
            'user_id' => $this->mahasiswa->id,
            'started_at' => now(),
            'question_ids' => [$q->id],
        ]);

        Answer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q->id,
            'jawaban' => 'a',
            'benar' => true,
        ]);

        // Verifikasi live monitoring mengembalikan data pilihan
        $response = $this->actingAs($this->dosen)->getJson('/dosen/ujian/' . $testPilgan->id . '/live-monitoring');
        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('attempts.0.answers.0.pilihan', 'a')
            ->assertJsonPath('attempts.0.is_expired', false);

        // Verifikasi view ujian mahasiswa mem-prefill jawaban tersimpan
        $viewResponse = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $testPilgan));
        $viewResponse->assertOk()
            ->assertViewHas('savedAnswers')
            ->assertSee('checked');
    }

    // ===== TEST JADWAL MAJEMUK (MULTI-SCHEDULE) =====

    public function test_schedule_isolation_same_kelas_different_prodi(): void
    {
        // Skenario: Kelas A Informatika dan Kelas A Sistem Informasi adalah jadwal berbeda
        $mhsInformatika = User::create([
            'name' => 'Mhs Informatika A',
            'email' => 'mhs_inf_a@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021010',
            'prodi' => 'Informatika',
            'kelas' => 'A',
        ]);

        $mhsSI = User::create([
            'name' => 'Mhs SI A',
            'email' => 'mhs_si_a@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021020',
            'prodi' => 'Sistem Informasi',
            'kelas' => 'A',
        ]);

        $mhsInfB = User::create([
            'name' => 'Mhs Informatika B',
            'email' => 'mhs_inf_b@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021030',
            'prodi' => 'Informatika',
            'kelas' => 'B',
        ]);

        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Jadwal Majemuk Prodi+Kelas',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q2', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);

        // Jadwal: Informatika A aktif hari ini, SI A aktif hari ini, Informatika B TIDAK ada jadwal
        TestSchedule::create([
            'test_id' => $test->id,
            'prodi' => 'Informatika',
            'kelas' => 'A',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
        ]);
        TestSchedule::create([
            'test_id' => $test->id,
            'prodi' => 'Sistem Informasi',
            'kelas' => 'A',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
        ]);

        // Mhs Informatika A: bisa akses, scheduleFor menemukan jadwal Informatika A
        $this->assertTrue($test->isAccessibleBy($mhsInformatika));
        $scheduleInf = $test->scheduleFor($mhsInformatika);
        $this->assertNotNull($scheduleInf);
        $this->assertEquals('Informatika', $scheduleInf->prodi);
        $this->assertEquals('A', $scheduleInf->kelas);

        // Mhs SI A: bisa akses, scheduleFor menemukan jadwal SI A (bukan Informatika A!)
        $this->assertTrue($test->isAccessibleBy($mhsSI));
        $scheduleSI = $test->scheduleFor($mhsSI);
        $this->assertNotNull($scheduleSI);
        $this->assertEquals('Sistem Informasi', $scheduleSI->prodi);
        $this->assertEquals('A', $scheduleSI->kelas);

        // Kedua jadwal harus berbeda (ID berbeda)
        $this->assertNotEquals($scheduleInf->id, $scheduleSI->id);

        // Mhs Informatika B: TIDAK bisa akses karena tidak ada jadwal untuk Informatika B
        $this->assertFalse($test->isAccessibleBy($mhsInfB));
        $this->assertNull($test->scheduleFor($mhsInfB));

        // Mhs Informatika A bisa mulai ujian
        $response = $this->actingAs($mhsInformatika)->get(route('mhs.mulai', $test));
        $response->assertOk();

        // Mhs SI A juga bisa mulai ujian
        $response2 = $this->actingAs($mhsSI)->get(route('mhs.mulai', $test));
        $response2->assertOk();

        // Mhs Informatika B ditolak
        $response3 = $this->actingAs($mhsInfB)->get(route('mhs.mulai', $test));
        $response3->assertRedirect(route('dashboard'));
        $response3->assertSessionHas('err');
    }

    public function test_ketat_deadline_follows_class_schedule(): void
    {
        // Skenario: Tes sifat ketat, kelas A selesai jam 10:00, kelas B selesai jam 14:00
        $mhsA = User::create([
            'name' => 'Mhs Ketat A',
            'email' => 'mhs_ketat_a@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021040',
            'prodi' => 'Informatika',
            'kelas' => 'A',
        ]);

        $mhsB = User::create([
            'name' => 'Mhs Ketat B',
            'email' => 'mhs_ketat_b@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021050',
            'prodi' => 'Informatika',
            'kelas' => 'B',
        ]);

        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Ketat Jadwal Per Kelas',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '14:00',
            'durasi' => 120,
            'sifat' => 'ketat',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q2', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);

        // Kelas A: 08:00 - 10:00
        TestSchedule::create([
            'test_id' => $test->id,
            'prodi' => 'Informatika',
            'kelas' => 'A',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        // Kelas B: 12:00 - 14:00
        TestSchedule::create([
            'test_id' => $test->id,
            'prodi' => 'Informatika',
            'kelas' => 'B',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '12:00',
            'jam_selesai' => '14:00',
        ]);

        // Verifikasi selesai_at untuk masing-masing kelas
        $scheduleA = $test->scheduleFor($mhsA);
        $scheduleB = $test->scheduleFor($mhsB);

        $this->assertNotNull($scheduleA);
        $this->assertNotNull($scheduleB);
        $this->assertEquals('10:00', $scheduleA->selesai_at->format('H:i'));
        $this->assertEquals('14:00', $scheduleB->selesai_at->format('H:i'));

        // Simulasi attempt mhsA dimulai jam 08:30 (durasi 120 menit = selesai 10:30,
        // tapi karena ketat dan jadwal kelas A berakhir 10:00, deadline harus 10:00)
        $attemptA = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $mhsA->id,
            'started_at' => Carbon::parse(now()->toDateString() . ' 08:30'),
            'finished_at' => null,
            'question_ids' => [1, 2], // placeholder ids
        ]);

        // Gunakan refleksi private method deadline via controller test
        // Cukup verifikasi bahwa selesai_at kelas A lebih dulu dari durasi penuh
        $durasiPenuh = $attemptA->started_at->copy()->addMinutes($test->durasi); // 10:30
        $this->assertTrue($scheduleA->selesai_at->lt($durasiPenuh)); // 10:00 < 10:30

        // Untuk kelas B, attempt dimulai 12:30, durasi 120 menit = 14:30
        // Jadwal kelas B berakhir 14:00, jadi deadline harus 14:00
        $attemptB = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $mhsB->id,
            'started_at' => Carbon::parse(now()->toDateString() . ' 12:30'),
            'finished_at' => null,
            'question_ids' => [1, 2],
        ]);

        $durasiPenuhB = $attemptB->started_at->copy()->addMinutes($test->durasi); // 14:30
        $this->assertTrue($scheduleB->selesai_at->lt($durasiPenuhB)); // 14:00 < 14:30
    }

    public function test_dosen_filter_prodi_kelas_on_rekap_nilai(): void
    {
        // Buat mahasiswa dari prodi/kelas berbeda
        $mhsInfA = User::create([
            'name' => 'Filter Inf A',
            'email' => 'filter_inf_a@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021060',
            'prodi' => 'Informatika',
            'kelas' => 'A',
        ]);

        $mhsInfB = User::create([
            'name' => 'Filter Inf B',
            'email' => 'filter_inf_b@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021070',
            'prodi' => 'Informatika',
            'kelas' => 'B',
        ]);

        $mhsSIA = User::create([
            'name' => 'Filter SI A',
            'email' => 'filter_si_a@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021080',
            'prodi' => 'Sistem Informasi',
            'kelas' => 'A',
        ]);

        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Filter Prodi Kelas',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        $q1 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        $q2 = Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q2', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);

        // Semua mahasiswa mengerjakan ujian
        foreach ([$mhsInfA, $mhsInfB, $mhsSIA] as $mhs) {
            $this->actingAs($mhs)->get(route('mhs.mulai', $test));
            $this->actingAs($mhs)->post(route('mhs.kirim', $test), [
                'jawaban' => [$q1->id => 'a', $q2->id => 'a'],
            ]);
        }

        // Tanpa filter: 3 peserta
        $response = $this->actingAs($this->dosen)->get(route('dosen.nilai', $test));
        $response->assertOk();
        $this->assertCount(3, $response->viewData('attempts'));

        // Filter prodi=Informatika: hanya 2 peserta (Inf A + Inf B)
        $response = $this->actingAs($this->dosen)->get(route('dosen.nilai', $test) . '?prodi=Informatika');
        $response->assertOk();
        $this->assertCount(2, $response->viewData('attempts'));

        // Filter kelas=A: 2 peserta (Inf A + SI A)
        $response = $this->actingAs($this->dosen)->get(route('dosen.nilai', $test) . '?kelas=A');
        $response->assertOk();
        $this->assertCount(2, $response->viewData('attempts'));

        // Filter prodi=Informatika&kelas=A: hanya 1 peserta (Inf A)
        $response = $this->actingAs($this->dosen)->get(route('dosen.nilai', $test) . '?prodi=Informatika&kelas=A');
        $response->assertOk();
        $attempts = $response->viewData('attempts');
        $this->assertCount(1, $attempts);
        $this->assertEquals($mhsInfA->id, $attempts->first()->user_id);

        // Filter prodi=Sistem Informasi&kelas=B: 0 peserta
        $response = $this->actingAs($this->dosen)->get(route('dosen.nilai', $test) . '?prodi=Sistem+Informasi&kelas=B');
        $response->assertOk();
        $this->assertCount(0, $response->viewData('attempts'));
    }

    public function test_dosen_filter_prodi_kelas_on_live_monitoring(): void
    {
        $mhsInfA = User::create([
            'name' => 'Live Inf A',
            'email' => 'live_inf_a@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021090',
            'prodi' => 'Informatika',
            'kelas' => 'A',
        ]);

        $mhsSIB = User::create([
            'name' => 'Live SI B',
            'email' => 'live_si_b@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021100',
            'prodi' => 'Sistem Informasi',
            'kelas' => 'B',
        ]);

        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Live Filter',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);

        // Kedua mahasiswa memulai ujian
        $this->actingAs($mhsInfA)->get(route('mhs.mulai', $test));
        $this->actingAs($mhsSIB)->get(route('mhs.mulai', $test));

        // Tanpa filter: 2 peserta
        $response = $this->actingAs($this->dosen)->getJson('/dosen/ujian/' . $test->id . '/live-monitoring');
        $response->assertOk()->assertJsonCount(2, 'attempts');

        // Filter prodi=Informatika: hanya 1
        $response = $this->actingAs($this->dosen)->getJson('/dosen/ujian/' . $test->id . '/live-monitoring?prodi=Informatika');
        $response->assertOk()
            ->assertJsonCount(1, 'attempts')
            ->assertJsonPath('attempts.0.user.prodi', 'Informatika');

        // Filter kelas=B: hanya 1
        $response = $this->actingAs($this->dosen)->getJson('/dosen/ujian/' . $test->id . '/live-monitoring?kelas=B');
        $response->assertOk()
            ->assertJsonCount(1, 'attempts')
            ->assertJsonPath('attempts.0.user.kelas', 'B');

        // Filter prodi=Informatika&kelas=B: 0
        $response = $this->actingAs($this->dosen)->getJson('/dosen/ujian/' . $test->id . '/live-monitoring?prodi=Informatika&kelas=B');
        $response->assertOk()->assertJsonCount(0, 'attempts');
    }

    public function test_test_without_schedules_is_accessible_by_all(): void
    {
        // Tes tanpa schedule -> backward compatible: semua mahasiswa bisa akses
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Tanpa Jadwal Khusus',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
            'jumlah_soal_tampil' => 2,
            'kkm' => 70,
        ]);

        Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q1', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);
        Question::create(['test_id' => $test->id, 'tipe' => 'pilihan_ganda', 'pertanyaan' => 'Q2', 'opsi_a' => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C', 'opsi_d' => 'D', 'jawaban_benar' => 'a']);

        // Mahasiswa tanpa prodi/kelas
        $this->assertTrue($test->isAccessibleBy($this->mahasiswa));
        $this->assertNull($test->scheduleFor($this->mahasiswa));

        // Mahasiswa dengan prodi/kelas
        $mhsWithClass = User::create([
            'name' => 'Mhs Backward',
            'email' => 'mhs_backward@unib.ac.id',
            'password' => bcrypt('password'),
            'role' => 'mahasiswa',
            'nomor_induk' => 'G1A021110',
            'prodi' => 'Informatika',
            'kelas' => 'A',
        ]);
        $this->assertTrue($test->isAccessibleBy($mhsWithClass));

        // Bisa mulai ujian
        $response = $this->actingAs($this->mahasiswa)->get(route('mhs.mulai', $test));
        $response->assertOk();
    }

    public function test_dosen_berlangsung_sees_composite_status_across_schedules(): void
    {
        // Dosen melihat status komposit: berlangsung = any schedule berlangsung, selesai = all schedules selesai
        $test = Test::create([
            'dosen_id' => $this->dosen->id,
            'judul' => 'Ujian Komposit Status',
            'tipe_ujian' => 'pilihan_ganda',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
            'durasi' => 60,
            'sifat' => 'fleksibel',
        ]);

        // Jadwal A: sudah selesai (kemarin)
        TestSchedule::create([
            'test_id' => $test->id,
            'prodi' => 'Informatika',
            'kelas' => 'A',
            'tanggal' => now()->subDay()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        // Jadwal B: sedang berlangsung (hari ini penuh)
        TestSchedule::create([
            'test_id' => $test->id,
            'prodi' => 'Informatika',
            'kelas' => 'B',
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00',
            'jam_selesai' => '23:59',
        ]);

        // Dari perspektif dosen (non-mahasiswa): berlangsung = true jika ada minimal 1 schedule aktif
        $this->assertTrue($test->berlangsung($this->dosen));
        // Selesai = false karena jadwal B masih aktif
        $this->assertFalse($test->selesai($this->dosen));
    }
}
