<?php

namespace App\Models;

use App\Models\User;
use App\Models\Customer;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesAgent extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'photo',
        'address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'sales_agent_id');
    }

    public function purchase_orders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'sales_agent_id');
    }
}
