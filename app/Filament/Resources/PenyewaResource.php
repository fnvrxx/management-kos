<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenyewaResource\Pages;
use App\Models\Penyewa;
use App\Models\TransaksiKos;
use App\Models\TempatKos;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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

                Tables\Columns\TextColumn::make('tempatKos.nomor_kamar')
                    ->label('Kamar')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status_bayar')
                    ->label('Status Bayar')
                    ->getStateUsing(function ($record) {
                        $kamar = $record->tempatKos;

                        if (!$kamar) {
                            return 'belum_assign';
                        }

                        if (!$kamar->tgl_jatuh_tempo) {
                            // Check if there are any partial payments (cicilan without completing a month)
                            $partial = TransaksiKos::getPartialPayment($kamar);
                            if ($partial > 0) {
                                $formatted = number_format($partial, 0, ',', '.');
                                $hargaFormatted = number_format($kamar->harga, 0, ',', '.');
                                return "cicilan|{$partial}|{$kamar->harga}";
                            }
                            return 'belum_bayar';
                        }

                        if ($kamar->tgl_jatuh_tempo->gt(Carbon::today())) {
                            return 'lunas';
                        }

                        // Jatuh tempo sudah lewat — cek apakah ada cicilan untuk bulan berikutnya
                        $partial = TransaksiKos::getPartialPayment($kamar);
                        if ($partial > 0) {
                            return "cicilan|{$partial}|{$kamar->harga}";
                        }

                        return 'tunggakan';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        str_starts_with($state, 'cicilan') => 'info',
                        $state === 'lunas'                  => 'success',
                        $state === 'belum_bayar'            => 'warning',
                        $state === 'belum_assign'           => 'gray',
                        $state === 'tunggakan'              => 'danger',
                        default                             => 'gray',
                    })
                    ->formatStateUsing(function (string $state): string {
                        if (str_starts_with($state, 'cicilan')) {
                            $parts = explode('|', $state);
                            $paid  = number_format((int)$parts[1], 0, ',', '.');
                            $total = number_format((int)$parts[2], 0, ',', '.');
                            return "💰 CICILAN Rp {$paid} / {$total}";
                        }

                        return match ($state) {
                            'lunas'        => '✅ LUNAS',
                            'belum_bayar'  => '🕐 BELUM BAYAR',
                            'belum_assign' => '⬜ BELUM ASSIGN KAMAR',
                            'tunggakan'    => '❌ TUNGGAKAN',
                            default        => '-',
                        };
                    }),

                Tables\Columns\TextColumn::make('jatuh_tempo_label')
                    ->label('Jatuh Tempo')
                    ->getStateUsing(function ($record) {
                        $kamar = $record->tempatKos;
                        if (!$kamar) return '-';

                        if (!$kamar->tgl_jatuh_tempo) {
                            $partial = TransaksiKos::getPartialPayment($kamar);
                            return $partial > 0 ? 'Cicilan Aktif' : '-';
                        }

                        $daysLeft = (int) Carbon::today()->diffInDays($kamar->tgl_jatuh_tempo, false);

                        if ($daysLeft < 0)   return 'NUNGGAK ' . abs($daysLeft) . ' HARI';
                        if ($daysLeft === 0) return 'JATUH TEMPO HARI INI!';
                        if ($daysLeft <= 7)  return 'Jatuh Tempo ' . $daysLeft . ' Hari Lagi';

                        return 'Aman s/d ' . $kamar->tgl_jatuh_tempo->format('d/m/Y');
                    })
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        str_contains($state, 'NUNGGAK')   => 'danger',
                        str_contains($state, 'HARI INI')  => 'danger',
                        str_contains($state, 'Hari Lagi') => 'warning',
                        str_contains($state, 'Cicilan')   => 'info',
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
                        ->form(function (Penyewa $record) {
                            $kamar = $record->tempatKos;
                            $harga = $kamar?->harga ?? 0;
                            $partial = $kamar ? TransaksiKos::getPartialPayment($kamar) : 0;
                            $sisa = $harga - $partial;

                            $helperText = $partial > 0
                                ? 'Sudah ada cicilan Rp ' . number_format($partial, 0, ',', '.') .
                                  '. Sisa tagihan: Rp ' . number_format($sisa, 0, ',', '.')
                                : 'Harga sewa: Rp ' . number_format($harga, 0, ',', '.');

                            return [
                                Forms\Components\DatePicker::make('tanggal_pembayaran')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\TextInput::make('nominal')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->required()
                                    ->default($sisa > 0 ? $sisa : $harga)
                                    ->helperText($helperText),

                                Forms\Components\FileUpload::make('bukti_transfer')
                                    ->label('Bukti Transfer (Foto/Screenshot)')
                                    ->image()
                                    ->directory('bukti-bayar')
                                    ->visibility('public'),

                                Forms\Components\Select::make('metode_pembayaran')
                                    ->options(['Transfer' => 'Transfer Bank', 'Tunai' => 'Tunai', 'QRIS' => 'QRIS'])
                                    ->default('Transfer')
                                    ->required(),
                            ];
                        })
                        ->action(function (Penyewa $record, array $data) {
                            $kamar = $record->tempatKos;

                            if (!$kamar) {
                                Notification::make()->title('Error: Penyewa belum assign ke kamar.')->danger()->send();
                                return;
                            }

                            $nominal = (int) $data['nominal'];
                            $harga   = $kamar->harga ?? 0;

                            // Determine how many full months this payment covers
                            // (considering existing partial payments)
                            $existingPartial = TransaksiKos::getPartialPayment($kamar);
                            $totalWithNew    = $existingPartial + $nominal;
                            $fullMonths      = $harga > 0 ? (int) floor($totalWithNew / $harga) : 0;

                            // Compute periode for record-keeping
                            $periodeAwal = $kamar->tgl_jatuh_tempo
                                ? Carbon::parse($kamar->tgl_jatuh_tempo)
                                : Carbon::parse($record->start_date ?? $data['tanggal_pembayaran']);

                            $periodeAkhir = $fullMonths > 0
                                ? $periodeAwal->copy()->addMonths($fullMonths)->subDay()
                                : $periodeAwal->copy();

                            // Build human-readable history
                            $nominalFormatted = number_format($nominal, 0, ',', '.');
                            if ($fullMonths > 0 && ($totalWithNew % $harga) === 0) {
                                $historyText = "Bayar Rp {$nominalFormatted} → LUNAS s/d " . $periodeAkhir->format('d M Y');
                            } elseif ($fullMonths > 0) {
                                $remainder = $totalWithNew % $harga;
                                $historyText = "Bayar Rp {$nominalFormatted} → $fullMonths bulan lunas + cicilan Rp " . number_format($remainder, 0, ',', '.');
                            } else {
                                $historyText = "Cicilan Rp {$nominalFormatted} pada " . Carbon::parse($data['tanggal_pembayaran'])->format('d M Y');
                            }

                            // Boot hook on TransaksiKos::saved will auto-recompute tgl_jatuh_tempo
                            TransaksiKos::create([
                                'id_penyewa'           => $record->id,
                                'id_tempat_kos'        => $kamar->id,
                                'tanggal_pembayaran'   => $data['tanggal_pembayaran'],
                                'nominal'              => $nominal,
                                'metode_pembayaran'    => $data['metode_pembayaran'],
                                'bukti_transfer'       => $data['bukti_transfer'] ?? null,
                                'durasi_bulan_dibayar' => $fullMonths,
                                'periode_mulai'        => $periodeAwal,
                                'periode_selesai'      => $periodeAkhir,
                                'history_pembayaran'   => $historyText,
                            ]);

                            // Refresh model to show updated status
                            $kamar->refresh();
                            $partial = TransaksiKos::getPartialPayment($kamar);

                            if ($partial > 0) {
                                $partialFmt = number_format($partial, 0, ',', '.');
                                $hargaFmt   = number_format($harga, 0, ',', '.');
                                Notification::make()
                                    ->title('Cicilan Tercatat!')
                                    ->body("Sudah dibayar Rp {$partialFmt} dari Rp {$hargaFmt}. Sisa: Rp " . number_format($harga - $partial, 0, ',', '.'))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Pembayaran Berhasil!')
                                    ->body('Masa aktif diperpanjang sampai ' . $kamar->tgl_jatuh_tempo?->format('d M Y'))
                                    ->success()
                                    ->send();
                            }
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
            'index' => Pages\ListPenyewas::route('/'),
            'create' => Pages\CreatePenyewa::route('/create'),
            'edit' => Pages\EditPenyewa::route('/{record}/edit'),
        ];
    }
}
