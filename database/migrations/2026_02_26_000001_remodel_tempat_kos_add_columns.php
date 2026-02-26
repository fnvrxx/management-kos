<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tempat_kos', function (Blueprint $table) {
            $table->integer('harga')->nullable()->after('kode_unik');
            $table->enum('status', ['Ditempati', 'Kosong'])->default('Kosong')->after('harga');
            $table->date('tgl_jatuh_tempo')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tempat_kos', function (Blueprint $table) {
            $table->dropColumn(['harga', 'status', 'tgl_jatuh_tempo']);
        });
    }
};
