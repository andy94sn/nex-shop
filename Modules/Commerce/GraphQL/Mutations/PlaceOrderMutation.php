<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Mutations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Error\UserError;
use Modules\Catalog\Models\Product;
use Modules\Commerce\Models\Coupon;
use Modules\Commerce\Models\Order;
use Modules\Commerce\Models\OrderItem;
use Modules\Commerce\Models\PaymentMethod;
use Modules\Commerce\Services\CheckoutService;
use Modules\Commerce\Services\ShippingZoneService;
use Modules\Interactions\Services\CartService;
use Modules\Commerce\Mail\OrderConfirmationMail;
use Modules\Settings\Models\SiteSettings;
use Modules\Commerce\GraphQL\Concerns\ResolvesSessionId;

/**
 * PlaceOrderMutation — validates cart, coupon, stock; creates Order + OrderItems;
 * sends order-confirmation email; clears cart + checkout session (Task 22).
 */
class PlaceOrderMutation
{
    use ResolvesSessionId;

    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
        private readonly ShippingZoneService $shippingZones,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = $this->sessionId();
        $input     = $args['input'];

        // --- 1. Load cart ---
        $cartItems = $this->cart->get($sessionId);
        if (empty($cartItems)) {
            throw new UserError('Coșul de cumpărături este gol.');
        }

        // --- 2. Load checkout session ---
        $session = $this->checkout->getSession($sessionId);

        // Terms must be accepted
        if (empty($session['terms_accepted'])) {
            throw new UserError('Trebuie să acceptați termenii și condițiile pentru a plasa comanda.');
        }

        // --- 3. Resolve shipping zone + cost ---
        $countryId        = $session['country_id']         ? (int) $session['country_id']         : null;
        $cityId           = $session['city_id']            ? (int) $session['city_id']            : null;
        $shippingZoneId   = $session['shipping_zone_id']   ? (int) $session['shipping_zone_id']   : null;
        $deliveryMethodId = $session['delivery_method_id'] ? (int) $session['delivery_method_id'] : null;
        $paymentMethodId  = $session['payment_method_id']  ? (int) $session['payment_method_id']  : null;
        $shippingCost     = 0.0;
        if ($countryId) {
            $subtotalPreview = array_reduce($cartItems, fn ($c, $i) => $c + ($i['price'] * $i['quantity']), 0.0);
            $shippingCost    = $this->shippingZones->resolve(
                countryId:        $countryId,
                cityId:           $cityId,
                cartTotal:        $subtotalPreview,
                deliveryMethodId: $deliveryMethodId,
                paymentMethodId:  $paymentMethodId,
            );
        }

        // --- 4. Validate & lock cart items (check stock) ---
        $products = Product::whereIn('id', array_column($cartItems, 'product_id'))
            ->get()
            ->keyBy('id');

        $allowBackorder = SiteSettings::instance()->allow_backorder;

        foreach ($cartItems as $item) {
            $product = $products->get($item['product_id']);
            if (! $product) {
                throw new UserError("Produsul '{$item['article']}' nu mai este disponibil.");
            }
            if (! $allowBackorder && $product->hasStockConflict($item['quantity'])) {
                throw new UserError(
                    "Stoc insuficient pentru '{$product->getTranslation('title', app()->getLocale())}'. Disponibil: {$product->quantity}."
                );
            }
        }

        // --- 5. Resolve coupon ---
        $couponCode     = $input['coupon_code'] ?? $session['coupon_code'] ?? null;
        $couponId       = null;
        $couponDiscount = 0.0;
        $subtotal       = array_reduce($cartItems, fn ($carry, $item) =>
            $carry + ($item['price'] * $item['quantity']), 0.0);

        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();

            // Re-validate freshly at checkout time
            if (! $coupon || ! $coupon->isValid()) {
                throw new UserError('Cuponul de reducere nu mai este valid sau a expirat.');
            }

