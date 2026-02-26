<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\TransaksiKos;
use App\Models\TempatKos;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // 1. Pemasukan Bulan Ini
        $pemasukan = TransaksiKos::whereMonth('tanggal_pembayaran', now()->month)
            ->whereYear('tanggal_pembayaran', now()->year)
            ->sum('nominal');

        // 2. Status kamar — computed from sum-based logic
        $kamarDitempati = TempatKos::where('status', 'Ditempati')->get();

        $tunggakan = 0;
        $belumBayar = 0;
        $cicilan = 0;

        foreach ($kamarDitempati as $kamar) {
            if (!$kamar->tgl_jatuh_tempo) {
                // Check if there's a partial payment
                $partial = TransaksiKos::getPartialPayment($kamar);
                if ($partial > 0) {
                    $cicilan++;
                } else {
                    $belumBayar++;
                }
            } elseif ($kamar->tgl_jatuh_tempo->lte(Carbon::today())) {
                // Past due (on or after jatuh tempo) — check if partial payment toward next month
                $partial = TransaksiKos::getPartialPayment($kamar);
                if ($partial > 0) {
                    $cicilan++;
                } else {
                    $tunggakan++;
                }
            }
            // else: lunas (tgl_jatuh_tempo > today)
        }

        // 3. Kamar Kosong
        $kamarKosong = TempatKos::where('status', 'Kosong')->count();
        $totalKamar  = TempatKos::count();

        return [
            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($pemasukan, 0, ',', '.'))
                ->description('Total uang masuk ' . now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Tunggakan', $tunggakan . ' Kamar')
                ->description('Jatuh tempo lewat, belum bayar sama sekali')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Cicilan', $cicilan . ' Kamar')
                ->description('Sudah bayar sebagian, belum lunas')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

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