<?php

namespace App\Services;

use App\Models\FreshMarketOrder;
use App\Models\ProductReview;
use App\Models\StoreRating;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 (2026-10-16) แจ้งผู้ขายเมื่อมีรีวิวใหม่ — ร้านช้อป (รีวิวสินค้า / ดาวร้าน) + ร้านตลาดสด
 *
 * ช่องทางเดียว: NotificationService::create → SendNotificationPush → Expo push เข้าแอป
 * 🚫 ไม่ยิง Expo เอง · 🚫 ไม่มี LINE push (โควต้า 300/เดือน)
 *
 * ส่งหลัง transaction commit เท่านั้น (rollback = ไม่มีแจ้งเตือนหลุด) และห้าม throw —
 * แจ้งเตือนพังต้องไม่ทำให้การบันทึกรีวิวของลูกค้าล้ม
 */
class SellerReviewNotifier
{
    public const TYPE = 'seller_review';

    /**
     * รีวิวสินค้าร้านช้อป → แจ้งเจ้าของสินค้า (products.seller_id)
     */
    public function productReview(ProductReview $review): void
    {
        try {
            $review->loadMissing(['product', 'orderItem']);
            $product = $review->product;
            $seller = $product?->seller_id ? User::find($product->seller_id) : null;

            if (! $seller || (int) $seller->id === (int) $review->user_id) {
                return;
            }

            $orderId = $review->orderItem?->order_id;

            $this->send(
                $seller,
                $this->title((int) $review->rating, 'สินค้า'),
                $this->body($product->name ?? 'สินค้าของคุณ', $review->comment),
                $orderId ? $this->safeRoute('seller.orders.show', ['id' => $orderId]) : null,
                [
                    'event' => 'product_review',
                    'source' => 'shop',
                    'screen' => $orderId ? 'merchant-order' : null,
                    'role' => 'seller',
                    'order_id' => $orderId ? (int) $orderId : null,
                    'product_id' => (int) $review->product_id,
                    'review_id' => (int) $review->id,
                    'rating' => (int) $review->rating,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('SellerReviewNotifier: product review failed', ['review_id' => $review->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * ดาวร้าน (store_ratings) ครั้งแรก → แจ้งเจ้าของร้าน (ให้คะแนนซ้ำ = แก้ของเดิม ไม่แจ้ง)
     */
    public function storeRating(StoreRating $rating): void
    {
        try {
            $rating->loadMissing('store');
            $store = $rating->store;
            $owner = $store?->user_id ? User::find($store->user_id) : null;

            if (! $owner || (int) $owner->id === (int) $rating->user_id) {
                return;
            }

            $this->send(
                $owner,
                $this->title((int) $rating->rating, 'ร้าน'),
                $this->body($store->store_name ?: 'ร้านของคุณ', $rating->comment),
                $this->safeRoute('seller.store-rating.show', $rating->id),
                [
                    'event' => 'store_rating',
                    'source' => 'shop',
                    'screen' => $rating->order_id ? 'merchant-order' : null,
                    'role' => 'seller',
                    'order_id' => $rating->order_id ? (int) $rating->order_id : null,
                    'store_id' => (int) $rating->store_id,
                    'rating_id' => (int) $rating->id,
                    'rating' => (int) $rating->rating,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('SellerReviewNotifier: store rating failed', ['rating_id' => $rating->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * ผู้ซื้อตลาดสดให้ดาวร้าน (buyer_rating) → แจ้งร้าน
     */
    public function freshMarketOrder(FreshMarketOrder $order): void
    {
        try {
            $order->loadMissing('seller.user');
            $sellerUser = $order->seller?->user;

            if (! $sellerUser || ! $order->buyer_rating || (int) $sellerUser->id === (int) $order->buyer_id) {
                return;
            }

            $this->send(
                $sellerUser,
                $this->title((int) $order->buyer_rating, 'ร้าน'),
                $this->body('ออเดอร์ #'.$order->order_number, $order->buyer_review),
                $this->safeRoute('taladsod.seller.orders.show', $order),
                [
                    'event' => 'fresh_market_review',
                    'source' => 'fresh-market',
                    'screen' => 'merchant-order',
                    'role' => 'seller',
                    'order_id' => (int) $order->id,
                    'order_number' => $order->order_number,
                    'rating' => (int) $order->buyer_rating,
                    'channel' => 'orders',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('SellerReviewNotifier: fresh market review failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    // ===== ภายใน =====

    protected function title(int $stars, string $what): string
    {
        $stars = max(1, min(5, $stars));

        return "⭐ ลูกค้ารีวิว{$what} {$stars} ดาว";
    }

    protected function body(string $subject, ?string $comment): string
    {
        $comment = trim((string) $comment);

        if ($comment === '') {
            return "{$subject} ได้รับคะแนนใหม่ แตะเพื่อดูรายละเอียด";
        }

        return $subject.': "'.mb_strimwidth($comment, 0, 90, '…').'"';
    }

    protected function safeRoute(string $name, mixed $params = []): ?string
    {
        try {
            return route($name, $params);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function send(User $user, string $title, string $message, ?string $url, array $data): void
    {
        $data = array_filter(['type' => self::TYPE] + $data, fn ($v) => $v !== null);

        $callback = function () use ($user, $title, $message, $url, $data) {
            try {
                app(NotificationService::class)->create($user, self::TYPE, $title, $message, $data, $url, 'ดูรีวิว', 'normal');
            } catch (\Throwable $e) {
                Log::warning('SellerReviewNotifier: create notification failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        };

        try {
            DB::afterCommit($callback);
        } catch (\Throwable $e) {
            $callback();
        }
    }
}
