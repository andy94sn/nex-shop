<?php

declare(strict_types=1);

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Models\FaqItem;

class FaqItemsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'question' => [
                    'en' => 'How do I place an order?',
                    'ro' => 'Cum plasez o comandă?'
                ],
                'answer' => [
                    'en' => 'Add items to your cart, proceed to checkout and follow the steps to complete payment.',
                    'ro' => 'Adăugați produsele în coș, mergeți la finalizare și urmați pașii pentru a plăti.'
                ],
                'sort' => 1,
                'is_active' => true,
            ],
            [
                'question' => [
                    'en' => 'What is the return policy?',
                    'ro' => 'Care este politica de returnare?'
                ],
                'answer' => [
                    'en' => 'You can return items within 30 days in original condition. See our returns page for details.',
                    'ro' => 'Puteți returna produsele în 30 de zile, în stare originală. Consultați pagina de returnări pentru detalii.'
                ],
                'sort' => 2,
                'is_active' => true,
            ],
            [
                'question' => [
                    'en' => 'Do you ship internationally?',
                    'ro' => 'Livrați internațional?'
                ],
                'answer' => [
                    'en' => 'Yes, we ship to selected countries. Shipping costs and times vary by destination.',
                    'ro' => 'Da, livrăm în țări selectate. Costurile și timpul de livrare variază în funcție de destinație.'
                ],
                'sort' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($items as $data) {
            FaqItem::create($data);
        }
    }
}
