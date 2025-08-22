<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAssociation extends Model
{
    protected $fillable = [
        'atecedent_product_ids',
        'consequent_product_ids',
        'support',
        'confidence',
        'lift',
        'analysis_date',
    ];
}
