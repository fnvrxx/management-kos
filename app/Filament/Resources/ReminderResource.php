<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReminderResource\Pages;
use App\Models\Reminder;
use App\Models\Penyewa;
use App\Models\TempatKos;
use App\Models\TransaksiKos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ReminderResource extends Resource
{
    protected static ?string $model = Reminder::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Kirim Tagihan WA';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_penyewa')
                    ->label('Penyewa')
                    ->options(function () {
                        return Penyewa::whereHas('tempatKos')
                            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>', now()))
                            ->pluck('nama_lengkap', 'id');
                    })
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state)
                            return;

                        $penyewa = Penyewa::find($state);
                        $kamar = $penyewa?->tempatKos;

                        if ($kamar) {
                            $set('end_date', $kamar->tgl_jatuh_tempo?->toDateString());
                            $tanggungan = TransaksiKos::getTanggungan($kamar);
                            $set('tanggungan', $tanggungan > 0 ? $tanggungan : $kamar->harga);
                        }
                    })
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Jatuh Tempo')
                    ->required(),

                Forms\Components\TextInput::make('tanggungan')
                    ->label('Tagihan / Tanggungan (Rp)')
                    ->prefix('Rp')
                    ->numeric()
                    ->required()
                    ->helperText('Jumlah yang harus dibayar penyewa.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('end_date', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('penyewa.nama_lengkap')
                    ->label('Nama Penyewa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('penyewa.no_wa')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('kamar_info')
                    ->label('Kamar')
                    ->getStateUsing(function ($record) {
                        $kamar = $record->penyewa?->tempatKos;
                        if (!$kamar)
                            return '-';
                        return $kamar->nomor_kamar . ' — ' . $kamar->lokasi;
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('end_date')
                    ->date('d M Y')
                    ->label('Jatuh Tempo')
                    ->color(fn($record) => $record->end_date?->lt(now()) ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggungan')
                    ->label('Tagihan')
                    ->money('IDR')
                    ->color('danger')
                    ->sortable(),

                Tables\Columns\IconColumn::make('broadcast')
                    ->label('Terkirim?')
                    ->boolean(),

                Tables\Columns\TextColumn::make('history_reminder')
                    ->label('Riwayat')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_kirim')
                    ->label('Status')
                    ->options([
                        'belum' => '📩 Belum Dikirim',
                        'sudah' => '✅ Sudah Dikirim',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'belum' => $query->where('broadcast', false),
                            'sudah' => $query->where('broadcast', true),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                // CLICKABLE WHATSAPP BUTTON — opens api.whatsapp.com with pre-filled message
                Tables\Actions\Action::make('send_reminder')
                    ->label('Kirim WA')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->modalHeading('Kirim Tagihan via WhatsApp')
                    ->modalContent(function (Reminder $record) {
                        $msg = nl2br(e(self::buildWhatsAppMessage($record)));
                        $url = self::getWhatsAppUrl($record);

                        return new \Illuminate\Support\HtmlString("
                            <div class='space-y-4'>
                                <div class='p-4 bg-gray-100 dark:bg-gray-800 rounded-lg text-sm leading-relaxed'>
                                    {$msg}
                                </div>
                                <a href='{$url}' target='_blank' rel='noopener'
                                   class='flex items-center justify-center gap-3 w-full px-8 py-5 bg-green-500 hover:bg-green-600 text-white rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl hover:scale-[1.02]'>
                                    📱 Buka WhatsApp &amp; Kirim Pesan
                                </a>
                                <p class='text-xs text-gray-500 dark:text-gray-400'>
                                    Klik tombol hijau di atas untuk membuka WhatsApp dengan pesan otomatis.
                                    Setelah pesan terkirim, klik <strong>\"Tandai Terkirim\"</strong> di bawah.
                                </p>
                            </div>
                        ");
                    })
                    ->modalSubmitActionLabel('✅ Tandai Terkirim')
                    ->action(function (Reminder $record) {
                        $record->update([
                            'broadcast' => true,
                            'history_reminder' => ($record->history_reminder ? $record->history_reminder . "\n" : '')
                                . '[' . now()->format('d/m/Y H:i') . '] Dikirim via WhatsApp ✅',
                        ]);

                        Notification::make()->title('Ditandai sebagai terkirim!')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /**
     * Build custom WhatsApp message in Indonesian.
     */
    public static function buildWhatsAppMessage(Reminder $record): string
    {
        $penyewa = $record->penyewa;
        $kamar = $penyewa?->tempatKos;

        $nama = $penyewa->nama_lengkap ?? '-';
        $lokasi = $kamar->lokasi ?? '-';
        $nomorKamar = $kamar->nomor_kamar ?? '-';
        $tagihan = number_format($record->tanggungan ?? 0, 0, ',', '.');
        $jatuhTempo = $record->end_date?->format('d/m/Y') ?? '-';

        return "Assalamualaikum Kak {$nama} 🙏\n\n"
            . "Kami dari pengelola kos *{$lokasi}* ingin mengingatkan bahwa tagihan kos Anda:\n\n"
            . "📍 Lokasi: *{$lokasi}*\n"
            . "🏠 Kamar: *{$nomorKamar}*\n"
            . "💰 Tagihan: *Rp {$tagihan}*\n"
            . "📅 Jatuh Tempo: *{$jatuhTempo}*\n\n"
            . "Mohon segera melakukan pembayaran agar tidak terjadi keterlambatan.\n\n"
            . "Terima kasih atas kerjasamanya 🙏\n"
            . "Salam, Pengelola Kos {$lokasi}";
    }

    /**
     * Generate WhatsApp click-to-chat URL with pre-filled message.
     * Uses api.whatsapp.com/send — opens WhatsApp directly.
     */
    public static function getWhatsAppUrl(Reminder $record): string
    {
        $phone = $record->penyewa->no_wa ?? '';

        // Format: remove leading 0, add 62 (Indonesia country code)
        $phone = preg_replace('/[^0-9]/', '', $phone); // strip non-digits
        $phone = preg_replace('/^0/', '62', $phone);    // 081xxx → 6281xxx

        $message = urlencode(string: self::buildWhatsAppMessage($record));

        return "https://api.whatsapp.com/send?phone={$phone}&text={$message}";
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReminders::route('/'),
            'create' => Pages\CreateReminder::route('/create'),
            'edit' => Pages\EditReminder::route('/{record}/edit'),
        ];
    }
}
