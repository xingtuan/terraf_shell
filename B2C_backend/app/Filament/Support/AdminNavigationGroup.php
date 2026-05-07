<?php

namespace App\Filament\Support;

use Filament\Support\Contracts\HasLabel;

enum AdminNavigationGroup: string implements HasLabel
{
    case Dashboard = 'dashboard';
    case StoreOperations = 'store_operations';
    case B2BLeads = 'b2b_leads';
    case Community = 'community';
    case Content = 'content_cms';
    case EmailCenter = 'email_center';
    case UsersGovernance = 'users_governance';
    case MediaLibrary = 'media_library';
    case SystemHandover = 'system_handover';

    public function getLabel(): string
    {
        return __("admin.navigation.{$this->value}");
    }
}
