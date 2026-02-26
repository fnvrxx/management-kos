<?php

namespace App\Filament\Resources\TempatKosResource\Pages;

use App\Filament\Resources\TempatKosResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTempatKos extends CreateRecord
{
    protected static string $resource = TempatKosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(TempatKosResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
