<?php
namespace App\Http\Controllers;

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
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'durasi' => 'required|integer|min:1|max:600',
            'sifat' => 'required|in:fleksibel,ketat',
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

    public function create() { return view('dosen.form', ['test' => new Test(['durasi' => 90, 'sifat' => 'fleksibel'])]); }

    public function store(Request $r)
    {
        $test = auth()->user()->tests()->create($r->validate($this->rules()));
        return redirect()->route('dosen.soal', $test)->with('ok', 'Tes dibuat. Silakan tambahkan soal.');
    }

    public function edit(Test $test) { return view('dosen.form', ['test' => $this->own($test)]); }

    public function update(Request $r, Test $test)
    {
        $this->own($test)->update($r->validate($this->rules()));
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
        $data = $r->validate([
            'tipe' => 'required|in:pilihan_ganda,essai',
            'pertanyaan' => 'required|string',
            'media_type' => 'nullable|in:gambar,link',
            'media_url' => 'nullable|required_if:media_type,link|url|max:2048',
            'media_file' => 'nullable|required_if:media_type,gambar|image|max:5120',
        ]);
        if (($data['media_type'] ?? null) === 'gambar') {
            $data['media_url'] = Storage::disk('public')->url($r->file('media_file')->store('question-media', 'public'));
        } elseif (($data['media_type'] ?? null) !== 'link') {
            $data['media_url'] = null;
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
        $this->own($test)->questions()->create($data);
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
        return view('dosen.nilai', ['test' => $this->own($test), 'attempts' => $test->attempts()->with('user')->get()]);
    }
}
