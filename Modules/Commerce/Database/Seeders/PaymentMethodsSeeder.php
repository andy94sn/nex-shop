<?php

declare(strict_types=1);

namespace Modules\Commerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Commerce\Models\PaymentMethod;

class PaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code'                => 'cash',
                'name'                => ['ro' => 'Numerar',         'ru' => 'Наличные',           'en' => 'Cash'],
                'description'         => ['ro' => 'Plată în numerar la livrare.',                  'ru' => 'Оплата наличными при доставке.',                    'en' => 'Cash payment on delivery.'],
                'icon'                => null,
                'is_online'           => false,
                'gateway'             => null,
                'gateway_config'      => null,
                'requires_credit_plan'=> false,
                'is_active'           => true,
                'sort'                => 1,
            ],
            [
                'code'                => 'cod',
                'name'                => ['ro' => 'Ramburs (curier)', 'ru' => 'Наложенный платёж', 'en' => 'Cash on delivery'],
                'description'         => ['ro' => 'Plată la primirea comenzii prin curier.',       'ru' => 'Оплата при получении заказа курьером.',             'en' => 'Pay when the courier delivers your order.'],
                'icon'                => null,
                'is_online'           => false,
                'gateway'             => null,
                'gateway_config'      => null,
                'requires_credit_plan'=> false,
                'is_active'           => true,
                'sort'                => 2,
            ],
            [
                'code'                => 'ccod',
                'name'                => ['ro' => 'Card la livrare',  'ru' => 'Карта при доставке','en' => 'Card on delivery'],
                'description'         => ['ro' => 'Plată cu cardul bancar la primirea comenzii.',  'ru' => 'Оплата банковской картой при получении.',           'en' => 'Pay by card when your order is delivered.'],
                'icon'                => null,
                'is_online'           => false,
                'gateway'             => null,
                'gateway_config'      => null,
                'requires_credit_plan'=> false,
                'is_active'           => true,
                'sort'                => 3,
            ],
            [
                'code'                => 'maib',
                'name'                => ['ro' => 'Transfer MAIB',    'ru' => 'Перевод MAIB',      'en' => 'MAIB transfer'],
                'description'         => ['ro' => 'Plată online prin sistemul MAIB e-commerce.',   'ru' => 'Онлайн-оплата через систему MAIB e-commerce.',     'en' => 'Online payment via MAIB e-commerce gateway.'],
                'icon'                => null,
                'is_online'           => true,
                'gateway'             => 'maib',
                'gateway_config'      => null,
                'requires_credit_plan'=> false,
                'is_active'           => true,
                'sort'                => 4,
            ],
            [
                'code'                => 'credit',
                'name'                => ['ro' => 'Credit / Rate',    'ru' => 'Кредит / Рассрочка','en' => 'Credit / Installments'],
                'description'         => ['ro' => 'Achiziție în rate fără dobândă sau cu dobândă redusă.', 'ru' => 'Покупка в рассрочку без процентов или по сниженной ставке.', 'en' => 'Purchase in installments with zero or reduced interest.'],
                'icon'                => null,
                'is_online'           => false,
                'gateway'             => null,
                'gateway_config'      => null,
                'requires_credit_plan'=> true,
                'is_active'           => true,
                'sort'                => 5,
            ],
        ];

        foreach ($methods as $data) {
            PaymentMethod::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
