<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyRevenuePrediction extends Model
{
    protected $fillable = [
        'year','month','predicted_profit','threshold_profit',
        'is_profitable','pct_change_vs_last','model_version','meta'
    ];
    protected $casts = ['is_profitable'=>'boolean','meta'=>'array'];
    public function getYearMonthLabelAttribute(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
