<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('tipe', ['pilihan_ganda', 'essai'])->default('pilihan_ganda');
            $table->enum('media_type', ['gambar', 'link'])->nullable();
            $table->string('media_url')->nullable();
            $table->string('opsi_a')->nullable()->change();
            $table->string('opsi_b')->nullable()->change();
            $table->string('opsi_c')->nullable()->change();
            $table->string('opsi_d')->nullable()->change();
            $table->enum('jawaban_benar', ['a', 'b', 'c', 'd'])->nullable()->change();
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->longText('jawaban_essai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('jawaban_essai');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['tipe', 'media_type', 'media_url']);
            $table->string('opsi_a')->nullable(false)->change();
            $table->string('opsi_b')->nullable(false)->change();
            $table->string('opsi_c')->nullable(false)->change();
            $table->string('opsi_d')->nullable(false)->change();
            $table->enum('jawaban_benar', ['a', 'b', 'c', 'd'])->nullable(false)->change();
        });
    }
};
