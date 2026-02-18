<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReminderResource\Pages;
use App\Models\Reminder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
class ReminderResource extends Resource
{
    protected static ?string $model = Reminder::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Kirim Tagihan WA';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_penyewa')
                    ->relationship('penyewa', 'nama_lengkap')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Due Date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('end_date', 'asc') // Sorted by closest due date
            ->columns([
                Tables\Columns\TextColumn::make('penyewa.nama_lengkap')->label('Tenant'),
                Tables\Columns\TextColumn::make('penyewa.no_wa')->label('WhatsApp'),
                Tables\Columns\TextColumn::make('end_date')->date()->label('Due Date'),
                Tables\Columns\BooleanColumn::make('broadcast')->label('Sent?'),
                Tables\Columns\TextColumn::make('history_reminder')->limit(20),
            ])
            ->actions([
                // THE MANUAL TRIGGER BUTTON
                Tables\Actions\Action::make('send_reminder')
                    ->label('Send WhatsApp')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Reminder $record) {
                        self::sendWhatsApp($record);
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }
    // Foonte API Integration Logic
    protected static function sendWhatsApp(Reminder $record)
    {
        $tenant = $record->penyewa;

        // Validate Phone
        if (!$tenant || !$tenant->no_wa) {
            Notification::make()->title('Error: No phone number found')->danger()->send();
            return;
        }

        // Prepare Message
        $message = "Hello {$tenant->nama_lengkap}, your boarding house payment is due on {$record->end_date->format('Y-m-d')}. Please complete your payment. Thank you 🙏";

        try {
            // Call Foonte API
            $response = Http::withHeaders([
                'Authorization' => env('FOONTE_TOKEN'),
            ])->post('https://api.fonnte.com/send', [
                        'target' => $tenant->no_wa,
                        'message' => $message,
                        'countryCode' => '62', // Optional: Adjust based on your region
                    ]);

            if ($response->successful()) {
                // Update History and Status
                $record->update([
                    'broadcast' => true,
                    'history_reminder' => $record->history_reminder . "\n[" . now() . "] Sent successfully.",
                ]);

                Notification::make()->title('WhatsApp Reminder Sent!')->success()->send();
            } else {
                Notification::make()->title('Foonte API Error')->body($response->body())->danger()->send();
            }
        } catch (\Exception $e) {
            Notification::make()->title('Connection Error')->body($e->getMessage())->danger()->send();
        }
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
            'index' => Pages\ListReminders::route('/'),
            'create' => Pages\CreateReminder::route('/create'),
            'edit' => Pages\EditReminder::route('/{record}/edit'),
        ];
    }
}
