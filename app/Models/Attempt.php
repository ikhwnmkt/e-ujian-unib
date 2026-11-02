<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    protected $fillable = ['test_id', 'user_id', 'question_ids', 'started_at', 'finished_at', 'nilai'];
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'question_ids' => 'array',
    ];

    public function test() { return $this->belongsTo(Test::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function answers() { return $this->hasMany(Answer::class); }

    public function isPassed(): bool
    {
        if ($this->nilai === null) {
            return false;
        }
        $kkm = $this->test?->kkm ?? 70;
        return (float) $this->nilai >= (float) $kkm;
    }

    public function getIsPassedAttribute(): bool
    {
        return $this->isPassed();
    }

    public function canRemedial(): bool
    {
        return ($this->test?->tipe_ujian === 'pilihan_ganda' || empty($this->test?->tipe_ujian))
            && $this->finished_at !== null
            && $this->nilai !== null
            && !$this->isPassed()
            && ($this->test?->berlangsung($this->user) ?? false);
    }
}
