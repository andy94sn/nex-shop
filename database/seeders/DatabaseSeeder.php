<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Commerce\Database\Seeders\CouponSeeder;
use Modules\Settings\Database\Seeders\LanguageSeeder;
use Modules\Settings\Database\Seeders\SiteLinkSeeder;
use Modules\Settings\Database\Seeders\VariableSeeder;
use Modules\Content\Database\Seeders\PageSeeder;
use Modules\Content\Database\Seeders\FaqItemsSeeder;
use Modules\Content\Database\Seeders\AboutUsPageSeeder;
use Modules\Commerce\Database\Seeders\DeliveryMethodsSeeder;
use Modules\Commerce\Database\Seeders\PaymentMethodsSeeder;
use Modules\Commerce\Database\Seeders\ShippingZonesSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * No user/customer seeding — the shop is fully guest-session-based.
     * Sessions are stored in Redis (keyed by session ID).
     * The 'users' table belongs to the shared B2B database.
     */
    public function run(): void
    {
        $this->call([
            // Settings
            LanguageSeeder::class,
            VariableSeeder::class,
            SiteLinkSeeder::class,

            // Content
            PageSeeder::class,
            FaqItemsSeeder::class,
            AboutUsPageSeeder::class,

            // Commerce
            DeliveryMethodsSeeder::class,
            PaymentMethodsSeeder::class,
            ShippingZonesSeeder::class,
            CouponSeeder::class,
        ]);
    }
}

