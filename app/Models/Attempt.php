<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    protected $fillable = ['test_id','user_id','started_at','finished_at','nilai'];
    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];

    public function test() { return $this->belongsTo(Test::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function answers() { return $this->hasMany(Answer::class); }
}
