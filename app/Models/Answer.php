<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = ['attempt_id', 'question_id', 'jawaban', 'pilihan', 'jawaban_essai', 'benar'];

    public function setPilihanAttribute($value)
    {
        $this->attributes['jawaban'] = $value;
    }

    public function getPilihanAttribute()
    {
        return $this->attributes['jawaban'] ?? null;
    }

    public function question() { return $this->belongsTo(Question::class); }
    public function attempt() { return $this->belongsTo(Attempt::class); }
}
