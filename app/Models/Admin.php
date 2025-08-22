<?php

namespace App\Models;

use App\Models\User;
use App\Models\DeliveryReturn;
use App\Models\SalesTransaction;
use App\Models\MonthlyBookClosing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admin extends Model
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

    public function sales_transactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class, 'admin_id');
    }

    public function monthly_book_closings(): HasMany
    {
        return $this->hasMany(MonthlyBookClosing::class, 'admin_id');
    }

    public function delivery_returns(): HasMany
    {
        return $this->hasMany(DeliveryReturn::class, 'admin_id');
    }
}
