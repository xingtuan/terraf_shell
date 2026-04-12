<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function create(User $user): bool
    {
        return ! $user->isParticipationRestricted();
    }

    public function view(User $user, Report $report): bool
    {
        return $user->id === $report->reporter_id;
    }
}
