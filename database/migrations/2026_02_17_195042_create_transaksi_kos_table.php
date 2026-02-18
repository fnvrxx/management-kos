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
        Schema::create('transaksi_kos', function (Blueprint $table) {
            $table->id();
            $table->string('lokasi_kos');
            $table->integer('price_kos');
            $table->date('tanggal_pembayaran');
            $table->integer('nominal');
            $table->string('metode_pembayaran'); // e.g., Transfer, Cash
            $table->text('history_pembayaran')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_kos');
    }
};
