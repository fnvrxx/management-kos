<?php

namespace App\Filament\Resources\TransaksiKosResource\Pages;

use App\Filament\Resources\TransaksiKosResource;
use App\Models\TempatKos;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaksiKos extends CreateRecord
{
    protected static string $resource = TransaksiKosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(TransaksiKosResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $kamar  = TempatKos::find($data['id_tempat_kos'] ?? null);
        $durasi = (int) ($data['durasi_bulan_dibayar'] ?? 1);

        if ($kamar && $durasi > 0) {
            $periodeAwal = $kamar->tgl_jatuh_tempo
                ? Carbon::parse($kamar->tgl_jatuh_tempo)
                : Carbon::parse($data['tanggal_pembayaran']);

            $periodeAkhir = $periodeAwal->copy()->addMonths($durasi)->subDay();

            $data['periode_mulai']       = $periodeAwal->toDateString();
            $data['periode_selesai']     = $periodeAkhir->toDateString();
            $data['history_pembayaran']  = "Bayar $durasi bulan. Valid s/d " . $periodeAkhir->format('d M Y');
        } else {
            // Cicilan — no period advancement
            $data['periode_mulai']  = $data['tanggal_pembayaran'];
            $data['periode_selesai'] = $data['tanggal_pembayaran'];
            $nominal = number_format($data['nominal'] ?? 0, 0, ',', '.');
            $data['history_pembayaran'] = "Cicilan Rp $nominal pada " . Carbon::parse($data['tanggal_pembayaran'])->format('d M Y');
        }

        return $data;
    }
}
