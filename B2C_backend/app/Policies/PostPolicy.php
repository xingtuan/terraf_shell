<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Post $post): bool
    {
        return $post->status === ContentStatus::Approved->value
            || ($user !== null && $user->id === $post->user_id);
    }

    public function create(User $user): bool
    {
        return $user->canSubmitConcepts();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
