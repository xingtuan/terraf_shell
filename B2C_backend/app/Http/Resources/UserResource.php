<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $canSeePrivate = (bool) ($this->show_private ?? false)
            || ($viewer !== null && ($viewer->is($this->resource) || $viewer->canModerate()));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when($canSeePrivate, $this->email),
            'role' => $this->when($canSeePrivate, $this->roleValue()),
            'account_status' => $this->when($canSeePrivate, $this->accountStatusValue()),
            'is_banned' => $this->when($canSeePrivate, $this->isBanned()),
            'is_restricted' => $this->when($canSeePrivate, $this->isRestricted()),
            'email_verified' => $this->when($canSeePrivate, $this->hasVerifiedEmail()),
            'email_verified_at' => $this->when($canSeePrivate, $this->email_verified_at?->toISOString()),
            'avatar_url' => $this->profile?->avatar_url,
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'followers_count' => $this->when(isset($this->followers_count), (int) $this->followers_count),
            'following_count' => $this->when(isset($this->following_count), (int) $this->following_count),
            'posts_count' => $this->when(isset($this->posts_count), (int) $this->posts_count),
            'comments_count' => $this->when(isset($this->comments_count), (int) $this->comments_count),
            'is_following' => (bool) ($this->is_following ?? false),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
