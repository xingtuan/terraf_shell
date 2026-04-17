<?php

namespace App\Support;

use App\Models\B2BLead;
use App\Models\Comment;
use App\Models\Inquiry;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditSubjectSummary
{
    public static function summarize(?Model $subject): ?array
    {
        return match (true) {
            $subject instanceof Post => [
                'type' => 'post',
                'id' => $subject->id,
                'title' => $subject->title,
                'slug' => $subject->slug,
                'status' => $subject->status,
            ],
            $subject instanceof Comment => [
                'type' => 'comment',
                'id' => $subject->id,
                'post_id' => $subject->post_id,
                'content' => Str::limit($subject->content, 160),
                'status' => $subject->status,
            ],
            $subject instanceof User => [
                'type' => 'user',
                'id' => $subject->id,
                'name' => $subject->name,
                'username' => $subject->username,
                'account_status' => $subject->accountStatusValue(),
            ],
            $subject instanceof Report => [
                'type' => 'report',
                'id' => $subject->id,
                'status' => $subject->status,
                'target_type' => $subject->target_type,
                'target_id' => $subject->target_id,
            ],
            $subject instanceof Inquiry => [
                'type' => 'inquiry',
                'id' => $subject->id,
                'reference' => $subject->reference ?: sprintf('INQ-%06d', $subject->id),
                'company_name' => $subject->company_name,
                'status' => $subject->status,
            ],
            $subject instanceof B2BLead => [
                'type' => 'b2b_lead',
                'id' => $subject->id,
                'reference' => $subject->reference ?: sprintf('INQ-%06d', $subject->id),
                'lead_type' => $subject->lead_type,
                'company_name' => $subject->company_name,
                'status' => $subject->status,
            ],
            default => null,
        };
    }
}
