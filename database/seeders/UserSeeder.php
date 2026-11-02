<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1 Admin
        User::firstOrCreate(
            ['email' => 'admin@unib.ac.id'],
            [
                'name' => 'Administrator UNIB',
                'password' => 'password',
                'role' => 'admin',
                'status' => 'aktif',
                'no_hp' => '081234567890',
            ]
        );

        // 1 Dosen
        User::firstOrCreate(
            ['email' => 'dosen@unib.ac.id'],
            [
                'name' => 'Dr. Ir. Dosen Pengampu, M.Kom.',
                'password' => 'password',
                'role' => 'dosen',
                'nomor_induk' => '198001012005012001',
                'status' => 'aktif',
                'fakultas' => 'Teknik',
                'prodi' => 'Informatika',
                'no_hp' => '081234567891',
                'tempat_lahir' => 'Bengkulu',
                'tanggal_lahir' => '1980-01-01',
            ]
        );

        // 3 Mahasiswa
        User::firstOrCreate(
            ['email' => 'mhs1@unib.ac.id'],
            [
                'name' => 'Aditya Pratama (Mhs 1 - Fresh)',
                'password' => 'password',
                'role' => 'mahasiswa',
                'nomor_induk' => 'G1A021001',
                'status' => 'aktif',
                'fakultas' => 'Teknik',
                'prodi' => 'Informatika',
                'kelas' => 'A',
                'no_hp' => '082100000001',
                'tempat_lahir' => 'Bengkulu',
                'tanggal_lahir' => '2003-05-10',
            ]
        );

        User::firstOrCreate(
            ['email' => 'mhs2@unib.ac.id'],
            [
                'name' => 'Budi Santoso (Mhs 2 - Remedial & Live)',
                'password' => 'password',
                'role' => 'mahasiswa',
                'nomor_induk' => 'G1A021002',
                'status' => 'aktif',
                'fakultas' => 'Teknik',
                'prodi' => 'Informatika',
                'kelas' => 'A',
                'no_hp' => '082100000002',
                'tempat_lahir' => 'Curup',
                'tanggal_lahir' => '2003-08-17',
            ]
        );

        User::firstOrCreate(
            ['email' => 'mhs3@unib.ac.id'],
            [
                'name' => 'Citra Lestari (Mhs 3 - Selesai Essai)',
                'password' => 'password',
                'role' => 'mahasiswa',
                'nomor_induk' => 'G1A021003',
                'status' => 'aktif',
                'fakultas' => 'Teknik',
                'prodi' => 'Informatika',
                'kelas' => 'B',
                'no_hp' => '082100000003',
                'tempat_lahir' => 'Manna',
                'tanggal_lahir' => '2003-11-20',
            ]
        );
    }
}
