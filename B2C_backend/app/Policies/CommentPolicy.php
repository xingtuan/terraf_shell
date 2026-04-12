<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(?User $user, Comment $comment): bool
    {
        return $comment->status === ContentStatus::Approved->value
            || ($user !== null && $user->id === $comment->user_id);
    }

    public function create(User $user): bool
    {
        return ! $user->isParticipationRestricted();
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
