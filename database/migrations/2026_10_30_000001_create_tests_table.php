<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->string('materi')->nullable();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->unsignedInteger('durasi'); // menit
            $table->enum('sifat', ['fleksibel', 'ketat'])->default('fleksibel');
            $table->enum('tipe_ujian', ['pilihan_ganda', 'essai'])->default('pilihan_ganda');
            $table->unsignedInteger('jumlah_soal_tampil')->default(30);
            $table->unsignedInteger('kkm')->default(70);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
