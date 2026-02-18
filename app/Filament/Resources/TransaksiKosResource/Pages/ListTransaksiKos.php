<?php

namespace App\Filament\Resources\TransaksiKosResource\Pages;

use App\Filament\Resources\TransaksiKosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransaksiKos extends ListRecords
{
    protected static string $resource = TransaksiKosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
