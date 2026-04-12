<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\User;
use Carbon\CarbonInterface;

class PostRankingService
{
    public function refreshScores(Post $post): Post
    {
        $engagementScore = $this->engagementScore(
            (int) $post->likes_count,
            (int) $post->comments_count,
            (int) $post->favorites_count
        );

        $trendingScore = $post->status === ContentStatus::Approved->value
            ? $this->trendingScore($post)
            : 0;

        $post->forceFill([
            'engagement_score' => $engagementScore,
            'trending_score' => $trendingScore,
        ])->save();

        return $post->fresh();
    }

    public function markFeatured(Post $post, bool $isFeatured, User $actor): Post
    {
        $post->forceFill([
            'is_featured' => $isFeatured,
            'featured_at' => $isFeatured ? now() : null,
            'featured_by' => $isFeatured ? $actor->id : null,
        ])->save();

        return $post->fresh();
    }

    public function engagementScore(int $likesCount, int $commentsCount, int $favoritesCount): int
    {
        return ($likesCount * $this->likeWeight())
            + ($commentsCount * $this->commentWeight())
            + ($favoritesCount * $this->favoriteWeight());
    }

    public function trendingScore(Post $post): int
    {
        $windowStart = now()->subDays($this->trendingWindowDays());

        $weeklyLikes = PostLike::query()
            ->where('post_id', $post->id)
            ->where('created_at', '>=', $windowStart)
            ->count();

        $weeklyComments = Comment::query()
            ->where('post_id', $post->id)
            ->where('status', ContentStatus::Approved->value)
            ->where('created_at', '>=', $windowStart)
            ->count();

        $weeklyFavorites = Favorite::query()
            ->where('post_id', $post->id)
            ->where('created_at', '>=', $windowStart)
            ->count();

        $weeklyWeightedEngagement = $this->engagementScore(
            $weeklyLikes,
            $weeklyComments,
            $weeklyFavorites
        ) * 10;

        return $weeklyWeightedEngagement + $this->recencyBoost($post);
    }

    public function rankingFormula(): array
    {
        return [
            'engagement_score' => sprintf(
                'likes_count * %d + comments_count * %d + favorites_count * %d',
                $this->likeWeight(),
                $this->commentWeight(),
                $this->favoriteWeight()
            ),
            'trending_score' => sprintf(
                '((weekly_likes * %d + weekly_comments * %d + weekly_favorites * %d) * 10) + recency_boost',
                $this->likeWeight(),
                $this->commentWeight(),
                $this->favoriteWeight()
            ),
            'window_days' => $this->trendingWindowDays(),
            'recency_boost_max_hours' => $this->trendingRecencyBoostHours(),
        ];
    }

    private function recencyBoost(Post $post): int
    {
        $referenceAt = $this->rankingReferenceAt($post);
        $maxHours = $this->trendingRecencyBoostHours();
        $ageHours = min($maxHours, max(0, now()->diffInHours($referenceAt)));

        return max(0, $maxHours - $ageHours);
    }

    private function rankingReferenceAt(Post $post): CarbonInterface
    {
        return $post->published_at ?? $post->created_at ?? now();
    }

    private function likeWeight(): int
    {
        return (int) config('community.discovery.weights.like', 3);
    }

    private function commentWeight(): int
    {
        return (int) config('community.discovery.weights.comment', 4);
    }

    private function favoriteWeight(): int
    {
        return (int) config('community.discovery.weights.favorite', 2);
    }

    private function trendingWindowDays(): int
    {
        return (int) config('community.discovery.trending_window_days', 7);
    }

    private function trendingRecencyBoostHours(): int
    {
        return (int) config('community.discovery.trending_recency_boost_hours', 168);
    }
}
