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

        // 2. Hitung Orang yang Belum Bayar Bulan Ini
        // Logika: Ambil semua kamar yg ada penghuninya, cek transaksi terakhirnya
        $kamarTerisi = TempatKos::whereNotNull('id_penyewa')->with('transaksi')->get();

        $belumBayar = $kamarTerisi->filter(function ($kamar) {
            // Jika tidak ada data transaksi sama sekali -> Belum Bayar
            if (!$kamar->transaksi)
                return true;

            // Jika tanggal transaksi bukan bulan ini -> Belum Bayar
            $tglBayar = Carbon::parse($kamar->transaksi->tanggal_pembayaran);
            return !($tglBayar->isCurrentMonth() && $tglBayar->isCurrentYear());
        })->count();

        // 3. Hitung Kamar Kosong
        $kamarKosong = TempatKos::whereNull('id_penyewa')->count();
        $totalKamar = TempatKos::count();

        return [
            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($pemasukan, 0, ',', '.'))
                ->description('Total uang masuk')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success') // Warna Hijau
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Grafik hiasan (dummy data)

            Stat::make('Belum Bayar', $belumBayar . ' Orang')
                ->description('Penyewa nunggak bulan ini')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'), // Warna Merah (Warning!)

            Stat::make('Ketersediaan Kamar', $kamarKosong . ' / ' . $totalKamar)
                ->description('Kamar kosong siap huni')
                ->descriptionIcon('heroicon-m-home')
                ->color('primary'), // Warna Biru
        ];
    }
}