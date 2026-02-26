<?php

namespace App\Filament\Resources\TemplateMessageResource\Pages;

use App\Filament\Resources\TemplateMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemplateMessages extends ListRecords
{
    protected static string $resource = TemplateMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('Dashboard')
                ->url(url('/admin'))
                ->icon('heroicon-o-home')
                ->color('gray'),
            Actions\CreateAction::make(),
        ];
    }
}
