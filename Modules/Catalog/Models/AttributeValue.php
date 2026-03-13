<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Represents a single possible value for an Attribute.
 *
 * Table: attribute_values
 * Columns: id, attribute_id, value (JSON translatable), deleted_at, ...
 */
class AttributeValue extends Model
{
    use HasTranslations, SoftDeletes;

    protected $table = 'attribute_values';

    public array $translatable = ['value'];

    protected $fillable = ['attribute_id', 'value'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_value_id');
    }
}
