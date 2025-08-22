<?php

namespace App\Models;

use App\Models\Admin;
use App\Models\SalesAgent;
use App\Models\PurchaseOrder;
use App\Models\DeliveryReturn;
use App\Models\SalesTransactionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTransaction extends Model
{
    protected $fillable = ['purchase_order_id', 'admin_id', 'sales_agent_id', 'invoice_id', 'invoice_date', 'discount_percent', 'initial_total_amount', 'final_total_amount', 'note', 'transaction_status', 'delivery_confirmed_at'];

    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function sales_agent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }

    public function sales_transaction_items(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class, 'sales_transaction_id');
    }

    public function delivery_returns(): HasMany
    {
        return $this->hasMany(DeliveryReturn::class, 'sales_transaction_id');
    }
}
