<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestSchedule extends Model
{
    protected $fillable = [
        'test_id',
        'prodi',
        'kelas',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function getMulaiAtAttribute(): Carbon
    {
        return Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->jam_mulai);
    }

    public function getSelesaiAtAttribute(): Carbon
    {
        return Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->jam_selesai);
    }

    public function getTanggalIndoAttribute(): string
    {
        return $this->tanggal->locale('id')->isoFormat('dddd D MMMM Y');
    }

    public function getWaktuAttribute(): string
    {
        return substr($this->jam_mulai, 0, 5) . ' - ' . substr($this->jam_selesai, 0, 5);
    }

    public function berlangsung(): bool
    {
        return now()->between($this->mulai_at, $this->selesai_at);
    }

    public function selesai(): bool
    {
        return now()->gt($this->selesai_at);
    }
}
