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
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->index()->constrained('attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->index()->constrained('questions')->cascadeOnDelete();
            $table->enum('jawaban', ['a', 'b', 'c', 'd'])->nullable();
            $table->longText('jawaban_essai')->nullable();
            $table->boolean('benar')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
