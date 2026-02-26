<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->integer('tanggungan')->nullable()->after('end_date')
                ->comment('Jumlah tagihan yang harus dibayar (Rp)');
        });
    }

    public function down(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->dropColumn('tanggungan');
        });
    }
};
