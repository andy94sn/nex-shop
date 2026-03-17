<?php

declare(strict_types=1);

namespace Modules\Commerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Commerce\Models\DeliveryMethod;

class DeliveryMethodsSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code'             => 'shipping',
                'name'             => ['ro' => 'Livrare la domiciliu', 'ru' => 'Доставка на дом',    'en' => 'Home delivery'],
                'description'      => ['ro' => 'Livrare prin curier la adresa indicată.',            'ru' => 'Доставка курьером по указанному адресу.',       'en' => 'Courier delivery to the specified address.'],
                'icon'             => null,
                'requires_address' => true,
                'is_active'        => true,
                'sort'             => 1,
            ],
            [
                'code'             => 'pickup',
                'name'             => ['ro' => 'Ridicare din magazin', 'ru' => 'Самовывоз из магазина', 'en' => 'Store pickup'],
                'description'      => ['ro' => 'Ridicați comanda personal din showroom-ul nostru.',  'ru' => 'Заберите заказ самостоятельно из нашего шоурума.', 'en' => 'Pick up your order from our showroom.'],
                'icon'             => null,
                'requires_address' => false,
                'is_active'        => true,
                'sort'             => 2,
            ],
        ];

        foreach ($methods as $data) {
            DeliveryMethod::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
