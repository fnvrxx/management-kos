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
            ->modifyQueryUsing(fn($query) => $query->whereNull('end_date')->orWhere('end_date', '>', now()))
            ->columns([
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->searchable(),

                Tables\Columns\TextColumn::make('no_wa')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-phone'),

                // LOGIKA STATUS PEMBAYARAN
                Tables\Columns\TextColumn::make('status_bayar')
                    ->label('Status Bulan Ini')
                    ->getStateUsing(function ($record) {
                        $kamar = $record->tempatKos;

                        if (!$kamar || !$kamar->transaksi) {
                            return 'unpaid';
                        }

                        $tglBayar = Carbon::parse($kamar->transaksi->tanggal_pembayaran);

                        return ($tglBayar->isCurrentMonth() && $tglBayar->isCurrentYear())
                            ? 'paid'
                            : 'unpaid';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'paid' => '✅ LUNAS',
                        'unpaid' => '❌ BELUM BAYAR',
                    }),
                Tables\Columns\TextColumn::make('status_tagihan')
                    ->label('Status Tagihan')
                    ->getStateUsing(function ($record) {
                        // Jika belum pernah ada set tanggal (penyewa baru)
                        if (!$record->tgl_jatuh_tempo_berikutnya)
                            return 'Belum Ada Tagihan';

                        $today = now();
                        $dueDate = $record->tgl_jatuh_tempo_berikutnya;
                        $daysLeft = $today->diffInDays($dueDate, false); // false = return negatif jika lewat
            
                        if ($daysLeft < 0)
                            return 'NUNGGAK ' . abs((int) $daysLeft) . ' HARI';
                        if ($daysLeft <= 7)
                            return 'Jatuh Tempo ' . $daysLeft . ' Hari Lagi';

                        return 'AMAN (s/d ' . $dueDate->format('d/m') . ')';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        str_contains($state, 'NUNGGAK') => 'danger',  // Merah
                        str_contains($state, 'Hari Lagi') => 'warning', // Kuning
                        str_contains($state, 'AMAN') => 'success',    // Hijau
                        default => 'gray',
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
                            // 1. LOGIKA JATUH TEMPO PINTAR
                            // Ambil jatuh tempo terakhir. Jika kosong atau masa lalu, mulai dari hari ini.
                            // Jika masih masa depan (misal bayar sebelum waktunya), lanjut dari tanggal itu.
                
                            $jatuhTempoAwal = $record->tgl_jatuh_tempo_berikutnya ?? now();

                            // Pastikan kita tidak menambah bulan dari tanggal lampau yg jauh (reset ke now kalau telat bayar lama)
                            if ($jatuhTempoAwal < now()->subDays(5)) {
                                $jatuhTempoAwal = now();
                            }

                            $durasi = (int) $data['durasi_bulan'];
                            $jatuhTempoBaru = \Carbon\Carbon::parse($jatuhTempoAwal)->copy()->addMonths($durasi);

                            // 2. Simpan Transaksi dengan Detail Lengkap
                            $transaksi = \App\Models\TransaksiKos::create([
                                'id_penyewa' => $record->id,
                                'lokasi_kos' => $record->tempatKos->lokasi ?? '-', // Backup jika kamar dihapus
                                'price_kos' => $data['nominal'],
                                'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                                'nominal' => $data['nominal'],
                                'metode_pembayaran' => $data['metode_pembayaran'],
                                'bukti_transfer' => $data['bukti_transfer'],
                                'durasi_bulan_dibayar' => $durasi,
                                'periode_mulai' => $jatuhTempoAwal,
                                'periode_selesai' => $jatuhTempoBaru,
                                'history_pembayaran' => "Bayar $durasi bulan. Valid s/d " . $jatuhTempoBaru->format('d M Y'),
                            ]);

                            // 3. Update Data Penyewa (Perpanjang masa aktif)
                            $record->update([
                                'tgl_jatuh_tempo_berikutnya' => $jatuhTempoBaru,
                            ]);

                            // 4. Update Kamar (opsional, untuk referensi cepat)
                            if ($record->tempatKos) {
                                $record->tempatKos->update(['id_transaksi' => $transaksi->id]);
                            }

                            Notification::make()->title('Pembayaran Berhasil!')->body("Masa aktif diperpanjang sampai " . $jatuhTempoBaru->format('d M Y'))->success()->send();
                        }),
                    Tables\Actions\Action::make('checkout')
                        ->label('Checkout / Pindah')
                        ->icon('heroicon-o-arrow-right-on-rectangle') // Ikon pintu keluar
                        ->color('danger') // Warna Merah (tanda aksi penting)
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Checkout Penyewa')
                        ->modalDescription('Apakah Anda yakin penyewa ini sudah pindah? Kamar akan dikosongkan dan reminder dimatikan.')
                        ->action(function (Penyewa $record) {
                            // 1. Ambil data kamar yang ditempati penyewa ini
                            $kamar = $record->tempatKos;

                            if ($kamar) {
                                // Kosongkan kamar (Set ID Penyewa & Transaksi jadi NULL)
                                $kamar->update([
                                    'id_penyewa' => null,
                                    'id_transaksi' => null, // Reset status bayar kamar juga
                                ]);
                            }

                            // 2. Set Tanggal Keluar Penyewa jadi HARI INI
                            $record->update([
                                'end_date' => now(),
                            ]);

                            // 3. Hapus semua Reminder tagihan orang ini (biar gak dispam WA)
                            $record->reminders()->delete();

                            // 4. Notifikasi Sukses
                            Notification::make()
                                ->title('Berhasil Checkout')
                                ->body('Kamar telah dikosongkan dan siap untuk penyewa baru.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->filters([
                Tables\Filters\Filter::make('tampilkan_alumni')
                    ->label('Tampilkan Alumni (Sudah Checkout)')
                    ->query(fn($query) => $query->withoutGlobalScopes()) // Reset filter di atas
                    ->toggle(), // Jadi tombol on/off
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
