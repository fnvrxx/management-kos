<?php

namespace App\Filament\Resources\TempatKosResource\Pages;

use App\Filament\Resources\TempatKosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTempatKos extends EditRecord
{
    protected static string $resource = TempatKosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
