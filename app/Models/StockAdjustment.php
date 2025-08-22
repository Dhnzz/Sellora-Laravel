<?php

namespace App\Models;

use App\Models\Product;
use App\Models\WarehouseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    protected $fillable = [
        'warehouse_manager_id',
        'product_id',
        'reason',
        'quantity',
        'adjustment_type',
        'source_type',
        'adjustment_date',
    ];

    public function warehouse_manager(): BelongsTo
    {
        return $this->belongsTo(WarehouseManager::class, 'warehouse_manager_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
