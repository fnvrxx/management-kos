<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // STEP 1: Data migration — populate transaksi_kos.id_tempat_kos
        // The old design stored id_transaksi on tempat_kos.
        // We reverse this: find which tempat_kos each transaksi belongs to.
        DB::statement('
                UPDATE transaksi_kos tk
                SET id_tempat_kos = tkos.id
                FROM tempat_kos tkos
                WHERE tkos.id_transaksi = tk.id
        ');

        // STEP 2: Populate tempat_kos.tgl_jatuh_tempo from latest periode_selesai
        DB::statement('
     UPDATE tempat_kos tkos
    SET tgl_jatuh_tempo = latest.latest_selesai
    FROM (
        SELECT id_tempat_kos, MAX(periode_selesai) AS latest_selesai
        FROM transaksi_kos
        WHERE id_tempat_kos IS NOT NULL
        GROUP BY id_tempat_kos
    ) latest
    WHERE latest.id_tempat_kos = tkos.id
        ');

        // STEP 3: Populate tempat_kos.status based on id_penyewa
        DB::statement("
            UPDATE tempat_kos
            SET status = CASE
                WHEN id_penyewa IS NOT NULL THEN 'Ditempati'
                ELSE 'Kosong'
            END
        ");

        // STEP 4: Remove old columns
        Schema::table('tempat_kos', function (Blueprint $table) {
            $table->dropForeign(['id_transaksi']);
            $table->dropColumn('id_transaksi');
        });

        Schema::table('transaksi_kos', function (Blueprint $table) {
            $table->dropColumn(['lokasi_kos', 'price_kos']);
        });

        Schema::table('penyewa', function (Blueprint $table) {
            $table->dropColumn('tgl_jatuh_tempo_berikutnya');
        });
    }

    public function down(): void
    {
        // Restore removed columns
        Schema::table('penyewa', function (Blueprint $table) {
            $table->date('tgl_jatuh_tempo_berikutnya')->nullable();
        });

        Schema::table('transaksi_kos', function (Blueprint $table) {
            $table->string('lokasi_kos')->nullable();
            $table->integer('price_kos')->nullable();
        });

        Schema::table('tempat_kos', function (Blueprint $table) {
            $table->foreignId('id_transaksi')
                ->nullable()
                ->constrained('transaksi_kos')
                ->nullOnDelete();
        });
    }
};
