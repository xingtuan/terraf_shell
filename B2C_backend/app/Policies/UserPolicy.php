<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, User $target): bool
    {
        return true;
    }

    public function follow(User $user, User $target): bool
    {
        return ! $user->isParticipationRestricted() && ! $user->is($target);
    }

    public function ban(User $user, User $target): bool
    {
        return $user->canManageUsers() && ! $user->is($target);
    }
}
