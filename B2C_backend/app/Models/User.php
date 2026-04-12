<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, MustVerifyEmail, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'account_status',
        'is_banned',
        'banned_at',
        'restricted_at',
        'ban_reason',
        'restriction_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'restricted_at' => 'datetime',
            'is_banned' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function postLikes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function commentLikes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function following(): HasMany
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'following_id');
    }

    public function followingUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follows',
            'follower_id',
            'following_id'
        )->withTimestamps();
    }

    public function followerUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follows',
            'following_id',
            'follower_id'
        )->withTimestamps();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'recipient_user_id');
    }

    public function actorNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'actor_user_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reviewedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'reviewed_by');
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(ModerationLog::class, 'actor_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isModerator(): bool
    {
        return $this->hasRole(UserRole::Moderator);
    }

    public function isCreator(): bool
    {
        return in_array($this->roleValue(), [UserRole::Creator->value, 'user'], true);
    }

    public function isVisitor(): bool
    {
        return $this->hasRole(UserRole::Visitor);
    }

    public function isSmePartner(): bool
    {
        return $this->hasRole(UserRole::SmePartner);
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isModerator();
    }

    public function canModerate(): bool
    {
        return $this->isStaff() && $this->isActive();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin() && $this->isActive();
    }

    public function canSubmitConcepts(): bool
    {
        return $this->isActive() && ($this->isCreator() || $this->isStaff());
    }

    public function isActive(): bool
    {
        return ! $this->isRestricted() && ! $this->isBanned();
    }

    public function isRestricted(): bool
    {
        return $this->accountStatusValue() === AccountStatus::Restricted->value;
    }

    public function isBanned(): bool
    {
        return $this->is_banned || $this->accountStatusValue() === AccountStatus::Banned->value;
    }

    public function isParticipationRestricted(): bool
    {
        return $this->isRestricted() || $this->isBanned();
    }

    public function participationRestrictionReason(): ?string
    {
        if ($this->isBanned()) {
            return $this->ban_reason;
        }

        if ($this->isRestricted()) {
            return $this->restriction_reason;
        }

        return null;
    }

    public function hasRole(string|UserRole $role): bool
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        if ($value === UserRole::Creator->value) {
            return in_array($this->roleValue(), [UserRole::Creator->value, 'user'], true);
        }

        return $this->roleValue() === $value;
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function roleValue(): string
    {
        return (string) $this->role;
    }

    public function accountStatusValue(): string
    {
        if ($this->is_banned) {
            return AccountStatus::Banned->value;
        }

        return (string) ($this->account_status ?: AccountStatus::Active->value);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isStaff() && $this->isActive();
    }
}
