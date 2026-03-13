<?php

declare(strict_types=1);

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores global key-value configuration variables managed from the B2B panel.
 * This model is read-only in the shop — never write to it here.
 *
 * @property int         $id
 * @property string      $group    Logical namespace (e.g. "seo", "homepage", "shipping")
 * @property string      $key      Variable name within the group (e.g. "title", "phone")
 * @property string|null $value    Raw stored value (plain string or JSON string)
 * @property bool        $is_json  When true, $value is decoded as JSON on read
 */
class Variable extends Model
{
    protected $table = 'variables';

    protected $fillable = ['group', 'key', 'value', 'is_json'];

    protected $casts = [
        'is_json' => 'boolean',
    ];

    /**
     * Return the value decoded according to is_json.
     * Returns null when the record does not exist.
     */
    public function getValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        return $this->is_json ? json_decode($this->value, true) : $this->value;
    }
}
