<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name','email','password','role','nomor_induk','tempat_lahir','tanggal_lahir','no_hp','status','fakultas','prodi','avatar'];
    protected $hidden = ['password','remember_token'];

    protected function casts(): array {
        return ['password' => 'hashed', 'tanggal_lahir' => 'date', 'email_verified_at' => 'datetime'];
    }

    public function tests() { return $this->hasMany(Test::class, 'dosen_id'); }
    public function attempts() { return $this->hasMany(Attempt::class); }

    public function getSubtitleAttribute(): string {
        return match ($this->role) {
            'admin' => 'Admin',
            'dosen' => $this->nomor_induk ?: 'Dosen',
            default => $this->nomor_induk ?: 'Mahasiswa',
        };
    }
    public function getTtlAttribute(): string {
        $tgl = $this->tanggal_lahir?->locale('id')->isoFormat('D MMMM Y');
        return trim(($this->tempat_lahir ?: '-') . ($tgl ? ', ' . $tgl : ''));
    }
}
