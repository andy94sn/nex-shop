<?php

declare(strict_types=1);

namespace Modules\Commerce\Services;

use Modules\Commerce\Models\ShippingZone;

/**
 * Resolves the applicable shipping cost for a given geographic location
 * (country + optional city) and cart context.
 *
 * Zone matching (WooCommerce-style, most-specific first):
 *   1. Zone that explicitly includes the customer's city.
 *   2. Zone that includes the customer's country (whole-country coverage).
 *   3. Catch-all zone (no country and no city entries).
 *
 * Within the matched zone the service walks rates in sort order;
 * the first matching rate wins.  Falls back to zone.default_cost.
 */
class ShippingZoneService
{
    /**
     * @param int|null    $countryId        Customer's country id
     * @param int|null    $cityId           Customer's city id
     * @param float       $cartTotal        Subtotal after coupon discount
     * @param int|null    $deliveryMethodId Selected DeliveryMethod id
     * @param int|null    $paymentMethodId  Selected PaymentMethod id
     */
    public function resolve(
        ?int   $countryId,
        ?int   $cityId,
        float  $cartTotal,
        ?int   $deliveryMethodId = null,
        ?int   $paymentMethodId  = null,
    ): float {
        $zone = $this->matchZone($countryId, $cityId);

        if (! $zone) {
            return 0.0;
        }

        // Walk rates (already ordered by sort, is_active filtered on relation).
        foreach ($zone->rates as $rate) {
            if ($rate->matches($cartTotal, $deliveryMethodId, $paymentMethodId)) {
                return $rate->is_free ? 0.0 : (float) $rate->cost;
            }
        }

        return (float) $zone->default_cost;
    }

    /**
     * Find the best-matching zone for the customer's location.
     */
    public function matchZone(?int $countryId, ?int $cityId): ?ShippingZone
    {
        // Eager-load geography + rates in one query set.
        $zones = ShippingZone::with(['countries', 'cities', 'rates'])
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        // 1️⃣  City-level match (most specific).
        if ($cityId !== null) {
            foreach ($zones as $zone) {
                if ($zone->cities->contains('id', $cityId)) {
                    return $zone;
                }
            }
        }

        // 2️⃣  Country-level match.
        if ($countryId !== null) {
            foreach ($zones as $zone) {
                if ($zone->countries->contains('id', $countryId)) {
                    return $zone;
                }
            }
        }

        // 3️⃣  Catch-all zone (no country, no city entries).
        foreach ($zones as $zone) {
            if ($zone->countries->isEmpty() && $zone->cities->isEmpty()) {
                return $zone;
            }
        }

        return null;
    }
}
