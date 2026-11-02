<?php
namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'mata_kuliah' => 'nullable|string|max:255',
            'materi' => 'nullable|string|max:255',
            'tipe_ujian' => 'nullable|in:pilihan_ganda,essai',
            'tanggal' => 'required_without:schedules|nullable|date',
            'jam_mulai' => 'required_without:schedules|nullable|date_format:H:i',
            'jam_selesai' => 'required_without:schedules|nullable|date_format:H:i|after:jam_mulai',
            'durasi' => 'required|integer|min:1|max:600',
            'sifat' => 'required|in:fleksibel,ketat',
            'jumlah_soal_tampil' => 'nullable|integer|min:1|max:1000',
            'kkm' => 'nullable|integer|min:0|max:100',
            'schedules' => 'nullable|array',
            'schedules.*.prodi' => 'nullable|string|max:255',
            'schedules.*.kelas' => 'required_with:schedules|string|max:10',
            'schedules.*.tanggal' => 'required_with:schedules|date',
            'schedules.*.jam_mulai' => 'required_with:schedules|date_format:H:i',
            'schedules.*.jam_selesai' => 'required_with:schedules|date_format:H:i|after:schedules.*.jam_mulai',
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
        $schedules = $data['schedules'] ?? null;
        unset($data['schedules']);

        $data['tipe_ujian'] = $data['tipe_ujian'] ?? 'pilihan_ganda';
        $data['jumlah_soal_tampil'] = $data['jumlah_soal_tampil'] ?? 30;
        $data['kkm'] = $data['kkm'] ?? 70;

        // Jika schedules diberikan, isi kolom tanggal/jam utama dari jadwal pertama sebagai fallback
        if (!empty($schedules)) {
            $first = $schedules[0];
            $data['tanggal'] = $data['tanggal'] ?? $first['tanggal'];
            $data['jam_mulai'] = $data['jam_mulai'] ?? $first['jam_mulai'];
            $data['jam_selesai'] = $data['jam_selesai'] ?? $first['jam_selesai'];
        }

        $test = auth()->user()->tests()->create($data);
        $this->syncSchedules($test, $schedules);

        return redirect()->route('dosen.soal', $test)->with('ok', 'Tes dibuat. Silakan tambahkan soal.');
    }

    public function edit(Test $test) { return view('dosen.form', ['test' => $this->own($test)->load('schedules')]); }

    public function update(Request $r, Test $test)
    {
        $data = $r->validate($this->rules());
        $schedules = $data['schedules'] ?? null;
        unset($data['schedules']);

        if (empty($data['tipe_ujian'])) {
            unset($data['tipe_ujian']);
        }

        // Jika schedules diberikan, isi kolom tanggal/jam utama dari jadwal pertama sebagai fallback
        if (!empty($schedules)) {
            $first = $schedules[0];
            $data['tanggal'] = $data['tanggal'] ?? $first['tanggal'];
            $data['jam_mulai'] = $data['jam_mulai'] ?? $first['jam_mulai'];
            $data['jam_selesai'] = $data['jam_selesai'] ?? $first['jam_selesai'];
        }

        $this->own($test)->update($data);
        $this->syncSchedules($test, $schedules);

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
            'media_url' => 'nullable|required_if:media_type,link|url|max:2048',
            'media_file' => 'nullable|required_if:media_type,gambar|image|max:5120',
        ]);

        $data['tipe'] = $data['tipe'] ?? $defaultTipe;

        if (($data['media_type'] ?? null) === 'gambar') {
            $data['media_url'] = Storage::disk('public')->url(
                $r->file('media_file')->store('question-media', 'public')
            );
        } elseif (($data['media_type'] ?? null) === 'link') {
            $data['media_url'] = $data['media_url'] ?? null;
        }

