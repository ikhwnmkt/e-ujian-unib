<?php
namespace Database\Seeders;

use App\Models\{Question, Test, User};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Admin UNIB', 'email' => 'admin@unib.ac.id', 'password' => 'password', 'role' => 'admin']);
        $dosen = User::create(['name' => 'Bahlil Lahadalia, S.Pd., M.Pd.', 'email' => 'dosen@unib.ac.id', 'password' => 'password',
            'role' => 'dosen', 'nomor_induk' => '198001012005011001', 'tempat_lahir' => 'Bengkulu', 'tanggal_lahir' => '1980-12-24', 'no_hp' => '082182022620']);
        User::create(['name' => 'Bahlil Lahadalia', 'email' => 'mhs@unib.ac.id', 'password' => 'password',
            'role' => 'mahasiswa', 'nomor_induk' => '2405090032', 'tempat_lahir' => 'Bengkulu', 'tanggal_lahir' => '2004-12-24', 'no_hp' => '082182022620']);

        $test = Test::create(['dosen_id' => $dosen->id, 'judul' => 'Tes Bahasa Indonesia', 'tanggal' => now()->toDateString(),
            'jam_mulai' => '00:00', 'jam_selesai' => '23:59', 'durasi' => 90, 'sifat' => 'fleksibel']);
        Question::create(['test_id' => $test->id, 'pertanyaan' => 'Ibu kota Indonesia adalah...', 'opsi_a' => 'Bandung', 'opsi_b' => 'Jakarta',
            'opsi_c' => 'Surabaya', 'opsi_d' => 'Medan', 'jawaban_benar' => 'b']);
    }
}
