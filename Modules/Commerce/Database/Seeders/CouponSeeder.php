<?php

declare(strict_types=1);

namespace Modules\Commerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Commerce\Models\Coupon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        // Clean start for local/dev seeding
        DB::table('coupons')->whereIn('code', [
            'SAVE10', 'SAVE50', 'MIN100', 'EXPIRED', 'LIMITED',
        ])->delete();

        // Percentage discount (10%)
        Coupon::create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10.0,
            'min_order_value' => null,
            'max_uses' => null,
            'used_count' => 0,
            'valid_from' => Carbon::now()->subDays(1),
            'valid_until' => Carbon::now()->addMonths(1),
            'is_active' => true,
        ]);

        // Fixed amount discount (50 MDL)
        Coupon::create([
            'code' => 'SAVE50',
            'type' => 'fixed',
            'value' => 50.0,
            'min_order_value' => null,
            'max_uses' => null,
            'used_count' => 0,
            'valid_from' => Carbon::now()->subDays(1),
            'valid_until' => Carbon::now()->addMonths(1),
            'is_active' => true,
        ]);

        // Fixed amount with minimum order value
        Coupon::create([
            'code' => 'MIN100',
            'type' => 'fixed',
            'value' => 20.0,
            'min_order_value' => 100.0,
            'max_uses' => null,
            'used_count' => 0,
            'valid_from' => Carbon::now()->subDays(1),
            'valid_until' => Carbon::now()->addMonths(1),
            'is_active' => true,
        ]);

        // Expired coupon
        Coupon::create([
            'code' => 'EXPIRED',
            'type' => 'percentage',
            'value' => 15.0,
            'min_order_value' => null,
            'max_uses' => null,
            'used_count' => 0,
            'valid_from' => Carbon::now()->subMonths(2),
            'valid_until' => Carbon::now()->subMonth(),
            'is_active' => false,
        ]);

        // Limited uses coupon
        Coupon::create([
            'code' => 'LIMITED',
            'type' => 'fixed',
            'value' => 30.0,
            'min_order_value' => null,
            'max_uses' => 2,
            'used_count' => 0,
            'valid_from' => Carbon::now()->subDays(1),
            'valid_until' => Carbon::now()->addMonths(1),
            'is_active' => true,
        ]);
    }
}
