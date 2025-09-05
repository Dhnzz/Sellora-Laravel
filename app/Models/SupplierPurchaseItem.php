<?php

namespace App\Models;

use App\Models\SupplierPurchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPurchaseItem extends Model
{
    protected $fillable  = [
        'supplier_purchase_id',
        'product_id',
        'quantity',
        'price',
        'total',
    ];

    public function supplier_purchase(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchase::class, 'supplier_purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
