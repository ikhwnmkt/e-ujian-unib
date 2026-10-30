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
        Schema::table('tests', function (Blueprint $table) {
            $table->string('materi')->nullable()->after('judul');
            $table->unsignedInteger('jumlah_soal_tampil')->default(30)->after('sifat');
            $table->unsignedInteger('kkm')->default(70)->after('jumlah_soal_tampil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn(['materi', 'jumlah_soal_tampil', 'kkm']);
        });
    }
};
