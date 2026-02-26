<?php

namespace App\Filament\Resources\PenyewaResource\Pages;

use App\Filament\Resources\PenyewaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePenyewa extends CreateRecord
{
    protected static string $resource = PenyewaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(PenyewaResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
