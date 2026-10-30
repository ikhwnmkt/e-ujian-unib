<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->enum('role', ['admin', 'dosen', 'mahasiswa'])->default('mahasiswa');
            $t->string('nomor_induk')->nullable();   // NIM / NIP
            $t->string('tempat_lahir')->nullable();
            $t->date('tanggal_lahir')->nullable();
            $t->string('no_hp')->nullable();
            $t->string('status')->default('aktif');
            $t->string('fakultas')->default('Teknik');
            $t->string('prodi')->default('Pendidikan Teknik Informatika dan Komputer');
            $t->string('avatar')->nullable();
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['role','nomor_induk','tempat_lahir','tanggal_lahir','no_hp','status','fakultas','prodi','avatar']);
        });
    }
};
