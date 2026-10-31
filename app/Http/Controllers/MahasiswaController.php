<?php
namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Test;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MahasiswaController extends Controller
{
    public function index()
    {
        $tests = Test::orderByDesc('tanggal')->get();
        $attempts = Attempt::where('user_id', auth()->id())
            ->orderBy('id')
            ->get()
            ->groupBy('test_id');

        $latestAttempts = $attempts->map(fn ($list) => $list->last());

        $aktif = $tests->filter(function ($t) use ($latestAttempts) {
            if ($t->selesai()) return false;
            $att = $latestAttempts->get($t->id);
            if (!$att || !$att->finished_at) return true;
            return $att->canRemedial();
        });

        $riwayat = $tests->reject(fn ($t) => $aktif->contains('id', $t->id));

        return view('mahasiswa.index', [
            'aktif' => $aktif,
            'riwayat' => $riwayat,
            'attempts' => $latestAttempts,
        ]);
    }

    public function mulai(Test $test)
    {
        $userId = auth()->id();

        $result = DB::transaction(function () use ($test, $userId) {
            // Lock baris user untuk serialisasi per mahasiswa (mencegah double attempt simultan)
            User::where('id', $userId)->lockForUpdate()->first();

            $attempt = Attempt::where('test_id', $test->id)
                ->where('user_id', $userId)
                ->latest('id')
                ->first();

            // Tugas 4: Jika attempt belum selesai tapi deadline sudah lewat, auto-finalize
            if ($attempt && $attempt->finished_at === null) {
                $deadline = $this->deadline($attempt, $test);
                if (now()->gt($deadline)) {
                    $this->finalizeAttempt($attempt, $test, $deadline);
                    $attempt->refresh();
                }
            }

            // Jika attempt sudah selesai (atau baru saja di-finalize karena expired)
            if ($attempt && $attempt->finished_at !== null) {
                $kkm = $test->kkm ?? 70;
                $canRemedial = ($test->tipe_ujian === 'pilihan_ganda' || empty($test->tipe_ujian))
                    && $attempt->nilai !== null
                    && (float) $attempt->nilai < (float) $kkm
                    && $test->berlangsung();

                if (!$canRemedial) {
                    return redirect()->route('mhs.hasil', $test);
                }

                // Memenuhi syarat remedial: siapkan pembuatan attempt baru
                $attempt = null;
            }

            // Jika belum ada attempt atau sedang memulai remedial baru
            if (!$attempt) {
                if (!$test->berlangsung()) {
                    return back()->with('err', 'Tes belum dibuka atau sudah berakhir.');
                }

                // Tugas 2: Guard bank soal kosong
                $isPilgan = ($test->tipe_ujian === 'pilihan_ganda' || empty($test->tipe_ujian));
                $availableCount = $isPilgan
                    ? $test->questions()->where('tipe', 'pilihan_ganda')->count()
                    : $test->questions()->count();

                if ($availableCount === 0) {
                    return redirect()->route('dashboard')->with('err', 'Soal ujian belum tersedia. Silakan hubungi dosen pengampu.');
                }

                // Pastikan tidak ada attempt aktif yang menggantung
                $existingActive = Attempt::where('test_id', $test->id)
                    ->where('user_id', $userId)
                    ->whereNull('finished_at')
                    ->first();

                if ($existingActive) {
                    $attempt = $existingActive;
                } else {
                    $limit = min($availableCount, $test->jumlah_soal_tampil ?? 30);
                    $questionIds = $isPilgan
                        ? $test->questions()->where('tipe', 'pilihan_ganda')->inRandomOrder()->limit($limit)->pluck('id')->toArray()
                        : $test->questions()->inRandomOrder()->limit($limit)->pluck('id')->toArray();

                    $attempt = Attempt::create([
                        'test_id' => $test->id,
                        'user_id' => $userId,
                        'started_at' => now(),
                        'question_ids' => $questionIds,
                    ]);
                }
            }

            return $attempt;
        });

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        $attempt = $result;
        $deadline = $this->deadline($attempt, $test);

        if (!empty($attempt->question_ids) && is_array($attempt->question_ids)) {
            $questions = $test->questions()
                ->whereIn('id', $attempt->question_ids)
                ->get()
                ->sortBy(fn ($q) => array_search($q->id, $attempt->question_ids))
                ->values();
        } else {
            $questions = $test->questions()->orderBy('id')->get();
        }

        return view('mahasiswa.ujian', [
            'test' => $test,
            'questions' => $questions,
            'sisaDetik' => max(0, now()->diffInSeconds($deadline, false)),
        ]);
    }

    public function autosave(Request $r, Test $test)
    {
        $attempt = Attempt::where('test_id', $test->id)
            ->where('user_id', auth()->id())
            ->latest('id')
            ->first();

        if (!$attempt) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi ujian tidak ditemukan. Silakan mulai ujian terlebih dahulu.',
            ], 404);
        }

        $deadline = $this->deadline($attempt, $test);

        if ($attempt->finished_at !== null || now()->gt($deadline)) {
            if ($attempt->finished_at === null) {
                $this->finalizeAttempt($attempt, $test, $deadline);
            }

            return response()->json([
                'expired' => true,
                'message' => 'Waktu ujian telah habis.',
                'redirect' => route('mhs.hasil', $test),
            ], 409);
        }

        $validated = $r->validate([
            'question_id' => 'required|exists:questions,id',
            'pilihan' => 'nullable|in:a,b,c,d',
            'jawaban' => 'nullable|in:a,b,c,d',
            'jawaban_essai' => 'nullable|string|max:65535',
        ]);

        $question = $test->questions()->where('id', $validated['question_id'])->firstOrFail();

        $pilihan = $validated['pilihan'] ?? $validated['jawaban'] ?? null;
        $isPilgan = ($test->tipe_ujian === 'pilihan_ganda' || empty($test->tipe_ujian));

        $attributes = [];

        if ($isPilgan && $pilihan !== null) {
            $isBenar = (strtolower($pilihan) === strtolower($question->jawaban_benar));
            $attributes['jawaban'] = $pilihan;
            $attributes['benar'] = $isBenar;
        } elseif (!$isPilgan && array_key_exists('jawaban_essai', $validated)) {
            $attributes['jawaban_essai'] = $validated['jawaban_essai'] ?? '';
            $attributes['benar'] = false;
        } else {
            if ($pilihan !== null) {
                $attributes['jawaban'] = $pilihan;
                $attributes['benar'] = (strtolower($pilihan) === strtolower($question->jawaban_benar));
            }
            if (array_key_exists('jawaban_essai', $validated)) {
                $attributes['jawaban_essai'] = $validated['jawaban_essai'] ?? '';
            }
        }

        $answer = Answer::updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            $attributes
        );

        $attempt->touch();

        return response()->json([
            'status' => 'saved',
            'updated_at' => $answer->updated_at->toIso8601String(),
        ]);
    }

    public function remedial(Request $r, Test $test)
    {
        return redirect()->route('mhs.mulai', $test);
    }

    public function kirim(Request $r, Test $test)
    {
        $attempt = Attempt::where('test_id', $test->id)
            ->where('user_id', auth()->id())
            ->latest('id')
            ->firstOrFail();

        // Idempotensi kirim: jika attempt sudah selesai, jangan dinilai ulang dan jangan menimpa nilai
        if ($attempt->finished_at !== null) {
            return redirect()->route('mhs.hasil', $test);
        }

        $deadline = $this->deadline($attempt, $test);
        $graceSeconds = (int) config('exam.submit_grace_seconds', 60);
        $deadlineWithGrace = $deadline->copy()->addSeconds($graceSeconds);

        if (now()->gt($deadlineWithGrace)) {
            // Lewat batas toleransi (grace period): abaikan payload baru, finalize dari DB
            $this->finalizeAttempt($attempt, $test, $deadline);
            return redirect()->route('mhs.hasil', $test)->with('err', 'Waktu ujian telah habis.');
        }

        $validated = $r->validate([
            'jawaban' => 'nullable|array',
            'jawaban.*' => 'nullable|in:a,b,c,d',
            'jawaban_essai' => 'nullable|array',
            'jawaban_essai.*' => 'nullable|string|max:65535',
        ]);
        $jawab = $validated['jawaban'] ?? [];
        $jawabanEssai = $validated['jawaban_essai'] ?? [];

        if (!empty($attempt->question_ids) && is_array($attempt->question_ids)) {
            // Tugas 3: Ambil hanya soal yang MASIH ADA di database
            $questions = $test->questions()
                ->whereIn('id', $attempt->question_ids)
                ->get()
                ->sortBy(fn ($q) => array_search($q->id, $attempt->question_ids))
                ->values();
        } else {
            $questions = $test->questions;
        }

        $pilihanGanda = $questions->where('tipe', 'pilihan_ganda');
        $benar = 0;

        foreach ($questions as $q) {
            if ($q->tipe === 'pilihan_ganda') {
                $j = $jawab[$q->id] ?? null;
                $ok = ($j !== null && $j === $q->jawaban_benar);
                $benar += $ok ? 1 : 0;

                Answer::updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'question_id' => $q->id,
                    ],
                    [
                        'jawaban' => $j,
                        'benar' => $ok,
                    ]
                );
            } else {
                $essay = $jawabanEssai[$q->id] ?? null;
                $existing = Answer::where('attempt_id', $attempt->id)->where('question_id', $q->id)->first();

                Answer::updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'question_id' => $q->id,
                    ],
                    [
                        'jawaban_essai' => $essay !== null ? $essay : ($existing?->jawaban_essai ?? ''),
                        'benar' => false,
                    ]
                );
            }
        }

        // Jika tipe ujian adalah essai, nilai bernilai null (status: Menunggu Penilaian)
        // Jika pilihan ganda, pembagi = jumlah soal yang MASIH ADA di database
        if ($test->tipe_ujian === 'essai') {
            $nilai = null;
        } else {
            // Tugas 3: pembagi = jumlah soal pilgan yang MASIH ADA di database
            // Jika 0, nilai = 0 tanpa division by zero error
            $pembagi = $pilihanGanda->count();
            $nilai = $pembagi > 0 ? round($benar / $pembagi * 100, 2) : 0;
        }

        $attempt->update([
            'finished_at' => now()->min($this->deadline($attempt, $test)),
            'nilai' => $nilai,
        ]);

        return redirect()->route('mhs.hasil', $test);
    }

    public function hasil(Test $test)
    {
        $attempt = Attempt::with('answers.question')
            ->where('test_id', $test->id)
            ->where('user_id', auth()->id())
            ->latest('id')
            ->firstOrFail();

        $selesaiEfektif = ($attempt->finished_at ?? now())->min($this->deadline($attempt, $test));
        $durasiDetik = max(0, $attempt->started_at->diffInSeconds($selesaiEfektif, false));

        $allAttempts = Attempt::where('test_id', $test->id)
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->get();

        return view('mahasiswa.hasil', compact('test', 'attempt', 'durasiDetik', 'allAttempts'));
    }

    private function finalizeAttempt(Attempt $attempt, Test $test, Carbon $deadline): void
    {
        if ($test->tipe_ujian === 'essai') {
            $nilai = null;
        } else {
            if (!empty($attempt->question_ids) && is_array($attempt->question_ids)) {
                $questions = $test->questions()->whereIn('id', $attempt->question_ids)->get();
            } else {
                $questions = $test->questions;
            }

            $pilihanGanda = $questions->where('tipe', 'pilihan_ganda');
            $pembagi = $pilihanGanda->count();
            $answers = $attempt->answers()->get()->keyBy('question_id');
            $benar = 0;

            foreach ($pilihanGanda as $q) {
                $ans = $answers->get($q->id);
                if ($ans && $ans->jawaban !== null && $ans->jawaban === $q->jawaban_benar) {
                    $benar++;
                }
            }

            $nilai = $pembagi > 0 ? round($benar / $pembagi * 100, 2) : 0;
        }

        $attempt->update([
            'finished_at' => $deadline,
            'nilai' => $nilai,
        ]);
    }

    private function deadline(Attempt $attempt, Test $test): Carbon
    {
        $deadline = $attempt->started_at->copy()->addMinutes($test->durasi);
        if ($test->sifat === 'ketat' && $test->selesai_at->lt($deadline)) {
            $deadline = $test->selesai_at;
        }
        return $deadline;
    }
}
