<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateMessageResource\Pages;
use App\Models\TemplateMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TemplateMessageResource extends Resource
{
    protected static ?string $model = TemplateMessage::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Template Pesan WA';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama_template')
                ->label('Nama Template')
                ->required()
                ->maxLength(255)
                ->placeholder('Contoh: Template Tagihan Bulanan')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('isi_template')
                ->label('Isi Template Pesan')
                ->rows(10)
                ->required()
                ->columnSpanFull()
                ->helperText('Tulis pesan template. Klik tombol variabel di bawah untuk menyisipkan kode variabel.'),

            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('var_nama')
                    ->label('+ {nama}')
                    ->color('info')
                    ->size('sm')
                    ->action(fn (Set $set, Get $get) =>
                        $set('isi_template', ($get('isi_template') ?? '') . '{nama}')
                    ),
                Forms\Components\Actions\Action::make('var_lokasi')
                    ->label('+ {lokasi}')
                    ->color('info')
                    ->size('sm')
                    ->action(fn (Set $set, Get $get) =>
                        $set('isi_template', ($get('isi_template') ?? '') . '{lokasi}')
                    ),
                Forms\Components\Actions\Action::make('var_kamar')
                    ->label('+ {kamar}')
                    ->color('info')
                    ->size('sm')
                    ->action(fn (Set $set, Get $get) =>
                        $set('isi_template', ($get('isi_template') ?? '') . '{kamar}')
                    ),
                Forms\Components\Actions\Action::make('var_tagihan')
                    ->label('+ {tagihan}')
                    ->color('warning')
                    ->size('sm')
                    ->action(fn (Set $set, Get $get) =>
                        $set('isi_template', ($get('isi_template') ?? '') . '{tagihan}')
                    ),
                Forms\Components\Actions\Action::make('var_jatuh_tempo')
                    ->label('+ {jatuh_tempo}')
                    ->color('danger')
                    ->size('sm')
                    ->action(fn (Set $set, Get $get) =>
                        $set('isi_template', ($get('isi_template') ?? '') . '{jatuh_tempo}')
                    ),
            ])
                ->label('Sisipkan Variabel (klik untuk menambahkan ke template)')
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_default')
                ->label('Jadikan Template Default')
                ->helperText('Template default otomatis digunakan saat mengirim tagihan via WhatsApp.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_template')
                    ->label('Nama Template')
                    ->searchable(),

                Tables\Columns\TextColumn::make('isi_template')
                    ->label('Preview')
                    ->limit(60),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
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
            'index'  => Pages\ListTemplateMessages::route('/'),
            'create' => Pages\CreateTemplateMessage::route('/create'),
            'edit'   => Pages\EditTemplateMessage::route('/{record}/edit'),
        ];
    }
}
