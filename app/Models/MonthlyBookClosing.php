<?php

namespace App\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyBookClosing extends Model
{
    protected $fillable = ['year', 'month', 'total_profit', 'closed_at', 'notes'];
    protected $casts = [
        'total_profit' => 'decimal:2',
        'closed_at' => 'datetime',
    ];
    public function getYearMonthLabelAttribute(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
