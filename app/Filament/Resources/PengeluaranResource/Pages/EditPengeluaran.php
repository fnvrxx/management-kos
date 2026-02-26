<?php

namespace App\Filament\Resources\PengeluaranResource\Pages;

use App\Filament\Resources\PengeluaranResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPengeluaran extends EditRecord
{
    protected static string $resource = PengeluaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(PengeluaranResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
            Actions\DeleteAction::make(),
        ];
    }
}
