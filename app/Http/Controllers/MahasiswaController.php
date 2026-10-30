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

    public function kirim(Request $r, Test $test)
    {
        $attempt = Attempt::where('test_id', $test->id)->where('user_id', auth()->id())->firstOrFail();
        if ($attempt->finished_at) return redirect()->route('mhs.hasil', $test);

        $validated = $r->validate([
            'jawaban' => 'nullable|array',
            'jawaban.*' => 'nullable|in:a,b,c,d',
            'jawaban_essai' => 'nullable|array',
            'jawaban_essai.*' => 'nullable|string|max:20000',
        ]);
        $jawab = $validated['jawaban'] ?? [];
        $jawabanEssai = $validated['jawaban_essai'] ?? [];
        $questions = $test->questions;
        $pilihanGanda = $questions->where('tipe', 'pilihan_ganda');
        $benar = 0;
        foreach ($questions as $q) {
            $j = $q->tipe === 'pilihan_ganda' ? ($jawab[$q->id] ?? null) : null;
            $essay = $q->tipe === 'essai' ? ($jawabanEssai[$q->id] ?? null) : null;
            $ok = $q->tipe === 'pilihan_ganda' && $j === $q->jawaban_benar;
            $benar += $ok ? 1 : 0;
            Answer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $q->id,
                'jawaban' => $j,
                'jawaban_essai' => $essay,
                'benar' => $ok,
            ]);
        }
        $attempt->update([
            'finished_at' => now()->min($this->deadline($attempt, $test)),
            'nilai' => $pilihanGanda->count() ? round($benar / $pilihanGanda->count() * 100, 2) : null,
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
