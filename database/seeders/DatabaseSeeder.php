<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Database\Seeders\LanguageSeeder;
use Modules\Settings\Database\Seeders\SiteLinkSeeder;
use Modules\Settings\Database\Seeders\VariableSeeder;

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
            LanguageSeeder::class,
            VariableSeeder::class,
            SiteLinkSeeder::class,
        ]);
    }
}

