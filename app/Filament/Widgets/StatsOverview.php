<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\TransaksiKos;
use App\Models\TempatKos;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    // Mengatur agar widget ini refresh otomatis setiap 30 detik (opsional)
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // 1. Hitung Pemasukan Bulan Ini
        $pemasukan = TransaksiKos::whereMonth('tanggal_pembayaran', now()->month)
            ->whereYear('tanggal_pembayaran', now()->year)
            ->sum('nominal');

        // 2. Status kamar berdasarkan tgl_jatuh_tempo (bukan bulan kalender)
        $kamarDitempati = TempatKos::where('status', 'Ditempati');

        // Sudah melewati jatuh tempo → TUNGGAKAN
        $tunggakan = (clone $kamarDitempati)
            ->whereNotNull('tgl_jatuh_tempo')
            ->where('tgl_jatuh_tempo', '<', now())
            ->count();

        // Belum pernah bayar (tgl_jatuh_tempo null) → BELUM BAYAR
        $belumBayar = (clone $kamarDitempati)
            ->whereNull('tgl_jatuh_tempo')
            ->count();

        // 3. Hitung Kamar Kosong
        $kamarKosong = TempatKos::where('status', 'Kosong')->count();
        $totalKamar  = TempatKos::count();

        return [
            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($pemasukan, 0, ',', '.'))
                ->description('Total uang masuk ' . now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Tunggakan', $tunggakan . ' Kamar')
                ->description('Jatuh tempo sudah lewat')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Belum Bayar', $belumBayar . ' Kamar')
                ->description('Belum ada transaksi sama sekali')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Ketersediaan Kamar', $kamarKosong . ' / ' . $totalKamar)
                ->description('Kamar kosong siap huni')
                ->descriptionIcon('heroicon-m-home')
                ->color('primary'),
        ];
    }
}