<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TempatKosResource\Pages;
use App\Filament\Resources\TempatKosResource\RelationManagers;
use App\Models\TempatKos;
use Carbon\Carbon;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TempatKosResource extends Resource
{
    protected static ?string $model = TempatKos::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Data Master'; // Kelompokkan biar rapi
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('lokasi')
                    ->options([
                        'Malang' => 'Malang',
                        'Surabaya' => 'Surabaya',
                        'Kediri' => 'Kediri',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('nomor_kamar')->required(),
                // Kode Unik is auto-generated, so we don't show it or make it disabled

                Forms\Components\TextInput::make('harga')
                    ->label('Harga Sewa / Bulan')
                    ->prefix('Rp')
                    ->numeric()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options(['Kosong' => 'Kosong', 'Ditempati' => 'Ditempati'])
                    ->default('Kosong')
                    ->required(),

                Forms\Components\Select::make('id_penyewa')
                    ->relationship('penyewa', 'nama_lengkap')
                    ->label('Nama Penyewa')
                    ->searchable()
                    ->preload()
                    // VALIDASI TAMBAHAN:
                    // Pastikan satu orang tidak bisa menempati 2 kamar sekaligus
                    ->unique(ignoreRecord: true)

                    // OPSI TAMBAHAN (Hanya tampilkan penyewa yang BELUM punya kamar)
                    ->options(function () {
                        return \App\Models\Penyewa::whereDoesntHave('tempatKos')
                            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>', now()))
                            ->pluck('nama_lengkap', 'id');
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lokasi')
                    ->sortable()
                    ->badge(), // Biar ada warnanya dikit

                Tables\Columns\TextColumn::make('kode_unik')
                    ->label('Kode Unik')
                    ->copyable(), // Biar bisa dicopy admin

                Tables\Columns\TextColumn::make('nomor_kamar'),

                Tables\Columns\TextColumn::make('penyewa.nama_lengkap')
                    ->label('Penghuni')
                    ->searchable(),

                Tables\Columns\TextColumn::make('harga')
                    ->label('Harga/Bulan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Ditempati' => 'success',
                        'Kosong'    => 'gray',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tgl_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->color(fn($record) => $record->tgl_jatuh_tempo?->lt(now()) ? 'danger' : 'success')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lokasi')
                    ->options([
                        'Malang' => 'Malang',
                        'Surabaya' => 'Surabaya',
                        'Kediri' => 'Kediri',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['Kosong' => 'Kosong', 'Ditempati' => 'Ditempati']),
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
            'index' => Pages\ListTempatKos::route('/'),
            'create' => Pages\CreateTempatKos::route('/create'),
            'edit' => Pages\EditTempatKos::route('/{record}/edit'),
        ];
    }
}
