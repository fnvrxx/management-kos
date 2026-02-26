<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transaksi_kos', function (Blueprint $table) {
            $table->foreignId('id_tempat_kos')
                ->nullable()
                ->after('id_penyewa')
                ->constrained('tempat_kos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_kos', function (Blueprint $table) {
            $table->dropForeign(['id_tempat_kos']);
            $table->dropColumn('id_tempat_kos');
        });
    }
};
