<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengeluaranResource\Pages;
use App\Models\Pengeluaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PengeluaranResource extends Resource
{
    protected static ?string $model = Pengeluaran::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Data Master';
    protected static ?string $navigationLabel = 'Pengeluaran';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('judul')
                    ->label('Judul Pengeluaran')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Contoh: Token Listrik, Sedot WC, dll'),

                Forms\Components\TextInput::make('nominal')
                    ->label('Nominal (Rp)')
                    ->prefix('Rp')
                    ->numeric()
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),

                Forms\Components\Select::make('kategori')
                    ->options([
                        'Operasional' => 'Operasional',
                        'Perbaikan'   => 'Perbaikan',
                        'Gaji'        => 'Gaji',
                        'Lainnya'     => 'Lainnya',
                    ])
                    ->default('Operasional')
                    ->required(),

                Forms\Components\FileUpload::make('bukti_foto')
                    ->label('Bukti Foto (Opsional)')
                    ->image()
                    ->directory('bukti-pengeluaran')
                    ->visibility('public'),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->label('Tanggal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable(),

                Tables\Columns\TextColumn::make('kategori')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Operasional' => 'info',
                        'Perbaikan'   => 'warning',
                        'Gaji'        => 'success',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->money('IDR')
                    ->label('Nominal (Rp)')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->default('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'Operasional' => 'Operasional',
                        'Perbaikan'   => 'Perbaikan',
                        'Gaji'        => 'Gaji',
                        'Lainnya'     => 'Lainnya',
                    ]),
                Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari_tanggal'] ?? null,
                                fn(Builder $q, $date) => $q->whereDate('tanggal', '>=', $date))
                            ->when($data['sampai_tanggal'] ?? null,
                                fn(Builder $q, $date) => $q->whereDate('tanggal', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPengeluarans::route('/'),
            'create' => Pages\CreatePengeluaran::route('/create'),
            'edit'   => Pages\EditPengeluaran::route('/{record}/edit'),
        ];
    }
}
