<?php

declare(strict_types=1);

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\SiteLink;

class SiteLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            // ── Social ────────────────────────────────────────────────────
            // ['type' => 'social', 'name' => 'Facebook',  'url' => 'https://facebook.com/nexdistribution', 'icon' => null, 'status' => true, 'sort' => 1],
            // ['type' => 'social', 'name' => 'Instagram', 'url' => 'https://instagram.com/nexdistribution','icon' => null, 'status' => true, 'sort' => 2],

            // ── Messenger ─────────────────────────────────────────────────
            // ['type' => 'messenger', 'name' => 'WhatsApp', 'url' => 'https://wa.me/37300000000', 'icon' => null, 'status' => true, 'sort' => 1],
            // ['type' => 'messenger', 'name' => 'Telegram', 'url' => 'https://t.me/nexdistribution', 'icon' => null, 'status' => true, 'sort' => 2],

            // ── Payment ───────────────────────────────────────────────────
            // ['type' => 'payment', 'name' => 'Visa',       'url' => null, 'icon' => 'payments/visa.svg',       'status' => true, 'sort' => 1],
            // ['type' => 'payment', 'name' => 'Mastercard', 'url' => null, 'icon' => 'payments/mastercard.svg', 'status' => true, 'sort' => 2],
        ];

        foreach ($links as $link) {
            SiteLink::updateOrCreate(
                ['type' => $link['type'], 'name' => $link['name']],
                $link,
            );
        }
    }
}
