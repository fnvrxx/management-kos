<?php

namespace App\Filament\Resources\ReminderResource\Pages;

use App\Filament\Resources\ReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateReminder extends CreateRecord
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(ReminderResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