unset($data['media_file']);

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
        if ($question->media_type === 'gambar' && str_starts_with($question->media_url ?? '', '/storage/')) {
            Storage::disk('public')->delete(substr($question->media_url, strlen('/storage/')));
        }
        $question->delete();
        return back()->with('ok', 'Soal dihapus.');
    }

    public function nilai(Test $test)
    {
        $test = $this->own($test);
        $attempts = $test->getPrimaryAttempts();

        // Filter berdasarkan query ?prodi=...&kelas=...
        if (request('prodi')) {
            $attempts = $attempts->filter(fn ($a) => strcasecmp($a->user->prodi ?? '', request('prodi')) === 0)->values();
        }
        if (request('kelas')) {
            $attempts = $attempts->filter(fn ($a) => strcasecmp($a->user->kelas ?? '', request('kelas')) === 0)->values();
        }

        return view('dosen.nilai', [
            'test' => $test,
            'attempts' => $attempts,
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

    public function liveMonitoring(Request $request, Test $test)
    {
        $test = $this->own($test);

        // Jika request dari browser biasa (HTML), sajikan view monitoring
        if (!$request->wantsJson() && !$request->ajax()) {
            return view('dosen.live-monitoring', compact('test'));
        }

        $allAttempts = $test->attempts()
            ->with([
                'user:id,name,nomor_induk,avatar,prodi,kelas',
                'answers' => function ($q) {
                    $q->select('id', 'attempt_id', 'question_id', 'jawaban', 'jawaban_essai', 'updated_at')
                      ->orderBy('question_id');
                },
                'answers.question:id,pertanyaan,tipe',
            ])
            ->orderBy('id')
            ->get();

        $attempts = $allAttempts->groupBy('user_id')->map(function ($userAttempts) use ($test) {
            $attemptCount = $userAttempts->count();
            // Ambil attempt paling baru (latest id)
            $attempt = $userAttempts->sortByDesc('id')->first();

            $lastUpdatedAnswer = $attempt->answers->sortByDesc('updated_at')->first();
            $lastActivity = $lastUpdatedAnswer?->updated_at ?? $attempt->updated_at;

            $isExpired = false;
            if ($attempt->started_at && $attempt->finished_at === null) {
                $deadline = $attempt->started_at->copy()->addMinutes($test->durasi);
                if ($test->sifat === 'ketat') {
                    $deadline = min($deadline, $test->selesai_at);
                }
                $isExpired = now()->gt($deadline);
            }

            return [
                'attempt_id' => $attempt->id,
                'attempt_count' => $attemptCount,
                'user' => [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'nomor_induk' => $attempt->user->nomor_induk,
                    'avatar' => $attempt->user->avatar,
                    'prodi' => $attempt->user->prodi,
                    'kelas' => $attempt->user->kelas,
                    'prodi_kelas' => $attempt->user->prodi_kelas,
                ],
                'started_at' => $attempt->started_at?->toIso8601String(),
                'finished_at' => $attempt->finished_at?->toIso8601String(),
                'is_finished' => !is_null($attempt->finished_at),
                'is_expired' => $isExpired,
                'nilai' => $attempt->nilai !== null ? (float) $attempt->nilai : null,
                'is_passed' => $attempt->isPassed(),
                'updated_at' => $lastActivity?->toIso8601String(),
                'answers' => $attempt->answers->map(function ($ans) {
                    return [
                        'id' => $ans->id,
                        'question_id' => $ans->question_id,
                        'pertanyaan' => $ans->question?->pertanyaan,
                        'tipe' => $ans->question?->tipe,
                        'pilihan' => $ans->pilihan,
                        'jawaban_essai' => $ans->jawaban_essai,
                        'updated_at' => $ans->updated_at?->toIso8601String(),
                    ];
                })->values(),
            ];
        })
        ->sortByDesc('updated_at')
        ->values();

        // Filter berdasarkan query ?prodi=...&kelas=...
        if ($request->query('prodi')) {
            $attempts = $attempts->filter(fn ($a) => strcasecmp($a['user']['prodi'] ?? '', $request->query('prodi')) === 0)->values();
        }
        if ($request->query('kelas')) {
            $attempts = $attempts->filter(fn ($a) => strcasecmp($a['user']['kelas'] ?? '', $request->query('kelas')) === 0)->values();
        }

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

    private function syncSchedules(Test $test, ?array $schedules): void
    {
        if ($schedules === null) {
            return;
        }

        $test->schedules()->delete();

        foreach ($schedules as $s) {
            $test->schedules()->create([
                'prodi' => $s['prodi'] ?? null,
                'kelas' => $s['kelas'],
                'tanggal' => $s['tanggal'],
                'jam_mulai' => $s['jam_mulai'],
                'jam_selesai' => $s['jam_selesai'],
            ]);
        }
    }
}
