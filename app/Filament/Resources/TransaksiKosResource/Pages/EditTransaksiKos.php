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
            Actions\DeleteAction::make(),
        ];
    }
}
