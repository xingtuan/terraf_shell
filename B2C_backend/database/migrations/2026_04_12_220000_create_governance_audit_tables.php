<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->foreignId('target_user_id')->nullable()->after('actor_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->after('subject_id')->constrained('reports')->nullOnDelete();
            $table->index(['target_user_id', 'created_at'], 'moderation_logs_target_user_created_at_index');
            $table->index(['report_id', 'created_at'], 'moderation_logs_report_created_at_index');
        });

        Schema::create('user_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->string('subject_type')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('type')->index();
            $table->string('severity')->index();
            $table->string('status')->default('open')->index();
            $table->text('reason')->nullable();
            $table->text('resolution_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at'], 'user_violations_user_status_created_at_index');
        });

        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('action')->index();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['actor_user_id', 'created_at'], 'admin_action_logs_actor_created_at_index');
            $table->index(['target_user_id', 'created_at'], 'admin_action_logs_target_created_at_index');
        });

        $this->backfillModerationLogs();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
        Schema::dropIfExists('user_violations');

        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->dropIndex('moderation_logs_target_user_created_at_index');
            $table->dropIndex('moderation_logs_report_created_at_index');
            $table->dropConstrainedForeignId('target_user_id');
            $table->dropConstrainedForeignId('report_id');
        });
    }

    private function backfillModerationLogs(): void
    {
        DB::table('moderation_logs')
            ->select(['id', 'subject_type', 'subject_id'])
            ->orderBy('id')
            ->chunkById(200, function (Collection $logs): void {
                $postIds = [];
                $commentIds = [];
                $reportIds = [];

                foreach ($logs as $log) {
                    if ($log->subject_type === 'post') {
                        $postIds[] = (int) $log->subject_id;
                    }

                    if ($log->subject_type === 'comment') {
                        $commentIds[] = (int) $log->subject_id;
                    }

                    if ($log->subject_type === 'report') {
                        $reportIds[] = (int) $log->subject_id;
                    }
                }

                $postOwners = DB::table('posts')
                    ->whereIn('id', array_values(array_unique($postIds)))
                    ->pluck('user_id', 'id');

                $commentOwners = DB::table('comments')
                    ->whereIn('id', array_values(array_unique($commentIds)))
                    ->pluck('user_id', 'id');

                $reportRows = DB::table('reports')
                    ->select(['id', 'target_type', 'target_id'])
                    ->whereIn('id', array_values(array_unique($reportIds)))
                    ->get()
                    ->keyBy('id');

                $reportPostOwners = DB::table('posts')
                    ->whereIn(
                        'id',
                        $reportRows
                            ->where('target_type', 'post')
                            ->pluck('target_id')
                            ->map(fn ($id): int => (int) $id)
                            ->unique()
                            ->values()
                            ->all()
                    )
                    ->pluck('user_id', 'id');

                $reportCommentOwners = DB::table('comments')
                    ->whereIn(
                        'id',
                        $reportRows
                            ->where('target_type', 'comment')
                            ->pluck('target_id')
                            ->map(fn ($id): int => (int) $id)
                            ->unique()
                            ->values()
                            ->all()
                    )
                    ->pluck('user_id', 'id');

                foreach ($logs as $log) {
                    $targetUserId = null;
                    $reportId = null;

                    if ($log->subject_type === 'post') {
                        $targetUserId = $postOwners[(int) $log->subject_id] ?? null;
                    }

                    if ($log->subject_type === 'comment') {
                        $targetUserId = $commentOwners[(int) $log->subject_id] ?? null;
                    }

                    if ($log->subject_type === 'user') {
                        $targetUserId = (int) $log->subject_id;
                    }

                    if ($log->subject_type === 'report') {
                        $reportId = (int) $log->subject_id;
                        $report = $reportRows->get((int) $log->subject_id);

                        if ($report !== null) {
                            $targetUserId = match ($report->target_type) {
                                'post' => $reportPostOwners[(int) $report->target_id] ?? null,
                                'comment' => $reportCommentOwners[(int) $report->target_id] ?? null,
                                default => null,
                            };
                        }
                    }

                    DB::table('moderation_logs')
                        ->where('id', $log->id)
                        ->update([
                            'target_user_id' => $targetUserId,
                            'report_id' => $reportId,
                        ]);
                }
            });
    }
};
