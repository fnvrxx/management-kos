<?php

namespace Database\Seeders;

use App\Models\TemplateMessage;
use Illuminate\Database\Seeder;

class TemplateMessageSeeder extends Seeder
{
    public function run(): void
    {
        TemplateMessage::create([
            'nama_template' => 'Template Tagihan Bulanan',
            'isi_template'  => "Assalamualaikum Kak {nama} 🙏\n\nKami dari pengelola kos *{lokasi}* ingin mengingatkan bahwa tagihan kos Anda:\n\n📍 Lokasi: *{lokasi}*\n🏠 Kamar: *{kamar}*\n💰 Tagihan: *Rp {tagihan}*\n📅 Jatuh Tempo: *{jatuh_tempo}*\n\nMohon segera melakukan pembayaran agar tidak terjadi keterlambatan.\n\nTerima kasih atas kerjasamanya 🙏\nSalam, Pengelola Kos {lokasi}",
            'is_default'    => true,
        ]);

        TemplateMessage::create([
            'nama_template' => 'Template Singkat',
            'isi_template'  => "Halo Kak {nama}, tagihan kos kamar *{kamar}* di *{lokasi}* sebesar *Rp {tagihan}* jatuh tempo *{jatuh_tempo}*. Mohon segera dibayar ya 🙏",
            'is_default'    => false,
        ]);
    }
}
