<?php

namespace App\Filament\Exports;

use App\Models\TransaksiKos;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransaksiKosExporter extends Exporter
{
    protected static ?string $model = TransaksiKos::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID Transaksi'),

            // Mengambil nama penyewa dari relasi
            ExportColumn::make('penyewa.nama_lengkap')->label('Nama Penyewa'),

            ExportColumn::make('tempatKos.lokasi')->label('Lokasi'),
            ExportColumn::make('tempatKos.nomor_kamar')->label('Kamar'),
            ExportColumn::make('tanggal_pembayaran')->label('Tanggal'),
            ExportColumn::make('nominal')->label('Jumlah Uang'),
            ExportColumn::make('metode_pembayaran')->label('Metode'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Laporan keuangan berhasil di-download.';
    }
}
