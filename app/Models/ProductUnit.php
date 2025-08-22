<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    protected $fillable = [
        'name',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'minimum_selling_unit_id');
    }

    // Relasi untuk mendapatkan data satuan 'asal' (from_unit)
    public function from_unit(): HasOne
    {
        return $this->hasOne(UnitConvertion::class, 'from_unit_id');
    }

    // Relasi untuk mendapatkan data satuan 'tujuan' (to_unit)
    public function to_unit(): HasOne
    {
        return $this->hasOne(UnitConvertion::class, 'to_unit_id');
    }
}
