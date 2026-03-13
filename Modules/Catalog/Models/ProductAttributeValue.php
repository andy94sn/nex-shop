<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\AttributeValue;

/**
 * Pivot between products and attribute_values.
 *
 * Table columns: product_id, attribute_value_id
 *
 * To reach the attribute or the value string, go through attributeValue:
 *   $pav->attributeValue->attribute_id
 *   $pav->attributeValue->value   (JSON translatable)
 *   $pav->attributeValue->attribute->title
 */
class ProductAttributeValue extends Model
{
    protected $table = 'product_attribute_values';

    protected $fillable = ['product_id', 'attribute_value_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** The canonical attribute_values row (holds attribute_id + value JSON). */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }
}