            // Check min_order_value against the actual subtotal
            if (! $coupon->meetsMinOrder($subtotal)) {
                throw new UserError(
                    "Cuponul necesită o comandă minimă de {$coupon->min_order_value}."
                );
            }

            $couponId       = $coupon->id;
            $couponDiscount = $coupon->discountFor($subtotal);
        }

        $total = max(0.0, $subtotal - $couponDiscount + $shippingCost);

        // --- 6. Credit plan / extras ---
        $creditPlanId = $input['credit_plan_id']  ?? $session['credit_plan_id']  ?? null;
        $creditExtras = $input['credit_extras']   ?? $session['credit_extras']   ?? [];

        // --- 7. IDNP / birth_date required for credit ---
        // Resolve the PaymentMethod model to check if it requires a credit plan
        $requiresCredit = $paymentMethodId
            ? PaymentMethod::find($paymentMethodId)?->requires_credit_plan
            : false;
        if ($requiresCredit) {
            if (empty($input['idnp']) || empty($input['birth_date'])) {
                throw new UserError('IDNP-ul și data nașterii sunt obligatorii pentru comanda în credit.');
            }
            if (! preg_match('/^\d{13}$/', $input['idnp'])) {
                throw new UserError('IDNP-ul trebuie să conțină exact 13 cifre.');
            }
        }

        // --- 8. Create order inside transaction ---
        $order = DB::transaction(function () use (
            $cartItems, $products, $input, $paymentMethodId, $deliveryMethodId,
            $creditPlanId, $creditExtras, $shippingZoneId, $shippingCost,
            $couponId, $couponDiscount, $subtotal, $total
        ) {
            $order = Order::create([
                'status'                 => 'pending',
                'payment_method_id'      => $paymentMethodId,
                'delivery_method_id'     => $deliveryMethodId,
                'credit_plan_id'         => $creditPlanId,
                'credit_extras_selected' => $creditExtras ?: null,
                'contact_name'           => $input['contact_name'],
                'contact_email'          => $input['contact_email'],
                'contact_phone'          => $input['contact_phone'],
                'shipping_zone_id'       => $shippingZoneId,
                'shipping_address'       => isset($input['shipping_address'])
                    ? json_encode($input['shipping_address'])
                    : (isset($session['shipping_address']) ? json_encode($session['shipping_address']) : null),
                'subtotal'               => round($subtotal, 2),
                'discount'               => round($couponDiscount, 2),
                'shipping_cost'          => round($shippingCost, 2),
                'total'                  => round($total, 2),
                'coupon_id'              => $couponId,
                'coupon_discount'        => round($couponDiscount, 2),
                'idnp'                   => $input['idnp'] ?? null,
                'birth_date'             => $input['birth_date'] ?? null,
                'notes'                  => $input['notes'] ?? null,
            ]);

            foreach ($cartItems as $item) {
                $product = $products->get($item['product_id']);
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'article'    => $item['article'],
                    'title'      => $item['title'],
                    'price'      => $item['price'],
                    'quantity'   => $item['quantity'],
                    'total'      => round($item['price'] * $item['quantity'], 2),
                ]);

                // Decrement stock if tracked
                if ($product->quantity !== null) {
                    $product->decrement('quantity', $item['quantity']);
                }
            }

            // Mark coupon as used
            if ($couponId) {
                Coupon::where('id', $couponId)->increment('used_count');
            }

            return $order;
        });

        // --- 9. Send confirmation email ---
        try {
            Mail::to($order->contact_email)
                ->queue(new OrderConfirmationMail($order->load('items')));
        } catch (\Throwable) {
            // Non-fatal — order already placed
        }

        // --- 10. Clear cart + checkout session ---
        $this->cart->clear($sessionId);
        $this->checkout->clearSession($sessionId);

        return [
            'order_number' => $order->order_number,
            'total'        => $order->total,
            'status'       => $order->status,
        ];
    }
}
