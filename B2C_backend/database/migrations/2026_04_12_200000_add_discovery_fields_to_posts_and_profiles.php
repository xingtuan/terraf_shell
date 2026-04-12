<?php

use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('engagement_score')->default(0)->index();
            $table->unsignedInteger('trending_score')->default(0)->index();
            $table->timestamp('featured_at')->nullable()->index();
            $table->foreignId('featured_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['status', 'engagement_score']);
            $table->index(['status', 'trending_score']);
            $table->index(['is_featured', 'featured_at']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->index('school_or_company');
            $table->index('region');
        });

        $now = CarbonImmutable::now();
        $windowStart = $now->subDays(7);
        $likeWeight = 3;
        $commentWeight = 4;
        $favoriteWeight = 2;
        $recencyBoostMaxHours = 168;

        $posts = DB::table('posts')
            ->select(['id', 'status', 'likes_count', 'comments_count', 'favorites_count', 'is_featured', 'published_at', 'created_at', 'updated_at'])
            ->get();

        foreach ($posts as $post) {
            $weeklyLikes = DB::table('post_likes')
                ->where('post_id', $post->id)
                ->where('created_at', '>=', $windowStart)
                ->count();

            $weeklyComments = DB::table('comments')
                ->where('post_id', $post->id)
                ->where('status', ContentStatus::Approved->value)
                ->where('created_at', '>=', $windowStart)
                ->count();

            $weeklyFavorites = DB::table('favorites')
                ->where('post_id', $post->id)
                ->where('created_at', '>=', $windowStart)
                ->count();

            $engagementScore = ((int) $post->likes_count * $likeWeight)
                + ((int) $post->comments_count * $commentWeight)
                + ((int) $post->favorites_count * $favoriteWeight);

            $referenceAt = $post->published_at ?? $post->created_at ?? $now;
            $ageHours = min($recencyBoostMaxHours, max(0, CarbonImmutable::parse($referenceAt)->diffInHours($now)));
            $recencyBoost = max(0, $recencyBoostMaxHours - $ageHours);

            $trendingScore = $post->status === ContentStatus::Approved->value
                ? ((($weeklyLikes * $likeWeight) + ($weeklyComments * $commentWeight) + ($weeklyFavorites * $favoriteWeight)) * 10) + $recencyBoost
                : 0;

            DB::table('posts')
                ->where('id', $post->id)
                ->update([
                    'engagement_score' => $engagementScore,
                    'trending_score' => $trendingScore,
                    'featured_at' => $post->is_featured ? ($post->updated_at ?? $now) : null,
                    'featured_by' => null,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['school_or_company']);
            $table->dropIndex(['region']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('featured_by');
            $table->dropIndex(['status', 'engagement_score']);
            $table->dropIndex(['status', 'trending_score']);
            $table->dropIndex(['is_featured', 'featured_at']);
            $table->dropColumn(['engagement_score', 'trending_score', 'featured_at']);
        });
    }
};
