<?php

namespace App\Models;

use App\Models\Admin;
use App\Models\SalesAgent;
use App\Models\Customer;
use App\Models\SalesTransactionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTransaction extends Model
{
    protected $fillable = ['customer_id', 'admin_id', 'sales_agent_id', 'order_date', 'invoice_id', 'invoice_date', 'initial_total_amount', 'final_total_amount', 'note', 'transaction_status', 'cancel_note', 'delivery_confirmed_at'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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

    // Relasi delivery_returns dihapus (fitur retur dinonaktifkan)
}
