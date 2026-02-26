<?php

namespace App\Filament\Resources\TransaksiKosResource\Pages;

use App\Filament\Resources\TransaksiKosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaksiKos extends EditRecord
{
    protected static string $resource = TransaksiKosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(TransaksiKosResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
            Actions\DeleteAction::make(),
        ];
    }
}
