<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Test extends Model
{
    protected $fillable = ['dosen_id','judul','tanggal','jam_mulai','jam_selesai','durasi','sifat'];
    protected $casts = ['tanggal' => 'date'];

    public function dosen() { return $this->belongsTo(User::class, 'dosen_id'); }
    public function questions() { return $this->hasMany(Question::class); }
    public function attempts() { return $this->hasMany(Attempt::class); }

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
}
