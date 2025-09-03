<?php

namespace App\Models;

use App\Models\User;
use App\Models\SalesAgent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = ['user_id', 'name', 'phone', 'photo', 'address'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi purchase_orders dihapus karena model tidak tersedia

    // Relasi ke ProductAssociationCustomer dihapus karena tidak lagi digunakan
}
