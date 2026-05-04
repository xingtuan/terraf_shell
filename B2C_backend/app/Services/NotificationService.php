<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\NotificationType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailPayloadFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    public function __construct(
        private readonly EmailDispatchService $emailDispatchService,
        private readonly EmailPayloadFactory $emailPayloadFactory,
    ) {}

    public function dispatch(
        User $recipient,
        ?User $actor,
        NotificationType $type,
        ?Model $target = null,
        array $data = []
    ): void {
        if ($actor !== null && $actor->is($recipient)) {
            return;
        }

        $payload = $this->normalizePayload($type, $data);

        DB::afterCommit(function () use ($recipient, $actor, $type, $payload, $target): void {
            $this->createRecord(
                $recipient->id,
                $actor?->id,
                $type->value,
                $payload['title'],
                $payload['body'],
                $payload['action_url'],
                $target?->getMorphClass(),
                $target?->getKey(),
                $payload['data']
            );
        });
    }

    public function createRecord(
        int $recipientUserId,
        ?int $actorUserId,
        string $type,
        ?string $title,
        ?string $body,
        ?string $actionUrl,
        ?string $targetType,
        ?int $targetId,
        array $data = []
    ): UserNotification {
        return UserNotification::query()->create([
            'recipient_user_id' => $recipientUserId,
            'actor_user_id' => $actorUserId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'data' => $data,
        ]);
    }

    public function listForUser(
        User $user,
        array $filters = [],
        null|int|string $requestedPerPage = null
    ): LengthAwarePaginator {
        $query = UserNotification::query()
            ->where('recipient_user_id', $user->id)
            ->with(['actor.profile', 'target'])
            ->orderByDesc('created_at');

        if (($filters['read'] ?? 'all') === 'read') {
            $query->where('is_read', true);
        }

        if (($filters['read'] ?? 'all') === 'unread') {
            $query->where('is_read', false);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query
            ->paginate($this->perPage($requestedPerPage))
            ->withQueryString();
    }

    public function markAsRead(UserNotification $notification): UserNotification
    {
        if (! $notification->is_read) {
            $notification->forceFill([
                'is_read' => true,
                'read_at' => now(),
            ])->save();
        }

        return $notification->fresh()->load(['actor.profile', 'target']);
    }

    public function unreadCount(User $user): int
    {
        return UserNotification::query()
            ->where('recipient_user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function markAllAsRead(User $user): int
    {
        return UserNotification::query()
            ->where('recipient_user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function notifyPostLiked(Post $post, User $actor): void
    {
        $post->loadMissing('user');

        $this->dispatch(
            $post->user,
            $actor,
            NotificationType::Like,
            $post,
            [
                'title' => 'New like on your concept',
                'body' => sprintf('%s liked "%s".', $actor->name, $this->displayPostTitle($post)),
                'action_url' => $this->postActionUrl($post),
                'message' => sprintf('%s liked your concept.', $actor->name),
                'post_id' => $post->id,
                'post_slug' => $post->slug,
                'interaction_target' => 'post',
            ]
        );

        $this->dispatchCommunityEmail('community.post_liked', $post->user, $actor, $post, $this->emailPayloadFactory->forPost($post, $actor));
    }

    public function notifyCommentLiked(Comment $comment, User $actor): void
    {
        $comment->loadMissing('user', 'post');

        $this->dispatch(
            $comment->user,
            $actor,
            NotificationType::Like,
            $comment,
            [
                'title' => 'New like on your comment',
                'body' => sprintf('%s liked your comment on "%s".', $actor->name, $this->displayPostTitle($comment->post)),
                'action_url' => $this->commentActionUrl($comment),
                'message' => sprintf('%s liked your comment.', $actor->name),
                'post_id' => $comment->post_id,
                'comment_id' => $comment->id,
                'interaction_target' => 'comment',
            ]
        );
    }

    public function notifyPostFavorited(Post $post, User $actor): void
    {
        $post->loadMissing('user');

        $this->dispatch(
            $post->user,
            $actor,
            NotificationType::Favorite,
            $post,
            [
                'title' => 'Your concept was favorited',
                'body' => sprintf('%s favorited "%s".', $actor->name, $this->displayPostTitle($post)),
                'action_url' => $this->postActionUrl($post),
                'message' => sprintf('%s added your concept to favorites.', $actor->name),
                'post_id' => $post->id,
                'post_slug' => $post->slug,
            ]
        );

        $this->dispatchCommunityEmail('community.post_favorited', $post->user, $actor, $post, $this->emailPayloadFactory->forPost($post, $actor));
    }

    public function notifyUserFollowed(User $recipient, User $actor): void
    {
        $this->dispatch(
            $recipient,
            $actor,
            NotificationType::Follow,
            $recipient,
            [
                'title' => 'New follower',
                'body' => sprintf('%s started following you.', $actor->name),
                'action_url' => '/users/'.$actor->id,
                'message' => sprintf('%s started following you.', $actor->name),
                'user_id' => $actor->id,
                'username' => $actor->username,
            ]
        );

        $this->dispatchCommunityEmail('community.follow_created', $recipient, $actor, $recipient, $this->emailPayloadFactory->forUser($recipient, [
            'actor' => [
                'name' => $actor->name,
                'email' => $actor->email,
            ],
            'action_url' => '/users/'.$actor->id,
        ]));
    }

    public function notifyCommentCreated(Post $post, Comment $comment, User $actor): void
    {
        $post->loadMissing('user');

        $this->dispatch(
            $post->user,
            $actor,
            NotificationType::Comment,
            $comment,
            [
                'title' => 'New comment on your concept',
                'body' => sprintf('%s commented on "%s".', $actor->name, $this->displayPostTitle($post)),
                'action_url' => $this->commentActionUrl($comment),
                'message' => sprintf('%s commented on your concept.', $actor->name),
                'post_id' => $post->id,
                'post_slug' => $post->slug,
                'comment_id' => $comment->id,
            ]
        );

        $this->dispatchCommunityEmail('community.comment_created', $post->user, $actor, $comment, $this->emailPayloadFactory->forComment($comment, $actor));
    }

    public function notifyReplyCreated(Comment $parent, Comment $reply, User $actor): void
    {
        $parent->loadMissing('user', 'post');

        $this->dispatch(
            $parent->user,
            $actor,
            NotificationType::Reply,
            $reply,
            [
                'title' => 'New reply to your comment',
                'body' => sprintf('%s replied to your comment on "%s".', $actor->name, $this->displayPostTitle($parent->post)),
                'action_url' => $this->commentActionUrl($reply),
                'message' => sprintf('%s replied to your comment.', $actor->name),
                'post_id' => $parent->post_id,
                'post_slug' => $parent->post?->slug,
                'comment_id' => $reply->id,
                'parent_comment_id' => $parent->id,
            ]
        );

        $this->dispatchCommunityEmail('community.reply_created', $parent->user, $actor, $reply, $this->emailPayloadFactory->forComment($reply, $actor, [
            'user' => $parent->user,
        ]));
    }

    public function notifyPostApproved(Post $post, User $actor): void
    {
        $post->loadMissing('user');

        $this->dispatch(
            $post->user,
            $actor,
            NotificationType::SubmissionApproved,
            $post,
            [
                'title' => 'Concept approved',
                'body' => sprintf('Your concept "%s" was approved and is now visible.', $this->displayPostTitle($post)),
                'action_url' => $this->postActionUrl($post),
                'message' => 'Your concept was approved.',
                'post_id' => $post->id,
                'post_slug' => $post->slug,
                'status' => 'approved',
            ]
        );

        $this->dispatchCommunityEmail('community.post_approved', $post->user, $actor, $post, $this->emailPayloadFactory->forPost($post, $actor));
    }

    public function notifyPostRejected(Post $post, User $actor, ?string $reason = null): void
    {
        $post->loadMissing('user');
        $body = sprintf('Your concept "%s" was rejected.', $this->displayPostTitle($post));

        if ($reason !== null && $reason !== '') {
            $body .= ' Review note: '.Str::limit($reason, 240);
        }

        $this->dispatch(
            $post->user,
            $actor,
            NotificationType::SubmissionRejected,
            $post,
            [
                'title' => 'Concept rejected',
                'body' => $body,
                'action_url' => $this->postActionUrl($post),
                'message' => 'Your concept was rejected.',
                'post_id' => $post->id,
                'post_slug' => $post->slug,
                'status' => 'rejected',
                'reason' => $reason,
            ]
        );

        $this->dispatchCommunityEmail('community.post_rejected', $post->user, $actor, $post, $this->emailPayloadFactory->forPost($post, $actor, [
            'reason' => $reason,
        ]));
    }

    public function notifyPostFeatured(Post $post, User $actor): void
    {
        $post->loadMissing('user');

        $this->dispatch(
            $post->user,
            $actor,
            NotificationType::ConceptFeatured,
            $post,
            [
                'title' => 'Concept featured',
                'body' => sprintf('Your concept "%s" is now featured.', $this->displayPostTitle($post)),
                'action_url' => $this->postActionUrl($post),
                'message' => 'Your concept is now featured.',
                'post_id' => $post->id,
                'post_slug' => $post->slug,
            ]
        );

        $this->dispatchCommunityEmail('community.post_featured', $post->user, $actor, $post, $this->emailPayloadFactory->forPost($post, $actor));
    }

    public function broadcastSystemAnnouncement(
        User $actor,
        string $title,
        string $body,
        ?string $actionUrl = null,
        array $roles = [],
        bool $sendEmail = false,
    ): int {
        $createdCount = 0;
        $data = [
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'message' => $body,
            'roles' => array_values($roles),
        ];

        User::query()
            ->whereKeyNot($actor->id)
            ->where('is_banned', false)
            ->where('account_status', AccountStatus::Active->value)
            ->when($roles !== [], fn ($query) => $query->whereIn('role', $roles))
            ->orderBy('id')
            ->chunkById(250, function ($users) use ($actor, $title, $body, $actionUrl, $data, $sendEmail, &$createdCount): void {
                $timestamp = now();
                $rows = $users->map(function (User $recipient) use ($actor, $title, $body, $actionUrl, $data, $timestamp): array {
                    return [
                        'recipient_user_id' => $recipient->id,
                        'actor_user_id' => $actor->id,
                        'type' => NotificationType::SystemAnnouncement->value,
                        'title' => $title,
                        'body' => $body,
                        'action_url' => $actionUrl,
                        'target_type' => null,
                        'target_id' => null,
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'is_read' => false,
                        'read_at' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                })->all();

                if ($rows !== []) {
                    UserNotification::query()->insert($rows);
                    $createdCount += count($rows);
                }

                if ($sendEmail) {
                    foreach ($users as $recipient) {
                        $this->emailDispatchService->sendEvent(
                            'community.system_announcement',
                            $this->emailPayloadFactory->forUser($recipient, [
                                'actor' => [
                                    'name' => $actor->name,
                                    'email' => $actor->email,
                                ],
                                'announcement' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'action_url' => $actionUrl,
                            ]),
                            [
                                'to' => [$recipient],
                                'idempotency_key' => 'community.system_announcement:'.$recipient->id.':'.sha1($title.'|'.$body.'|'.($actionUrl ?? '')),
                            ],
                        );
                    }
                }
            });

        return $createdCount;
    }

    private function dispatchCommunityEmail(
        string $eventKey,
        User $recipient,
        ?User $actor,
        Model $target,
        array $payload
    ): void {
        if ($actor !== null && $actor->is($recipient)) {
            return;
        }

        $payload['user'] = $recipient;

        $this->emailDispatchService->sendEvent(
            $eventKey,
            $payload,
            [
                'related' => $target,
            ],
        );
    }

    private function perPage(null|int|string $requested): int
    {
        $default = (int) config('community.pagination.default_per_page', 20);
        $max = (int) config('community.pagination.max_per_page', 50);
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }

    private function normalizePayload(NotificationType $type, array $data): array
    {
        $title = $data['title'] ?? $this->defaultTitle($type);
        $body = $data['body'] ?? $data['message'] ?? $this->defaultBody($type);
        $actionUrl = $data['action_url'] ?? null;

        return [
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'data' => array_merge($data, [
                'title' => $title,
                'body' => $body,
                'action_url' => $actionUrl,
                'message' => $data['message'] ?? $body,
            ]),
        ];
    }

    private function defaultTitle(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::Comment => 'New comment',
            NotificationType::Reply => 'New reply',
            NotificationType::Like => 'New like',
            NotificationType::Favorite => 'New favorite',
            NotificationType::Follow => 'New follower',
            NotificationType::SubmissionApproved => 'Concept approved',
            NotificationType::SubmissionRejected => 'Concept rejected',
            NotificationType::ConceptFeatured => 'Concept featured',
            NotificationType::SystemAnnouncement => 'System announcement',
        };
    }

    private function defaultBody(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::Comment => 'Your concept has a new comment.',
            NotificationType::Reply => 'Your comment has a new reply.',
            NotificationType::Like => 'You received a new like.',
            NotificationType::Favorite => 'Your concept was added to favorites.',
            NotificationType::Follow => 'You have a new follower.',
            NotificationType::SubmissionApproved => 'Your concept was approved.',
            NotificationType::SubmissionRejected => 'Your concept was rejected.',
            NotificationType::ConceptFeatured => 'Your concept is now featured.',
            NotificationType::SystemAnnouncement => 'There is a new system announcement.',
        };
    }

    private function postActionUrl(Post $post): string
    {
        return '/posts/'.($post->slug ?: $post->id);
    }

    private function commentActionUrl(Comment $comment): string
    {
        $comment->loadMissing('post');

        return $this->postActionUrl($comment->post).'#comment-'.$comment->id;
    }

    private function displayPostTitle(Post $post): string
    {
        return Str::limit($post->title, 120);
    }
}
