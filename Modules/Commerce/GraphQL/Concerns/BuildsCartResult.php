<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Concerns;

use Modules\Catalog\Models\Product;
use Modules\Commerce\Models\City;
use Modules\Commerce\Models\Country;
use Modules\Commerce\Models\CreditPlan;
use Modules\Commerce\Models\DeliveryMethod;
use Modules\Commerce\Models\PaymentMethod;
use Modules\Commerce\Models\Coupon;

/**
 * Builds the canonical CartResult array.
 *
 * Any resolver that returns CartResult must use this trait instead of
 * building the array inline.  This guarantees a single source of truth
 * for the cart shape across CartQuery, AddToCartMutation,
 * RemoveFromCartMutation, UpdateCartQuantityMutation, and ClearCartMutation.
 *
 * Consuming classes must declare:
 *   private readonly CartService     $cart;
 *   private readonly CheckoutService $checkout;
 */
trait BuildsCartResult
{
    use FormatsTotals;
    use ResolvesSessionId;

    private function buildCartResult(string $sessionId): array
    {
        // ── 1. Items ─────────────────────────────────────────────────────
        $items    = $this->cart->get($sessionId);
        $subtotal = 0.0;
        $formatted = [];

        $productIds = array_column($items, 'product_id');
        $products   = Product::with(['images' => fn ($q) => $q->where('is_main', true)])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product      = $products->get($item['product_id']);
            $currentPrice = $product?->rrp ?? $item['price'];
            $subtotal    += $item['price'] * $item['quantity'];
            $locale       = app()->getLocale();

            $formatted[] = [
                'article'        => $item['article'],
                'quantity'       => $item['quantity'],
                'snapshot_price' => $item['price'],
                'snapshot_title' => $item['title'][$locale] ?? reset($item['title']),
                'snapshot_image' => $item['image'],
                'current_price'  => $currentPrice,
                'current_stock'  => $product?->quantity,
                'current_title'  => $product?->title,
                'current_image'  => $product?->images->first()?->path,
                'rrp_old'        => $product?->rrp_old,
                'is_unavailable' => ! $product || ! $product->is_active || $product->quantity === 0,
                'stock_changed'  => $product !== null && $product->hasStockConflict($item['quantity']),
            ];
        }

        // ── 2. Checkout session ───────────────────────────────────────────
        $session  = $this->checkout->getSession($sessionId);
        $totals   = $this->checkout->calculateTotals($sessionId);
        $checkout = $this->buildCheckoutSnapshot($session, $subtotal);

        // dd($totals);

        // ── 3. Cart totals ────────────────────────────────────────────────
        $coupon     = null;
        $couponCode = $session['coupon_code'] ?? null;
        if ($couponCode) {
            $c = Coupon::where('code', $couponCode)->first();
            if ($c && $c->isValid()) {
                $isMet = $c->meetsMinOrder($subtotal);
                $coupon = [
                    'code'              => $c->code,
                    'type'              => $c->type,
                    'value'             => $this->formatAmount($c->value),
                    'discount_amount'   => $this->formatAmount($totals['discount']),
                    'min_order_value'   => $c->min_order_value ? $this->formatAmount($c->min_order_value) : null,
                    'is_met'            => $isMet,
                ];
            }
        }

        $deliveryMethodId       = $session['delivery_method_id'] ?? null;
        $shippingCostVisible    = $deliveryMethodId !== null && $totals['shipping_cost'] > 0;

        $cartTotals = [
            'subtotal'              => $this->formatAmount($totals['subtotal']),
            'discount'              => $this->formatAmount($totals['discount']),
            'shipping_cost'         => $this->formatAmount($totals['shipping_cost']),
            'shipping_cost_visible' => $shippingCostVisible,
            'total'                 => $this->formatAmount($totals['total']),
            'coupon'                => $coupon,
        ];

        // ── 4. Assemble ───────────────────────────────────────────────────
        return [
            'items'       => $formatted,
            'subtotal'    => $this->formatAmount($subtotal),
            'count'       => $this->cart->count($sessionId),
            'checkout'    => $checkout,
            'cart_totals' => $cartTotals,
        ];
    }

    private function buildCheckoutSnapshot(array $session, float $subtotal): array
    {
        $locale = app()->getLocale();

        // Delivery method
        $deliveryMethod = null;
        if ($session['delivery_method_id']) {
            $dm = DeliveryMethod::find($session['delivery_method_id']);
            if ($dm) {
                $deliveryMethod = [
                    'id'               => $dm->id,
                    'code'             => $dm->code,
                    'name'             => $dm->getTranslation('name', $locale, false),
                    'requires_address' => $dm->requires_address,
                ];
            }
        }

        // Payment method (eager-load deliveryMethods for allowed_delivery_method_ids)
        $paymentMethod = null;
        if ($session['payment_method_id']) {
            $pm = PaymentMethod::with('deliveryMethods')->find($session['payment_method_id']);
            if ($pm) {
                $paymentMethod = [
                    'id'                           => $pm->id,
                    'code'                         => $pm->code,
                    'name'                         => $pm->getTranslation('name', $locale, false),
                    'requires_credit_plan'         => $pm->requires_credit_plan,
                    'is_online'                    => $pm->is_online,
                    'allowed_delivery_method_ids'  => $pm->deliveryMethods->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
                ];
            }
        }

        // Credit plan
        $creditPlan = null;
        if ($session['credit_plan_id']) {
            $cp = CreditPlan::find($session['credit_plan_id']);
            if ($cp) {
                $creditPlan = [
                    'id'               => $cp->id,
                    'months'           => $cp->months,
                    'interest_label'   => $cp->interest_label,
                    'monthly_rate'     => $subtotal > 0 ? $cp->monthlyRate($subtotal) : null,
                    'is_zero_interest' => $cp->is_zero_interest,
                ];
            }
        }

        // Country & city
        $country = null;
        if ($session['country_id']) {
            $c = Country::find($session['country_id']);
            if ($c) {
                $country = [
                    'id'   => $c->id,
                    'code' => $c->code,
                    'name' => $c->getTranslation('name', $locale, false),
                ];
            }
        }

        $city = null;
        if ($session['city_id']) {
            $ct = City::find($session['city_id']);
            if ($ct) {
                $city = [
                    'id'   => $ct->id,
                    'name' => $ct->getTranslation('name', $locale, false),
                ];
            }
        }

        // Structured shipping address (stored as array, may be null)
        $shippingAddress = $session['shipping_address'] ?? null;

        return [
            'delivery_method'  => $deliveryMethod,
            'payment_method'   => $paymentMethod,
            'credit_plan'      => $creditPlan,
            'credit_extras'    => $session['credit_extras'] ?? [],
            'country'          => $country,
            'city'             => $city,
            'shipping_address' => $shippingAddress,
            'coupon_code'      => $session['coupon_code'],
            'terms_accepted'   => (bool) ($session['terms_accepted'] ?? false),
        ];
    }
}
