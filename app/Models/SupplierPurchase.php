<?php

namespace App\Models;

use App\Models\Admin;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPurchase extends Model
{
    protected $fillable  = [
        'admin_id',
        'supplier_id',
        'purchase_date',
        'invoice_number',
        'total_amount',
        'notes',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function supplier_purchase_item(): HasMany
    {
        return $this->hasMany(SupplierPurchaseItem::class);
    }
}
