<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenyewaResource\Pages;
use App\Models\Penyewa;
use App\Models\TransaksiKos;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;

class PenyewaResource extends Resource
{
    protected static ?string $model = Penyewa::class;
    protected static ?string $title = 'Finance dashboard';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Data Penyewa (Utama)';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_lengkap')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('no_wa')
                    ->label('Nomor WhatsApp')
                    ->tel()
                    ->required()
                    ->placeholder('Contoh: 08123456789')
                    // Validasi agar format angka saja
                    ->numeric(),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai Kos')
                    ->required(),

                Forms\Components\DatePicker::make('rencana_lama_kos')
                    ->label('Rencana Sampai (Opsional)'),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Tanggal Berhenti (Opsional)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->searchable(),

                Tables\Columns\TextColumn::make('no_wa')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('tempatKos.kode_unik')
                    ->label('Kamar')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status_bayar')
                    ->label('Status Bayar')
                    ->getStateUsing(function ($record) {
                        $kamar = $record->tempatKos;

                        if (!$kamar || !$kamar->tgl_jatuh_tempo) {
                            return 'belum_bayar';
                        }

                        if ($kamar->tgl_jatuh_tempo->lt(now())) {
                            return 'tunggakan';
                        }

                        return 'lunas';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'lunas'       => 'success',
                        'belum_bayar' => 'warning',
                        'tunggakan'   => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'lunas'       => '✅ LUNAS',
                        'belum_bayar' => '🕐 BELUM BAYAR',
                        'tunggakan'   => '❌ TUNGGAKAN',
                        default       => '-',
                    }),

                Tables\Columns\TextColumn::make('jatuh_tempo_label')
                    ->label('Jatuh Tempo')
                    ->getStateUsing(function ($record) {
                        $kamar = $record->tempatKos;
                        if (!$kamar || !$kamar->tgl_jatuh_tempo) return '-';

                        $daysLeft = now()->diffInDays($kamar->tgl_jatuh_tempo, false);

                        if ($daysLeft < 0)  return 'NUNGGAK ' . abs((int) $daysLeft) . ' HARI';
                        if ($daysLeft <= 7) return 'Jatuh Tempo ' . $daysLeft . ' Hari Lagi';

                        return 'Aman s/d ' . $kamar->tgl_jatuh_tempo->format('d/m/Y');
                    })
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        str_contains($state, 'NUNGGAK')   => 'danger',
                        str_contains($state, 'Hari Lagi') => 'warning',
                        str_contains($state, 'Aman')      => 'success',
                        default                           => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    // TOMBOL QUICK PAY
                    Tables\Actions\Action::make('catat_bayar')
                        ->label('Bayar Tagihan')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            Forms\Components\DatePicker::make('tanggal_pembayaran')
                                ->default(now())
                                ->required(),

                            Forms\Components\TextInput::make('durasi_bulan')
                                ->label('Bayar untuk berapa bulan?')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->reactive() // Agar bisa hitung total otomatis (opsional)
                                ->helperText('Contoh: Isi 3 jika bayar langsung 3 bulan.'),

                            Forms\Components\TextInput::make('nominal')
                                ->prefix('Rp')
                                ->numeric()
                                ->required(),

                            Forms\Components\FileUpload::make('bukti_transfer')
                                ->label('Bukti Transfer (Foto/Screenshot)')
                                ->image()
                                ->directory('bukti-bayar') // Folder penyimpanan
                                ->visibility('public'),

                            Forms\Components\Select::make('metode_pembayaran')
                                ->options(['Transfer' => 'Transfer Bank', 'Tunai' => 'Tunai', 'QRIS' => 'QRIS'])
                                ->default('Transfer')
                                ->required(),
                        ])
                        ->action(function (Penyewa $record, array $data) {
                            $kamar = $record->tempatKos;

                            if (!$kamar) {
                                Notification::make()->title('Error: Penyewa belum assign ke kamar.')->danger()->send();
                                return;
                            }

                            // Mulai dari tgl_jatuh_tempo kamar, atau tanggal bayar jika belum ada
                            $periodeAwal = $kamar->tgl_jatuh_tempo
                                ? Carbon::parse($kamar->tgl_jatuh_tempo)
                                : Carbon::parse($data['tanggal_pembayaran']);

                            // Jika sudah lewat jatuh tempo (tunggakan), mulai dari tanggal pembayaran
                            if ($periodeAwal->lt(now()->subDays(5))) {
                                $periodeAwal = Carbon::parse($data['tanggal_pembayaran']);
                            }

                            $durasi       = (int) $data['durasi_bulan'];
                            $periodeAkhir = $periodeAwal->copy()->addMonths($durasi)->subDay();

                            // Model boot() di TransaksiKos akan auto-sync tgl_jatuh_tempo pada kamar
                            \App\Models\TransaksiKos::create([
                                'id_penyewa'           => $record->id,
                                'id_tempat_kos'        => $kamar->id,
                                'tanggal_pembayaran'   => $data['tanggal_pembayaran'],
                                'nominal'              => $data['nominal'],
                                'metode_pembayaran'    => $data['metode_pembayaran'],
                                'bukti_transfer'       => $data['bukti_transfer'] ?? null,
                                'durasi_bulan_dibayar' => $durasi,
                                'periode_mulai'        => $periodeAwal,
                                'periode_selesai'      => $periodeAkhir,
                                'history_pembayaran'   => "Bayar $durasi bulan. Valid s/d " . $periodeAkhir->format('d M Y'),
                            ]);

                            Notification::make()
                                ->title('Pembayaran Berhasil!')
                                ->body('Masa aktif diperpanjang sampai ' . $periodeAkhir->format('d M Y'))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('checkout')
                        ->label('Checkout / Pindah')
                        ->icon('heroicon-o-arrow-right-on-rectangle') // Ikon pintu keluar
                        ->color('danger') // Warna Merah (tanda aksi penting)
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Checkout Penyewa')
                        ->modalDescription('Apakah Anda yakin penyewa ini sudah pindah? Kamar akan dikosongkan dan reminder dimatikan.')
                        ->action(function (Penyewa $record) {
                            $kamar = $record->tempatKos;

                            if ($kamar) {
                                $kamar->update([
                                    'id_penyewa'      => null,
                                    'status'          => 'Kosong',
                                    'tgl_jatuh_tempo' => null,
                                ]);
                            }

                            $record->update(['end_date' => now()]);
                            $record->reminders()->delete();

                            Notification::make()
                                ->title('Berhasil Checkout')
                                ->body('Kamar telah dikosongkan dan siap untuk penyewa baru.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tampilkan')
                    ->label('Tampilkan')
                    ->options([
                        'aktif'  => '🏠 Penyewa Aktif',
                        'alumni' => '📦 Alumni (Sudah Checkout)',
                        'semua'  => '👥 Semua',
                    ])
                    ->default('aktif')
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? 'aktif') {
                            'alumni' => $query->whereNotNull('end_date')->where('end_date', '<=', now()),
                            'semua'  => $query,
                            default  => $query->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>', now())),
                        };
                    }),
            ], layout: FiltersLayout::AboveContent);
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
            'index' => Pages\ListPenyewas::route('/'),
            'create' => Pages\CreatePenyewa::route('/create'),
            'edit' => Pages\EditPenyewa::route('/{record}/edit'),
        ];
    }
}
