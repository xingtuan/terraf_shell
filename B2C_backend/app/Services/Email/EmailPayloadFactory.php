<?php

namespace App\Services\Email;

use App\Models\B2BLead;
use App\Models\Comment;
use App\Models\Order;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

class EmailPayloadFactory
{
    public function forUser(User $user, array $extra = []): array
    {
        return array_replace_recursive([
            'user' => $user,
        ], $extra);
    }

    public function forOrder(Order $order, array $extra = []): array
    {
        $order->loadMissing(['user', 'items.product']);
        $status = $order->status instanceof \BackedEnum
            ? $order->status->value
            : (string) $order->status;

        return array_replace_recursive([
            'user' => $order->user,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $status,
                'total' => '$'.number_format((float) $order->total_usd, 2),
                'subtotal' => '$'.number_format((float) $order->subtotal_usd, 2),
                'shipping' => '$'.number_format((float) $order->shipping_usd, 2),
                'currency' => $order->currency,
                'items' => $order->items->map(fn ($item): array => [
                    'name' => $item->product_name,
                    'sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit_price' => '$'.number_format((float) $item->unit_price_usd, 2),
                    'subtotal' => '$'.number_format((float) $item->subtotal_usd, 2),
                ])->all(),
            ],
            'order_url' => rtrim((string) config('app.url'), '/').'/orders/'.$order->order_number,
            'shipping' => [
                'address' => collect([
                    $order->shipping_name,
                    $order->shipping_address_line1,
                    $order->shipping_address_line2,
                    $order->shipping_city,
                    $order->shipping_state_province,
                    $order->shipping_postal_code,
                    $order->shipping_country,
                ])->filter()->implode(', '),
            ],
            'customer_note' => $order->customer_note,
        ], $extra);
    }

    public function forLead(B2BLead $lead, array $extra = []): array
    {
        $lead->loadMissing(['assignee', 'reviewer', 'partnershipInquiry', 'sampleRequest']);

        return array_replace_recursive([
            'user' => [
                'name' => $lead->name,
                'email' => $lead->email,
            ],
            'inquiry' => [
                'name' => $lead->name,
                'email' => $lead->email,
                'company' => $lead->company_name,
                'message' => $lead->message,
                'type' => $lead->inquiry_type,
            ],
            'lead' => [
                'id' => $lead->id,
                'reference' => $lead->reference ?: sprintf('INQ-%06d', $lead->id),
                'status' => $lead->status,
                'type' => $lead->lead_type,
            ],
            'assignee' => [
                'name' => $lead->assignee?->name,
                'email' => $lead->assignee?->email,
            ],
        ], $extra);
    }

    public function forPost(Post $post, ?User $actor = null, array $extra = []): array
    {
        $post->loadMissing('user');

        return array_replace_recursive([
            'user' => $post->user,
            'actor' => [
                'name' => $actor?->name,
                'email' => $actor?->email,
            ],
            'moderator' => [
                'name' => $actor?->name,
                'email' => $actor?->email,
            ],
            'post' => [
                'id' => $post->id,
                'title' => Str::limit($post->title, 160, ''),
                'slug' => $post->slug,
            ],
            'post_url' => rtrim((string) config('app.url'), '/').'/posts/'.($post->slug ?: $post->id),
            'edit_url' => rtrim((string) config('app.url'), '/').'/posts/'.($post->slug ?: $post->id).'/edit',
        ], $extra);
    }

    public function forComment(Comment $comment, ?User $actor = null, array $extra = []): array
    {
        $comment->loadMissing(['user', 'post.user', 'parent.user']);

        return array_replace_recursive($this->forPost($comment->post, $actor), [
            'comment' => [
                'id' => $comment->id,
                'content' => Str::limit(strip_tags($comment->content), 240, ''),
            ],
        ], $extra);
    }
}
