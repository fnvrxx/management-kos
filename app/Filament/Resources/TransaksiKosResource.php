<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiKosResource\Pages;
use App\Filament\Resources\TransaksiKosResource\RelationManagers;
use Filament\Tables\Actions\ExportAction; // Import Action Export
use App\Filament\Exports\TransaksiKosExporter; // Import Exporter Class
use App\Models\TransaksiKos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransaksiKosResource extends Resource
{
    protected static ?string $model = TransaksiKos::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationGroup = 'Data Master';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal_pembayaran')
                    ->label('Tanggal')
                    ->required(),

                Forms\Components\Select::make('id_tempat_kos')
                    ->label('Kamar')
                    ->options(function () {
                        return \App\Models\TempatKos::with('penyewa')
                            ->get()
                            ->mapWithKeys(fn($k) => [
                                $k->id => $k->kode_unik . ' — ' . $k->lokasi
                                    . ($k->penyewa ? ' [' . $k->penyewa->nama_lengkap . ']' : ' [Kosong]'),
                            ]);
                    })
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $kamar = \App\Models\TempatKos::find($state);
                        if ($kamar) {
                            $set('id_penyewa', $kamar->id_penyewa);
                            $set('nominal', $kamar->harga);
                        }
                    })
                    ->required(),

                Forms\Components\Select::make('id_penyewa')
                    ->relationship('penyewa', 'nama_lengkap')
                    ->label('Penyewa')
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('nominal')
                    ->label('Nominal')
                    ->prefix('Rp')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('durasi_bulan_dibayar')
                    ->label('Durasi (Bulan)')
                    ->numeric()
                    ->default(1)
                    ->required(),

                Forms\Components\Select::make('metode_pembayaran')
                    ->options(['Transfer' => 'Transfer', 'Tunai' => 'Tunai', 'QRIS' => 'QRIS'])
                    ->required(),

                Forms\Components\FileUpload::make('bukti_transfer')
                    ->label('Bukti Transfer')
                    ->image()
                    ->directory('bukti-bayar')
                    ->visibility('public'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal_pembayaran', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_pembayaran')
                    ->date('d M Y')
                    ->label('Tanggal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('penyewa.nama_lengkap')
                    ->label('Nama Penyewa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tempatKos.kode_unik')
                    ->label('Kamar')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('tempatKos.lokasi')
                    ->label('Lokasi')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nominal')
                    ->money('IDR')
                    ->label('Masuk (Rp)')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')),

                Tables\Columns\TextColumn::make('metode_pembayaran'),
            ])
            ->filters([
                Filter::make('tanggal_pembayaran')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_pembayaran', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_pembayaran', '<=', $date),
                            );
                    })
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(TransaksiKosExporter::class)
                    ->label('Download Laporan (Excel)')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down'),
            ])
            ->actions([
                // Jika perlu: Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksiKos::route('/'),
            'create' => Pages\CreateTransaksiKos::route('/create'),
            'edit' => Pages\EditTransaksiKos::route('/{record}/edit'),
        ];
    }
}
