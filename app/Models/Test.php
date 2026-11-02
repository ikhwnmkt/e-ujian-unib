<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    protected $fillable = [
        'dosen_id', 'judul', 'mata_kuliah', 'materi', 'tanggal', 'jam_mulai', 'jam_selesai',
        'durasi', 'sifat', 'tipe_ujian', 'jumlah_soal_tampil', 'kkm',
    ];
    protected $casts = [
        'tanggal' => 'date',
        'jumlah_soal_tampil' => 'integer',
        'kkm' => 'integer',
    ];

    public function dosen(): BelongsTo { return $this->belongsTo(User::class, 'dosen_id'); }
    public function questions(): HasMany { return $this->hasMany(Question::class); }
    public function attempts(): HasMany { return $this->hasMany(Attempt::class); }
    public function schedules(): HasMany { return $this->hasMany(TestSchedule::class); }

    public function isPilihanGanda(): bool { return ($this->tipe_ujian ?? 'pilihan_ganda') === 'pilihan_ganda'; }
    public function isEssai(): bool { return $this->tipe_ujian === 'essai'; }
    public function getTipeUjianLabelAttribute(): string {
        return $this->tipe_ujian === 'essai' ? 'Esai' : 'Pilihan Ganda';
    }

    public function scheduleFor(?User $user = null): ?TestSchedule
    {
        $user = $user ?? auth()->user();
        if (!$user || $user->role !== 'mahasiswa') {
            return null;
        }

        if ($this->schedules->isEmpty()) {
            return null;
        }

        $matches = $this->schedules->filter(function ($s) use ($user) {
            if (empty($user->kelas) || strcasecmp(trim($s->kelas), trim($user->kelas)) !== 0) {
                return false;
            }
            return empty($s->prodi) || strcasecmp(trim($s->prodi), trim($user->prodi ?? '')) === 0;
        });

        // Prioritaskan jadwal yang mendefinisikan prodi secara spesifik daripada null prodi
        return $matches->sortBy(fn ($s) => empty($s->prodi) ? 1 : 0)->first();
    }

    public function isAccessibleBy(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if ($user && $user->role !== 'mahasiswa') {
            return true;
        }

        if ($this->schedules->isEmpty()) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $this->scheduleFor($user) !== null;
    }

    public function getMulaiAtAttribute(): Carbon
    {
        if ($this->schedules->isNotEmpty() && ($schedule = $this->scheduleFor())) {
            return $schedule->mulai_at;
        }
        return Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->jam_mulai);
    }

    public function getSelesaiAtAttribute(): Carbon
    {
        if ($this->schedules->isNotEmpty() && ($schedule = $this->scheduleFor())) {
            return $schedule->selesai_at;
        }
        return Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->jam_selesai);
    }

    public function getTanggalIndoAttribute(): string
    {
        if ($this->schedules->isNotEmpty() && ($schedule = $this->scheduleFor())) {
            return $schedule->tanggal_indo;
        }
        return $this->tanggal->locale('id')->isoFormat('dddd D MMMM Y');
    }

    public function getWaktuAttribute(): string
    {
        if ($this->schedules->isNotEmpty() && ($schedule = $this->scheduleFor())) {
            return $schedule->waktu;
        }
        return substr($this->jam_mulai, 0, 5) . ' - ' . substr($this->jam_selesai, 0, 5);
    }

    public function berlangsung(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if ($this->schedules->isNotEmpty()) {
            if ($user && $user->role === 'mahasiswa') {
                $schedule = $this->scheduleFor($user);
                return $schedule ? $schedule->berlangsung() : false;
            }
            return $this->schedules->contains(fn ($s) => $s->berlangsung());
        }

        return now()->between($this->mulai_at, $this->selesai_at);
    }

    public function selesai(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if ($this->schedules->isNotEmpty()) {
            if ($user && $user->role === 'mahasiswa') {
                $schedule = $this->scheduleFor($user);
                return $schedule ? $schedule->selesai() : true;
            }
            return $this->schedules->every(fn ($s) => $s->selesai());
        }

        return now()->gt($this->selesai_at);
    }

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
