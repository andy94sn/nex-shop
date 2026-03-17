<?php

declare(strict_types=1);

namespace Modules\Commerce\Services;

use GraphQL\Error\UserError;
use Illuminate\Support\Facades\Cache;
use Modules\Commerce\Models\Coupon;
use Modules\Commerce\Services\ShippingZoneService;
use Modules\Interactions\Services\CartService;
use Modules\Settings\Models\SiteSettings;

/**
 * Handles checkout session state and total calculation (Tasks 21b / 21c).
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly ShippingZoneService $shippingZones,
    ) {}

    private function sessionKey(string $sessionId): string
    {
        return "checkout:{$sessionId}";
    }

    public function getSession(string $sessionId): array
    {
        $existing = Cache::get($this->sessionKey($sessionId));

        if ($existing !== null) {
            return $existing;
        }

        // Bootstrap a fresh checkout session, pre-seeding the default country
        // from SiteSettings so shipping can be calculated without a country selector.
        $defaultCountryId = SiteSettings::instance()->default_country_id;
        $defaultZoneId    = $defaultCountryId
            ? $this->shippingZones->matchZone($defaultCountryId, null)?->id
            : null;

        $session = [
            'payment_method_id'  => null,
            'delivery_method_id' => null,
            'credit_plan_id'     => null,
            'credit_extras'      => [],
            'shipping_zone_id'   => $defaultZoneId,
            'country_id'         => $defaultCountryId,
            'city_id'            => null,
            'shipping_address'   => null,
            'coupon_code'        => null,
            'terms_accepted'     => false,
        ];

        Cache::put($this->sessionKey($sessionId), $session, 2592000);

        return $session;
    }

    // ── Setters ───────────────────────────────────────────────────────────

    public function setPaymentMethod(string $sessionId, int $paymentMethodId, ?int $planId = null, array $extras = []): array
    {
        $session = $this->getSession($sessionId);
        $session['payment_method_id'] = $paymentMethodId;
        $session['credit_plan_id']    = $planId;
        $session['credit_extras']     = $extras;
        Cache::put($this->sessionKey($sessionId), $session, 2592000);

        return $this->calculateTotals($sessionId);
    }

    public function setDeliveryMethod(string $sessionId, int $deliveryMethodId): array
    {
        $session = $this->getSession($sessionId);
        $session['delivery_method_id'] = $deliveryMethodId;
        Cache::put($this->sessionKey($sessionId), $session, 2592000);

        return $this->calculateTotals($sessionId);
    }

    /**
     * Set the customer's shipping location.
     *
     * The matching ShippingZone is resolved immediately and stored so that
     * calculateTotals() doesn't need to re-run zone matching every call.
     *
     * @param int        $countryId  Selected Country id
     * @param int|null   $cityId     Selected City id (optional, for city-level zones)
     * @param array|null $address    Structured address fields (see ShippingAddressInput)
     */
    public function setShipping(
        string  $sessionId,
        int     $countryId,
        ?int    $cityId   = null,
        ?array  $address  = null,
    ): array {
        $zone = $this->shippingZones->matchZone($countryId, $cityId);

        $session = $this->getSession($sessionId);
        $session['country_id']        = $countryId;
        $session['city_id']           = $cityId;
        $session['shipping_address']  = $address;
        $session['shipping_zone_id']  = $zone?->id;
        Cache::put($this->sessionKey($sessionId), $session, 2592000);

        return $this->calculateTotals($sessionId);
    }

    public function applyCoupon(string $sessionId, string $code): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (! $coupon || ! $coupon->isValid()) {
            throw new UserError('Cuponul este invalid sau expirat.');
        }

        // Save the code regardless of whether the min_order_value is met yet.
        // calculateTotals() will apply the discount only when the cart meets the requirement.
        $session                = $this->getSession($sessionId);
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

    public function setTermsAccepted(string $sessionId, bool $accepted): void
    {
        $session                   = $this->getSession($sessionId);
        $session['terms_accepted'] = $accepted;
        Cache::put($this->sessionKey($sessionId), $session, 2592000);
    }

    // ── Totals ────────────────────────────────────────────────────────────

    public function calculateTotals(string $sessionId): array
    {
        $session   = $this->getSession($sessionId);
        $cartItems = $this->cart->get($sessionId);

        // Subtotal from cart snapshots
        $subtotal = array_reduce($cartItems, fn($carry, $item) =>
        $carry + ($item['price'] * $item['quantity']), 0.0);

        // Coupon discount — silently 0 if coupon is no longer valid or min_order_value not met
        $discount = 0.0;
        if ($session['coupon_code']) {
            $coupon = Coupon::where('code', $session['coupon_code'])->first();
            if ($coupon) {
                $discount = $coupon->discountFor($subtotal);
            }
        }

        // Shipping — resolved via zone service using country/city + method IDs
        $shippingCost = 0.0;
        if ($session['country_id']) {
            $afterDiscount = max(0.0, $subtotal - $discount);
            $shippingCost  = $this->shippingZones->resolve(
                countryId: $session['country_id'] ? (int) $session['country_id'] : null,
                cityId: $session['city_id']    ? (int) $session['city_id']    : null,
                cartTotal: $afterDiscount,
                deliveryMethodId: $session['delivery_method_id'] ? (int) $session['delivery_method_id'] : null,
                paymentMethodId: $session['payment_method_id']  ? (int) $session['payment_method_id']  : null,
            );
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
