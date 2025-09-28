<?php

namespace App\Models;

use App\Models\Product;
use App\Models\SalesTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesTransactionItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'sales_transaction_id',
        'product_id',
        'quantity_ordered',
        'quantity_sold',
        'msu_price',
    ];

    public function sales_transaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
