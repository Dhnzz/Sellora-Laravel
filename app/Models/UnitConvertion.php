<?php

namespace App\Models;

use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConvertion extends Model
{
    protected $fillable = [
        'product_id',
        'from_unit_id',
        'to_unit_id',
        'convertion_factor'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function from_unit()
    {
        return $this->belongsTo(ProductUnit::class, 'from_unit_id');
    }

    public function to_unit()
    {
        return $this->belongsTo(ProductUnit::class, 'to_unit_id');
    }
}
