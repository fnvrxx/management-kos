<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Drop kode_unik from tempat_kos
        Schema::table('tempat_kos', function (Blueprint $table) {
            $table->dropUnique(['kode_unik']);
            $table->dropColumn('kode_unik');
        });

        // 2. Create template_messages table for WhatsApp message templates
        Schema::create('template_messages', function (Blueprint $table) {
            $table->id();
            $table->string('nama_template');
            $table->text('isi_template');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_messages');

        Schema::table('tempat_kos', function (Blueprint $table) {
            $table->string('kode_unik')->unique()->after('nomor_kamar');
        });
    }
};
