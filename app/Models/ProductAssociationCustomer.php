<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAssociationCustomer extends Model
{
    protected $fillable = ['customer_id', 'atecedent_product_ids', 'consequent_product_ids', 'support', 'confidence', 'lift', 'analysis_date'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
