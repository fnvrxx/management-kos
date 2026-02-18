<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Update Tabel Penyewa: Tambah kolom untuk melacak kapan dia harus bayar lagi
        Schema::table('penyewa', function (Blueprint $table) {
            $table->date('tgl_jatuh_tempo_berikutnya')->nullable()->after('start_date');
        });

        // 2. Update Tabel Transaksi: Tambah bukti foto & periode bayar
        Schema::table('transaksi_kos', function (Blueprint $table) {
            $table->string('bukti_transfer')->nullable()->after('nominal');
            $table->integer('durasi_bulan_dibayar')->default(1)->after('nominal')->comment('Bayar untuk berapa bulan?');
            $table->date('periode_mulai')->nullable();
            $table->date('periode_selesai')->nullable();
        });

        // 3. Buat Tabel Baru: Pengeluaran (Expense)
        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->string('judul'); // Contoh: Token Listrik, Sedot WC
            $table->integer('nominal');
            $table->date('tanggal');
            $table->string('kategori')->default('Operasional'); // Operasional, Perbaikan, Gaji
            $table->string('bukti_foto')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');

        Schema::table('transaksi_kos', function (Blueprint $table) {
            $table->dropColumn(['bukti_transfer', 'durasi_bulan_dibayar', 'periode_mulai', 'periode_selesai']);
        });

        Schema::table('penyewa', function (Blueprint $table) {
            $table->dropColumn('tgl_jatuh_tempo_berikutnya');
        });
    }
};