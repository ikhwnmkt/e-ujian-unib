<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'test_id', 'tipe', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d',
        'jawaban_benar', 'media_type', 'media_url',
    ];
    public function test() { return $this->belongsTo(Test::class); }
}
