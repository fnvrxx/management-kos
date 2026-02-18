<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaksi_kos', function (Blueprint $table) {
            //
            $table->foreignId('id_penyewa')->after('id')->nullable()->constrained('penyewa')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_kos', function (Blueprint $table) {
            $table->dropForeign(['id_penyewa']);
            $table->dropColumn('id_penyewa');
        });
    }
};
