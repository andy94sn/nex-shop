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
use Modules\Commerce\Models\ShippingRegion;
use Modules\Commerce\Services\CheckoutService;
use Modules\Interactions\Services\CartService;
use Modules\Commerce\Mail\OrderConfirmationMail;

/**
 * PlaceOrderMutation — validates cart, coupon, stock; creates Order + OrderItems;
 * sends order-confirmation email; clears cart + checkout session (Task 22).
 */
class PlaceOrderMutation
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $input     = $args['input'];

        // --- 1. Load cart ---
        $cartItems = $this->cart->get($sessionId);
        if (empty($cartItems)) {
            throw new UserError('Coșul de cumpărături este gol.');
        }

        // --- 2. Load checkout session ---
        $session = $this->checkout->getSession($sessionId);

        // --- 3. Resolve shipping region ---
        $shippingRegionId = $input['shipping_region_id'] ?? $session['shipping_region_id'] ?? null;
        $shippingCost     = 0.0;
        if ($shippingRegionId) {
            $region = ShippingRegion::find($shippingRegionId);
            if (! $region) {
                throw new UserError('Regiunea de livrare selectată nu este validă.');
            }
            $shippingCost = (float) $region->shipping_cost;
        }

        // --- 4. Validate & lock cart items (check stock) ---
        $articles = array_column($cartItems, null, 'article');
        $productSlugs = array_keys($articles);

        $products = Product::whereIn('article', $productSlugs)
            ->get()
            ->keyBy('article');

        foreach ($cartItems as $item) {
            $product = $products->get($item['article']);
            if (! $product) {
                throw new UserError("Produsul '{$item['article']}' nu mai este disponibil.");
            }
            if ($product->stock !== null && $product->stock < $item['quantity']) {
                throw new UserError(
                    "Stoc insuficient pentru '{$product->getTranslation('title', app()->getLocale())}'. Disponibil: {$product->stock}."
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
            if (! $coupon || ! $coupon->isValid()) {
                throw new UserError('Cuponul de reducere nu este valid sau a expirat.');
            }
            $couponId       = $coupon->id;
            $couponDiscount = match ($coupon->type) {
                'percentage' => round($subtotal * $coupon->value / 100, 2),
                'fixed'      => min((float) $coupon->value, $subtotal),
                default      => 0.0,
            };
        }

        $total = max(0.0, $subtotal - $couponDiscount + $shippingCost);

        // --- 6. Payment method ---
        $paymentMethod = $input['payment_method'] ?? $session['payment_method'] ?? 'cash';
        $creditPlanId  = $input['credit_plan_id']  ?? $session['credit_plan_id']  ?? null;
        $creditExtras  = $input['credit_extras']   ?? $session['credit_extras']   ?? [];

        // --- 7. IDNP / birth_date required for credit ---
        if ($paymentMethod === 'credit') {
            if (empty($input['idnp']) || empty($input['birth_date'])) {
                throw new UserError('IDNP-ul și data nașterii sunt obligatorii pentru comanda în credit.');
            }
            if (! preg_match('/^\d{13}$/', $input['idnp'])) {
                throw new UserError('IDNP-ul trebuie să conțină exact 13 cifre.');
            }
        }

        // --- 8. Create order inside transaction ---
        $order = DB::transaction(function () use (
            $cartItems, $products, $input, $paymentMethod, $creditPlanId, $creditExtras,
            $shippingRegionId, $shippingCost, $couponId, $couponDiscount,
            $subtotal, $total
        ) {
            $order = Order::create([
                'status'                 => 'pending',
                'payment_method'         => $paymentMethod,
                'credit_plan_id'         => $creditPlanId,
                'credit_extras_selected' => $creditExtras ?: null,
                'contact_name'           => $input['contact_name'],
                'contact_email'          => $input['contact_email'],
                'contact_phone'          => $input['contact_phone'],
                'shipping_region_id'     => $shippingRegionId,
                'shipping_address'       => $input['shipping_address'] ?? null,
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
                $product = $products->get($item['article']);
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
                if ($product->stock !== null) {
                    $product->decrement('stock', $item['quantity']);
                }
            }

            // Mark coupon as used (increment usage)
            if ($couponId) {
                Coupon::where('id', $couponId)->increment('times_used');
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
