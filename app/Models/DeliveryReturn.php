<?php

namespace App\Models;

use App\Models\Admin;
use App\Models\SalesTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryReturn extends Model
{
    protected $fillable  = [
        'sales_transaction_id',
        'return_date',
        'reason',
        'status',
        'admin_id', // Admin yang mengkonfirmasi return
        'confirmed_at',
        'total_amount'
    ];

    public function sales_transaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function delivery_return_items(): HasMany
    {
        return $this->hasMany(DeliveryReturnItem::class, 'delivery_return_id');
    }
}
