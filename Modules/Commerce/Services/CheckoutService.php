<?php

declare(strict_types=1);

namespace Modules\Commerce\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Commerce\Models\Coupon;
use Modules\Commerce\Models\ShippingRegion;
use Modules\Interactions\Services\CartService;

/**
 * Handles checkout session state and total calculation (Tasks 21b / 21c).
 */
class CheckoutService
{
    public function __construct(private readonly CartService $cart) {}

    private function sessionKey(string $sessionId): string
    {
        return "checkout:{$sessionId}";
    }

    public function getSession(string $sessionId): array
    {
        return Cache::get($this->sessionKey($sessionId), [
            'payment_method'     => 'cash',
            'credit_plan_id'     => null,
            'credit_extras'      => [],
            'shipping_region_id' => null,
            'shipping_address'   => null,
            'coupon_code'        => null,
        ]);
    }

    public function setPaymentMethod(string $sessionId, string $method, ?int $planId = null, array $extras = []): array
    {
        $session = $this->getSession($sessionId);
        $session['payment_method']  = $method;
        $session['credit_plan_id']  = $planId;
        $session['credit_extras']   = $extras;
        Cache::put($this->sessionKey($sessionId), $session, 2592000);

        return $this->calculateTotals($sessionId);
    }

    public function setShipping(string $sessionId, int $regionId, ?string $address = null): array
    {
        $session = $this->getSession($sessionId);
        $session['shipping_region_id'] = $regionId;
        $session['shipping_address']   = $address;
        Cache::put($this->sessionKey($sessionId), $session, 2592000);

        return $this->calculateTotals($sessionId);
    }

    public function applyCoupon(string $sessionId, string $code): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (! $coupon || ! $coupon->isValid()) {
            return ['success' => false, 'error' => 'Cuponul este invalid sau expirat.'];
        }

        $session               = $this->getSession($sessionId);
        $session['coupon_code'] = strtoupper($code);
        Cache::put($this->sessionKey($sessionId), $session, 2592000);

        return array_merge(['success' => true], $this->calculateTotals($sessionId));
    }

    public function removeCoupon(string $sessionId): array
    {
        $session                = $this->getSession($sessionId);
        $session['coupon_code'] = null;
        Cache::put($this->sessionKey($sessionId), $session, 2592000);

        return $this->calculateTotals($sessionId);
    }

    public function calculateTotals(string $sessionId): array
    {
        $session  = $this->getSession($sessionId);
        $cartItems = $this->cart->get($sessionId);

        // Subtotal from cart snapshots
        $subtotal = array_reduce($cartItems, fn ($carry, $item) =>
            $carry + ($item['price'] * $item['quantity']), 0.0);

        // Coupon discount
        $discount = 0.0;
        if ($session['coupon_code']) {
            $coupon = Coupon::where('code', $session['coupon_code'])->first();
            if ($coupon && $coupon->isValid()) {
                $discount = match($coupon->type) {
                    'percentage' => round($subtotal * $coupon->value / 100, 2),
                    'fixed'      => min((float) $coupon->value, $subtotal),
                    default      => 0.0,
                };
            }
        }

        // Shipping
        $shippingCost = 0.0;
        if ($session['shipping_region_id']) {
            $region = ShippingRegion::find($session['shipping_region_id']);
            $shippingCost = $region ? (float) $region->shipping_cost : 0.0;
        }

        $total = max(0.0, $subtotal - $discount + $shippingCost);

        return [
            'subtotal'      => round($subtotal, 2),
            'discount'      => round($discount, 2),
            'shipping_cost' => round($shippingCost, 2),
            'total'         => round($total, 2),
        ];
    }

    public function clearSession(string $sessionId): void
    {
        Cache::forget($this->sessionKey($sessionId));
    }
}
