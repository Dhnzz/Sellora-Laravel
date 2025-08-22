<?php

namespace App\Models;

use App\Models\Stock;
use App\Models\ProductUnit;
use App\Models\ProductBrand;
use App\Models\UnitConvertion;
use App\Models\StockAdjustment;
use App\Models\ProductBundleItem;
use App\Models\ProductReturnItem;
use App\Models\PurchaseOrderItem;
use App\Models\SalesTransactionItem;
use Illuminate\Database\Eloquent\Model;
use App\Models\IncomingSupplyTransactionItem;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'product_brand_id',
        'name',
        'minimum_selling_unit_id',
        'discount',
        'selling_price',
        'image'
    ];

    public function product_brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'product_brand_id');
    }

    public function product_unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'minimum_selling_unit_id');
    }

    public function unit_convertions()
    {
        // Satu produk bisa memiliki banyak aturan konversi
        return $this->hasMany(UnitConvertion::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'product_id');
    }

    public function purchase_order_items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'product_id');
    }

    public function sales_transaction_items(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class, 'product_id');
    }

    public function delivery_return_items(): HasMany
    {
        return $this->hasMany(DeliveryReturnItem::class, 'product_id');
    }

    public function supplier_purchase_items(): HasMany
    {
        return $this->hasMany(SupplierPurchaseItem::class, 'product_id');
    }

    public function stock_adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'product_id');
    }

    public function product_bundle_items(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'product_id');
    }
}
