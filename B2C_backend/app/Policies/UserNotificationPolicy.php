<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;

class UserNotificationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, UserNotification $notification): bool
    {
        return $user->id === $notification->recipient_user_id;
    }

    public function update(User $user, UserNotification $notification): bool
    {
        return $user->id === $notification->recipient_user_id;
    }
}
