<?php

namespace App\Models;

use App\Models\Customer;
use App\Models\SalesAgent;
use App\Models\PurchaseOrderItem;
use App\Models\SalesTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'customer_id',
        'order_date',
        'delivery_date',
        'status',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function purchase_order_items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function sales_transaction(): HasOne
    {
        return $this->hasOne(SalesTransaction::class, 'purchase_order_id');
    }
}
