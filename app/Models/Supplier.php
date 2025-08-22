<?php

namespace App\Models;

use App\Models\IncomingSupplyTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['name', 'address', 'phone'];

    public function incoming_supply_transactions(): HasMany
    {
        return $this->hasMany(IncomingSupplyTransaction::class, 'supplier_id');
    }
}
