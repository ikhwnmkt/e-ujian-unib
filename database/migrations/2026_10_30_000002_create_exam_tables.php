<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $t->string('judul');
            $t->date('tanggal');
            $t->time('jam_mulai');
            $t->time('jam_selesai');
            $t->unsignedInteger('durasi');                       // menit
            $t->enum('sifat', ['fleksibel', 'ketat'])->default('fleksibel');
            $t->timestamps();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_id')->constrained()->cascadeOnDelete();
            $t->text('pertanyaan');
            $t->string('opsi_a'); $t->string('opsi_b'); $t->string('opsi_c'); $t->string('opsi_d');
            $t->enum('jawaban_benar', ['a','b','c','d']);
            $t->timestamps();
        });
        Schema::create('attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamp('started_at');
            $t->timestamp('finished_at')->nullable();
            $t->decimal('nilai', 5, 2)->nullable();
            $t->timestamps();
            $t->unique(['test_id', 'user_id']);
        });
        Schema::create('answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->enum('jawaban', ['a','b','c','d'])->nullable();
            $t->boolean('benar')->default(false);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('answers'); Schema::dropIfExists('attempts');
        Schema::dropIfExists('questions'); Schema::dropIfExists('tests');
    }
};
