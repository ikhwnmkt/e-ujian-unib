<?php
namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Http\Request;

class DosenController extends Controller
{
    private function own(Test $test): Test
    {
        abort_unless($test->dosen_id === auth()->id(), 403);
        return $test;
    }

    private function rules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'materi' => 'nullable|string|max:255',
            'tipe_ujian' => 'nullable|in:pilihan_ganda,essai',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'durasi' => 'required|integer|min:1|max:600',
            'sifat' => 'required|in:fleksibel,ketat',
            'jumlah_soal_tampil' => 'nullable|integer|min:1|max:1000',
            'kkm' => 'nullable|integer|min:0|max:100',
        ];
    }

    public function index()
    {
        $tests = auth()->user()->tests()->orderByDesc('tanggal')->get();
        return view('dosen.index', [
            'aktif' => $tests->filter(fn ($t) => !$t->selesai()),
            'riwayat' => $tests->filter(fn ($t) => $t->selesai()),
        ]);
    }

    public function create()
    {
        return view('dosen.form', [
            'test' => new Test([
                'durasi' => 90,
                'sifat' => 'fleksibel',
                'tipe_ujian' => 'pilihan_ganda',
                'jumlah_soal_tampil' => 30,
                'kkm' => 70,
            ]),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());
        $data['tipe_ujian'] = $data['tipe_ujian'] ?? 'pilihan_ganda';
        $data['jumlah_soal_tampil'] = $data['jumlah_soal_tampil'] ?? 30;
        $data['kkm'] = $data['kkm'] ?? 70;
        $test = auth()->user()->tests()->create($data);
        return redirect()->route('dosen.soal', $test)->with('ok', 'Tes dibuat. Silakan tambahkan soal.');
    }

    public function edit(Test $test) { return view('dosen.form', ['test' => $this->own($test)]); }

    public function update(Request $r, Test $test)
    {
        $data = $r->validate($this->rules());
        if (empty($data['tipe_ujian'])) {
            unset($data['tipe_ujian']);
        }
        $this->own($test)->update($data);
        return redirect()->route('dosen.index')->with('ok', 'Tes diperbarui.');
    }

    public function destroy(Test $test)
    {
        $this->own($test)->delete();
        return back()->with('ok', 'Tes dihapus.');
    }

    public function soal(Test $test)
    {
        return view('dosen.soal', ['test' => $this->own($test)->load('questions')]);
    }

    public function soalStore(Request $r, Test $test)
    {
        $test = $this->own($test);
        $defaultTipe = $test->tipe_ujian ?? 'pilihan_ganda';
        $data = $r->validate([
            'tipe' => 'nullable|in:pilihan_ganda,essai',
            'pertanyaan' => 'required|string',
            'media_type' => 'nullable|in:gambar,link',
            'media_url' => 'nullable|url|max:2048|required_with:media_type',
        ]);
        $data['tipe'] = $data['tipe'] ?? $defaultTipe;
        if ($data['tipe'] === 'pilihan_ganda') {
            $data = array_merge($data, $r->validate([
                'opsi_a' => 'required|string|max:255',
                'opsi_b' => 'required|string|max:255',
                'opsi_c' => 'required|string|max:255',
                'opsi_d' => 'required|string|max:255',
                'jawaban_benar' => 'required|in:a,b,c,d',
            ]));
        } else {
            $data = array_merge($data, [
                'opsi_a' => null, 'opsi_b' => null, 'opsi_c' => null, 'opsi_d' => null,
                'jawaban_benar' => null,
            ]);
        }
        $test->questions()->create($data);
        return back()->with('ok', 'Soal ditambahkan.');
    }

    public function soalDestroy(Test $test, Question $question)
    {
        abort_unless($question->test_id === $this->own($test)->id, 404);
        $question->delete();
        return back()->with('ok', 'Soal dihapus.');
    }

    public function nilai(Test $test)
    {
        $test = $this->own($test);
        return view('dosen.nilai', [
            'test' => $test,
            'attempts' => $test->getPrimaryAttempts(),
        ]);
    }

    public function updateNilai(Request $r, Test $test, Attempt $attempt)
    {
        $test = $this->own($test);
        abort_unless($attempt->test_id === $test->id, 404);

        if ($test->tipe_ujian === 'pilihan_ganda' || empty($test->tipe_ujian)) {
            if ($r->expectsJson() || $r->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Penilaian manual hanya diizinkan untuk ujian bertipe essai.',
                ], 422);
            }
            return back()->with('err', 'Penilaian manual hanya diizinkan untuk ujian bertipe essai.');
        }

        $data = $r->validate([
            'nilai' => 'required|numeric|min:0|max:100',
        ]);

        $attempt->update(['nilai' => $data['nilai']]);

        if ($r->expectsJson() || $r->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Nilai berhasil disimpan.',
                'attempt_id' => $attempt->id,
                'nilai' => (float) $attempt->nilai,
            ]);
        }

        return back()->with('ok', 'Nilai mahasiswa berhasil diperbarui.');
    }

    public function liveMonitoring(Test $test)
    {
        $test = $this->own($test);

        $allAttempts = $test->attempts()
            ->with([
                'user:id,name,nomor_induk,avatar',
                'answers' => function ($q) {
                    $q->select('id', 'attempt_id', 'question_id', 'jawaban_essai', 'updated_at')
                      ->orderBy('question_id');
                },
                'answers.question:id,pertanyaan,tipe',
            ])
            ->orderBy('id')
            ->get();

        $attempts = $allAttempts->groupBy('user_id')->map(function ($userAttempts) {
            $attemptCount = $userAttempts->count();
            // Ambil attempt paling baru (latest id)
            $attempt = $userAttempts->sortByDesc('id')->first();

            $lastUpdatedAnswer = $attempt->answers->sortByDesc('updated_at')->first();
            $lastActivity = $lastUpdatedAnswer?->updated_at ?? $attempt->updated_at;

            return [
                'attempt_id' => $attempt->id,
                'attempt_count' => $attemptCount,
                'user' => [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'nomor_induk' => $attempt->user->nomor_induk,
                    'avatar' => $attempt->user->avatar,
                ],
                'started_at' => $attempt->started_at?->toIso8601String(),
                'finished_at' => $attempt->finished_at?->toIso8601String(),
                'is_finished' => !is_null($attempt->finished_at),
                'nilai' => $attempt->nilai !== null ? (float) $attempt->nilai : null,
                'is_passed' => $attempt->isPassed(),
                'updated_at' => $lastActivity?->toIso8601String(),
                'answers' => $attempt->answers->map(function ($ans) {
                    return [
                        'id' => $ans->id,
                        'question_id' => $ans->question_id,
                        'pertanyaan' => $ans->question?->pertanyaan,
                        'tipe' => $ans->question?->tipe,
                        'jawaban_essai' => $ans->jawaban_essai,
                        'updated_at' => $ans->updated_at?->toIso8601String(),
                    ];
                })->values(),
            ];
        })
        ->sortByDesc('updated_at')
        ->values();

        return response()->json([
            'status' => 'success',
            'test' => [
                'id' => $test->id,
                'judul' => $test->judul,
                'materi' => $test->materi,
                'tipe_ujian' => $test->tipe_ujian,
                'kkm' => $test->kkm ?? 70,
                'jumlah_soal_tampil' => $test->jumlah_soal_tampil ?? 30,
                'peserta_count' => $test->peserta_count,
            ],
            'server_time' => now()->toIso8601String(),
            'attempts' => $attempts,
        ]);
    }
}
