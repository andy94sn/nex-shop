<?php

declare(strict_types=1);

namespace Modules\Commerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Commerce\Models\City;
use Modules\Commerce\Models\Country;
use Modules\Commerce\Models\DeliveryMethod;
use Modules\Commerce\Models\PaymentMethod;
use Modules\Commerce\Models\ShippingRate;
use Modules\Commerce\Models\ShippingZone;

/**
 * Seeds countries, cities, and shipping zones.
 *
 * Zone strategy (WooCommerce-style):
 *   Zone 1 — Chișinău city        (city-level, most specific)
 *   Zone 2 — Moldova nationwide   (country-level)
 *   Zone 3 — Romania              (country-level)
 *   Zone 4 — Everywhere else      (catch-all — no geo entries)
 *
 * Must run after DeliveryMethodsSeeder and PaymentMethodsSeeder.
 */
class ShippingZonesSeeder extends Seeder
{
    public function run(): void
    {
        // ── Resolve method IDs ─────────────────────────────────────────────
        $shipping = DeliveryMethod::where('code', 'shipping')->value('id');
        $pickup   = DeliveryMethod::where('code', 'pickup')->value('id');
        $credit   = PaymentMethod::where('code', 'credit')->value('id');

        // ── Countries ──────────────────────────────────────────────────────
        $moldova = Country::updateOrCreate(['code' => 'MD'], [
            'name'      => ['ro' => 'Moldova', 'ru' => 'Молдова', 'en' => 'Moldova'],
            'is_active' => true,
            'sort'      => 1,
        ]);

        $romania = Country::updateOrCreate(['code' => 'RO'], [
            'name'      => ['ro' => 'România', 'ru' => 'Румыния', 'en' => 'Romania'],
            'is_active' => true,
            'sort'      => 2,
        ]);

        $ukraine = Country::updateOrCreate(['code' => 'UA'], [
            'name'      => ['ro' => 'Ucraina', 'ru' => 'Украина', 'en' => 'Ukraine'],
            'is_active' => true,
            'sort'      => 3,
        ]);

        // ── Cities — Moldova ───────────────────────────────────────────────
        $chisinau = City::updateOrCreate(
            ['country_id' => $moldova->id, 'name->ro' => 'Chișinău'],
            [
                'name'      => ['ro' => 'Chișinău', 'ru' => 'Кишинёв',  'en' => 'Chișinău'],
                'is_active' => true,
                'sort'      => 1,
            ]
        );

        City::updateOrCreate(
            ['country_id' => $moldova->id, 'name->ro' => 'Bălți'],
            [
                'name'      => ['ro' => 'Bălți', 'ru' => 'Бэлць', 'en' => 'Bălți'],
                'is_active' => true,
                'sort'      => 2,
            ]
        );

        City::updateOrCreate(
            ['country_id' => $moldova->id, 'name->ro' => 'Cahul'],
            [
                'name'      => ['ro' => 'Cahul', 'ru' => 'Кагул', 'en' => 'Cahul'],
                'is_active' => true,
                'sort'      => 3,
            ]
        );

        City::updateOrCreate(
            ['country_id' => $moldova->id, 'name->ro' => 'Ungheni'],
            [
                'name'      => ['ro' => 'Ungheni', 'ru' => 'Унгены', 'en' => 'Ungheni'],
                'is_active' => true,
                'sort'      => 4,
            ]
        );

        // ── Cities — Romania ───────────────────────────────────────────────
        City::updateOrCreate(
            ['country_id' => $romania->id, 'name->ro' => 'București'],
            [
                'name'      => ['ro' => 'București', 'ru' => 'Бухарест', 'en' => 'Bucharest'],
                'is_active' => true,
                'sort'      => 1,
            ]
        );

        City::updateOrCreate(
            ['country_id' => $romania->id, 'name->ro' => 'Iași'],
            [
                'name'      => ['ro' => 'Iași', 'ru' => 'Яссы', 'en' => 'Iași'],
                'is_active' => true,
                'sort'      => 2,
            ]
        );

        City::updateOrCreate(
            ['country_id' => $romania->id, 'name->ro' => 'Cluj-Napoca'],
            [
                'name'      => ['ro' => 'Cluj-Napoca', 'ru' => 'Клуж-Напока', 'en' => 'Cluj-Napoca'],
                'is_active' => true,
                'sort'      => 3,
            ]
        );

        // ── Cities — Ukraine ───────────────────────────────────────────────
        City::updateOrCreate(
            ['country_id' => $ukraine->id, 'name->ro' => 'Kiev'],
            [
                'name'      => ['ro' => 'Kiev', 'ru' => 'Киев', 'en' => 'Kyiv'],
                'is_active' => true,
                'sort'      => 1,
            ]
        );

        // ── Zone 1 — Chișinău (city-level, cheapest delivery) ─────────────
        $zoneCapital = ShippingZone::create([
            'name'         => ['ro' => 'Chișinău', 'ru' => 'Кишинёв',  'en' => 'Chișinău'],
            'default_cost' => 80.00,
            'is_active'    => true,
            'sort'         => 1,
        ]);
        $zoneCapital->cities()->sync([$chisinau->id]);

        // ── Chișinău rates (sort order = evaluation priority, first match wins) ──

        // 1. Free pickup — always free regardless of cart total
        ShippingRate::create([
            'shipping_zone_id'   => $zoneCapital->id,
            'delivery_method_id' => $pickup,
            'payment_method_id'  => null,
            'cart_min'           => null,
            'cart_max'           => null,
            'cost'               => 0,
            'is_free'            => true,
            'label'              => ['ro' => 'Ridicare gratuită', 'en' => 'Free pickup'],
            'sort'               => 1,
        ]);

        // 2. Free delivery when cart >= 1500 MDL
        ShippingRate::create([
            'shipping_zone_id'   => $zoneCapital->id,
            'delivery_method_id' => $shipping,
            'payment_method_id'  => null,
            'cart_min'           => 1500.00,
            'cart_max'           => null,
            'cost'               => 0,
            'is_free'            => true,
            'label'              => ['ro' => 'Livrare gratuită (≥ 1500 MDL)', 'en' => 'Free delivery (≥ 1500 MDL)'],
            'sort'               => 2,
        ]);

        // 3. Reduced delivery 14 MDL when cart 300–1499.99 MDL
        ShippingRate::create([
            'shipping_zone_id'   => $zoneCapital->id,
            'delivery_method_id' => $shipping,
            'payment_method_id'  => null,
            'cart_min'           => 300.00,
            'cart_max'           => 1499.99,
            'cost'               => 14.00,
            'is_free'            => false,
            'label'              => ['ro' => 'Livrare redusă (300–1499 MDL)', 'en' => 'Reduced delivery (300–1499 MDL)'],
            'sort'               => 3,
        ]);

        // 4. Standard delivery 80 MDL when cart 0–299.99 MDL
        ShippingRate::create([
            'shipping_zone_id'   => $zoneCapital->id,
            'delivery_method_id' => $shipping,
            'payment_method_id'  => null,
            'cart_min'           => null,
            'cart_max'           => 299.99,
            'cost'               => 80.00,
            'is_free'            => false,
            'label'              => ['ro' => 'Livrare standard (0–299 MDL)', 'en' => 'Standard delivery (0–299 MDL)'],
            'sort'               => 4,
        ]);

        // ── Zone 2 — Moldova (whole country) ──────────────────────────────
        $zoneMoldova = ShippingZone::create([
            'name'         => ['ro' => 'Moldova (națională)', 'ru' => 'Молдова (по стране)', 'en' => 'Moldova (nationwide)'],
            'default_cost' => 80.00,
            'is_active'    => true,
            'sort'         => 2,
        ]);
        $zoneMoldova->countries()->sync([$moldova->id]);

        // Free pickup
        ShippingRate::create([
            'shipping_zone_id'   => $zoneMoldova->id,
            'delivery_method_id' => $pickup,
            'payment_method_id'  => null,
            'cart_min'           => null,
            'cart_max'           => null,
            'cost'               => 0,
            'is_free'            => true,
            'label'              => ['ro' => 'Ridicare gratuită', 'en' => 'Free pickup'],
            'sort'               => 1,
        ]);

        // Free delivery over 2000 MDL
        ShippingRate::create([
            'shipping_zone_id'   => $zoneMoldova->id,
            'delivery_method_id' => $shipping,
            'payment_method_id'  => null,
            'cart_min'           => 2000.00,
            'cart_max'           => null,
            'cost'               => 0,
            'is_free'            => true,
            'label'              => ['ro' => 'Livrare gratuită (2000+ MDL)', 'en' => 'Free delivery (2000+ MDL)'],
            'sort'               => 2,
        ]);

        // Standard Moldova delivery — 80 MDL
        ShippingRate::create([
            'shipping_zone_id'   => $zoneMoldova->id,
            'delivery_method_id' => $shipping,
            'payment_method_id'  => null,
            'cart_min'           => null,
            'cart_max'           => null,
            'cost'               => 80.00,
            'is_free'            => false,
            'label'              => ['ro' => 'Livrare standard Moldova', 'en' => 'Standard Moldova delivery'],
            'sort'               => 3,
        ]);

        // ── Zone 3 — Romania ──────────────────────────────────────────────
        $zoneRomania = ShippingZone::create([
            'name'         => ['ro' => 'România', 'ru' => 'Румыния', 'en' => 'Romania'],
            'default_cost' => 150.00,
            'is_active'    => true,
            'sort'         => 3,
        ]);
        $zoneRomania->countries()->sync([$romania->id]);

        // Free delivery with credit over 3000 MDL
        ShippingRate::create([
            'shipping_zone_id'   => $zoneRomania->id,
            'delivery_method_id' => $shipping,
            'payment_method_id'  => $credit,
            'cart_min'           => 3000.00,
            'cart_max'           => null,
            'cost'               => 0,
            'is_free'            => true,
            'label'              => ['ro' => 'Livrare gratuită cu credit (3000+ MDL)', 'en' => 'Free delivery with credit (3000+ MDL)'],
            'sort'               => 1,
        ]);

        // Standard Romania delivery — 150 MDL
        ShippingRate::create([
            'shipping_zone_id'   => $zoneRomania->id,
            'delivery_method_id' => $shipping,
            'payment_method_id'  => null,
            'cart_min'           => null,
            'cart_max'           => null,
            'cost'               => 150.00,
            'is_free'            => false,
            'label'              => ['ro' => 'Livrare în România', 'en' => 'Delivery to Romania'],
            'sort'               => 2,
        ]);

        // ── Zone 4 — Everywhere else (catch-all, no geo entries) ──────────
        $zoneGlobal = ShippingZone::create([
            'name'         => ['ro' => 'Internațional', 'ru' => 'Международный', 'en' => 'International'],
            'default_cost' => 300.00,
            'is_active'    => true,
            'sort'         => 99,
        ]);

        ShippingRate::create([
            'shipping_zone_id'   => $zoneGlobal->id,
            'delivery_method_id' => $shipping,
            'payment_method_id'  => null,
            'cart_min'           => null,
            'cart_max'           => null,
            'cost'               => 300.00,
            'is_free'            => false,
            'label'              => ['ro' => 'Livrare internațională', 'en' => 'International delivery'],
            'sort'               => 1,
        ]);
    }
}
