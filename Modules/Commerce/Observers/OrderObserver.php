<?php

declare(strict_types=1);

namespace Modules\Commerce\Observers;

use Modules\Commerce\Models\Coupon;
use Modules\Commerce\Models\Order;

/**
 * Keeps coupon usage counts in sync with order lifecycle.
 *
 * - Increment used_count when an order is placed (done inline in PlaceOrderMutation).
 * - Decrement used_count when an order is cancelled or refunded so the coupon
 *   slot becomes available again.
 */
class OrderObserver
{
    private const REVERSIBLE_STATUSES = ['cancelled', 'refunded'];

    public function updated(Order $order): void
    {
        // Only act when the status field itself changed
        if (! $order->wasChanged('status')) {
            return;
        }

        $newStatus = $order->status;
        $oldStatus = $order->getOriginal('status');

        $isReversing = in_array($newStatus, self::REVERSIBLE_STATUSES, true);
        $wasAlreadyReversed = in_array($oldStatus, self::REVERSIBLE_STATUSES, true);

        // Decrement when moving INTO a reversible status (only once)
        if ($isReversing && ! $wasAlreadyReversed && $order->coupon_id) {
            Coupon::where('id', $order->coupon_id)
                ->where('used_count', '>', 0)
                ->decrement('used_count');
        }

        // Re-increment if a cancelled/refunded order is somehow reinstated
        if (! $isReversing && $wasAlreadyReversed && $order->coupon_id) {
            Coupon::where('id', $order->coupon_id)->increment('used_count');
        }
    }
}
