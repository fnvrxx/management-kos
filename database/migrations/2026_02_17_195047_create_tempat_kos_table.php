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
        Schema::create('tempat_kos', function (Blueprint $table) {
            $table->id();
            $table->enum('lokasi', ['Malang', 'Surabaya', 'Kediri']);
            $table->string('nomor_kamar');
            $table->string('kode_unik')->unique(); // Generated: MLG-001

            // Foreign Keys
            $table->foreignId('id_transaksi')
                ->nullable()
                ->constrained('transaksi_kos')
                ->nullOnDelete();

            $table->foreignId('id_penyewa')
                ->nullable()
                ->constrained('penyewa')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tempat_kos');
    }
};
