<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'article',
        'title', 'subtitle', 'quantity', 'unit_price', 'image',
    ];

    protected $casts = [
        'title'      => 'array',
        'subtitle'   => 'array',
        'unit_price' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
