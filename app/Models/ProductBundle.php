<?php

namespace App\Models;

use App\Models\ProductBundleItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBundle extends Model
{
    protected $fillable = [
        'bundle_name',
        'flyer',
        'description',
        'start_date',
        'end_date',
        'special_bundle_price',
        'original_price',
        'is_active',
    ];

    public function product_bundle_items(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'product_bundle_id');
    }
}
