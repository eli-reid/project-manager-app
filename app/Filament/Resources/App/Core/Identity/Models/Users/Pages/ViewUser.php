<?php

namespace App\Filament\Resources\App\Core\Identity\Models\Users\Pages;

use App\Filament\Resources\App\Core\Identity\Models\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
