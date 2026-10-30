<?php
namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Test;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MahasiswaController extends Controller
{
    public function index()
    {
        $tests = Test::orderByDesc('tanggal')->get();
        $attempts = Attempt::where('user_id', auth()->id())->get()->keyBy('test_id');
        $aktif = $tests->filter(fn ($t) => !$t->selesai() && !optional($attempts->get($t->id))->finished_at);
        $riwayat = $tests->reject(fn ($t) => $aktif->contains('id', $t->id));
        return view('mahasiswa.index', compact('aktif', 'riwayat', 'attempts'));
    }

    public function mulai(Test $test)
    {
        $attempt = Attempt::where('test_id', $test->id)->where('user_id', auth()->id())->first();
        if ($attempt?->finished_at) return redirect()->route('mhs.hasil', $test);
        if (!$attempt) {
            if (!$test->berlangsung()) return back()->with('err', 'Tes belum dibuka atau sudah berakhir.');
            $attempt = Attempt::create(['test_id' => $test->id, 'user_id' => auth()->id(), 'started_at' => now()]);
        }
        $deadline = $this->deadline($attempt, $test);

        return view('mahasiswa.ujian', [
            'test' => $test, 'questions' => $test->questions()->orderBy('id')->get(),
            'sisaDetik' => max(0, now()->diffInSeconds($deadline, false)),
        ]);
    }

    public function autosave(Request $r, Test $test)
    {
        $attempt = Attempt::where('test_id', $test->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$attempt) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi ujian tidak ditemukan. Silakan mulai ujian terlebih dahulu.',
            ], 404);
        }

        if ($attempt->finished_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ujian telah selesai diserahkan.',
            ], 403);
        }

        $deadline = $this->deadline($attempt, $test);
        if (now()->gt($deadline)) {
            $attempt->update(['finished_at' => $deadline]);
            return response()->json([
                'status' => 'error',
                'message' => 'Waktu pengerjaan ujian telah berakhir.',
            ], 403);
        }

        $validated = $r->validate([
            'question_id' => 'required|exists:questions,id',
            'jawaban_essai' => 'nullable|string|max:65535',
        ]);

        $question = $test->questions()->where('id', $validated['question_id'])->firstOrFail();

        $answer = Answer::updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'jawaban_essai' => $validated['jawaban_essai'] ?? '',
                'benar' => false,
            ]
        );

        $attempt->touch();

        return response()->json([
            'status' => 'saved',
            'updated_at' => $answer->updated_at->toIso8601String(),
        ]);
    }

    public function kirim(Request $r, Test $test)
    {
        $attempt = Attempt::where('test_id', $test->id)->where('user_id', auth()->id())->firstOrFail();
        if ($attempt->finished_at) return redirect()->route('mhs.hasil', $test);

        $validated = $r->validate([
            'jawaban' => 'nullable|array',
            'jawaban.*' => 'nullable|in:a,b,c,d',
            'jawaban_essai' => 'nullable|array',
            'jawaban_essai.*' => 'nullable|string|max:65535',
        ]);
        $jawab = $validated['jawaban'] ?? [];
        $jawabanEssai = $validated['jawaban_essai'] ?? [];
        $questions = $test->questions;
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
        // Jika pilihan ganda, nilai dihitung otomatis
        if ($test->tipe_ujian === 'essai') {
            $nilai = null;
        } else {
            $nilai = $pilihanGanda->count() ? round($benar / $pilihanGanda->count() * 100, 2) : 0;
        }

        $attempt->update([
            'finished_at' => now()->min($this->deadline($attempt, $test)),
            'nilai' => $nilai,
        ]);
        return redirect()->route('mhs.hasil', $test);
    }

    public function hasil(Test $test)
    {
        $attempt = Attempt::with('answers.question')->where('test_id', $test->id)->where('user_id', auth()->id())->firstOrFail();
        $selesaiEfektif = ($attempt->finished_at ?? now())->min($this->deadline($attempt, $test));
        $durasiDetik = max(0, $attempt->started_at->diffInSeconds($selesaiEfektif, false));
        return view('mahasiswa.hasil', compact('test', 'attempt', 'durasiDetik'));
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
