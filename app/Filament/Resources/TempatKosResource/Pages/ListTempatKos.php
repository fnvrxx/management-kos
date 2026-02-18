<?php

namespace App\Filament\Resources\TempatKosResource\Pages;

use App\Filament\Resources\TempatKosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTempatKos extends ListRecords
{
    protected static string $resource = TempatKosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
