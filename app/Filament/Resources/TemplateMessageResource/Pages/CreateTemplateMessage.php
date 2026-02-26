<?php

namespace App\Filament\Resources\TemplateMessageResource\Pages;

use App\Filament\Resources\TemplateMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTemplateMessage extends CreateRecord
{
    protected static string $resource = TemplateMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(TemplateMessageResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
