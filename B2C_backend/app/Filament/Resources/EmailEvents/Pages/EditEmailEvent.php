<?php

namespace App\Filament\Resources\EmailEvents\Pages;

use App\Filament\Resources\EmailEvents\EmailEventResource;
use Filament\Resources\Pages\EditRecord;

class EditEmailEvent extends EditRecord
{
    protected static string $resource = EmailEventResource::class;
}
