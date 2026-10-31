<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Test extends Model
{
    protected $fillable = [
        'dosen_id', 'judul', 'materi', 'tanggal', 'jam_mulai', 'jam_selesai',
        'durasi', 'sifat', 'tipe_ujian', 'jumlah_soal_tampil', 'kkm',
    ];
    protected $casts = [
        'tanggal' => 'date',
        'jumlah_soal_tampil' => 'integer',
        'kkm' => 'integer',
    ];

    public function dosen() { return $this->belongsTo(User::class, 'dosen_id'); }
    public function questions() { return $this->hasMany(Question::class); }
    public function attempts() { return $this->hasMany(Attempt::class); }

    public function isPilihanGanda(): bool { return ($this->tipe_ujian ?? 'pilihan_ganda') === 'pilihan_ganda'; }
    public function isEssai(): bool { return $this->tipe_ujian === 'essai'; }
    public function getTipeUjianLabelAttribute(): string {
        return $this->tipe_ujian === 'essai' ? 'Esai' : 'Pilihan Ganda';
    }

    public function getMulaiAtAttribute(): Carbon {
        return Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->jam_mulai);
    }
    public function getSelesaiAtAttribute(): Carbon {
        return Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->jam_selesai);
    }
    public function getTanggalIndoAttribute(): string {
        return $this->tanggal->locale('id')->isoFormat('dddd D MMMM Y');
    }
    public function getWaktuAttribute(): string {
        return substr($this->jam_mulai, 0, 5) . ' - ' . substr($this->jam_selesai, 0, 5);
    }
    public function berlangsung(): bool { return now()->between($this->mulai_at, $this->selesai_at); }
    public function selesai(): bool { return now()->gt($this->selesai_at); }

    public function getPesertaCountAttribute(): int
    {
        return $this->pesertaCount();
    }

    public function pesertaCount(): int
    {
        return $this->attempts()->distinct('user_id')->count('user_id');
    }

    /**
     * Mengembalikan daftar attempt utama ter-deduplikasi (1 attempt per mahasiswa)
     * Mengambil attempt dengan nilai tertinggi (atau attempt terbaru jika nilai sama/null)
     * beserta atribut dinamis: attempt_count, first_score, latest_score, best_score, history
     */
    public function getPrimaryAttempts(): \Illuminate\Support\Collection
    {
        $allAttempts = $this->attempts()
            ->with(['user', 'answers.question'])
            ->orderBy('id')
            ->get();

        return $allAttempts->groupBy('user_id')->map(function ($userAttempts) {
            $chronological = $userAttempts->sortBy('id')->values();
            $scored = $chronological->filter(fn ($att) => $att->nilai !== null);

            $firstScore = $chronological->first()->nilai !== null ? (float) $chronological->first()->nilai : null;
            $latestScore = $chronological->last()->nilai !== null ? (float) $chronological->last()->nilai : null;

            if ($scored->isNotEmpty()) {
                $maxScore = (float) $scored->max('nilai');
                $bestScore = $maxScore;
                $primary = $scored->filter(fn ($att) => (float) $att->nilai === $maxScore)->sortByDesc('id')->first();
            } else {
                $bestScore = null;
                $primary = $chronological->last();
            }

            $primary->attempt_count = $chronological->count();
            $primary->first_score = $firstScore;
            $primary->latest_score = $latestScore;
            $primary->best_score = $bestScore;
            $primary->history = $chronological;

            return $primary;
        })->values();
    }
}
