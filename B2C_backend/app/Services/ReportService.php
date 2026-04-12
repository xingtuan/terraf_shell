<?php

namespace App\Services;

use App\Enums\ReportTargetType;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function create(User $reporter, array $data): Report
    {
        return DB::transaction(function () use ($reporter, $data): Report {
            $type = ReportTargetType::from($data['target_type']);
            $target = $type->modelClass()::query()->find($data['target_id']);

            if ($target === null) {
                throw $this->notFound($type->modelClass(), $data['target_id']);
            }

            if (isset($target->user_id) && (int) $target->user_id === $reporter->id) {
                throw ValidationException::withMessages([
                    'target_id' => ['You cannot report your own content.'],
                ]);
            }

            $exists = Report::query()
                ->where('reporter_id', $reporter->id)
                ->where('target_type', $type->value)
                ->where('target_id', $target->getKey())
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'target_id' => ['You have already reported this content.'],
                ]);
            }

            $report = new Report([
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
            ]);
            $report->reporter()->associate($reporter);
            $report->target()->associate($target);
            $report->save();

            return $report->load(['reporter.profile', 'target']);
        });
    }

    public function listForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = Report::query()
            ->with(['reporter.profile', 'reviewer.profile', 'target'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($this->perPage($filters['per_page'] ?? null))->withQueryString();
    }

    private function perPage(null|int|string $requested): int
    {
        $default = (int) config('community.pagination.default_per_page', 20);
        $max = (int) config('community.pagination.max_per_page', 50);
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }

    private function notFound(string $model, int|string $id): ModelNotFoundException
    {
        return (new ModelNotFoundException)->setModel($model, [$id]);
    }
}
