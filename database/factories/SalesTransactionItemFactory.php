<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use App\Models\SalesTransactionItem;
use App\Models\Product;

class SalesTransactionItemFactory extends Factory
{
    protected $model = SalesTransactionItem::class;

    public function definition(): array
    {
        $product = Product::query()->inRandomOrder()->first();
        $unitPrice = optional($product)->msu_price ?? $this->faker->numberBetween(5000, 120000);
        $qtyOrdered = $this->faker->numberBetween(1, 6);
        $qtySold = $this->faker->numberBetween(0, $qtyOrdered);
        $disc = $this->faker->boolean(50) ? $this->faker->randomElement([0.05, 0.1, 0.15]) : 0.0;

        $lineBefore = $unitPrice * $qtySold;
        $lineAfter = $lineBefore - ($disc > 0 ? $lineBefore * $disc : 0);

        $hasUnitPriceCol = Schema::hasColumn('sales_transaction_items', 'unit_price');
        $hasProdDiscCol = Schema::hasColumn('sales_transaction_items', 'product_discount_percent');
        $hasUnitAfterCol = Schema::hasColumn('sales_transaction_items', 'unit_price_after_product_discount');
        $hasLineBeforeCol = Schema::hasColumn('sales_transaction_items', 'line_total_before_order_discount');
        $hasLineAfterCol = Schema::hasColumn('sales_transaction_items', 'line_total_after_product_discount');

        $data = [
            'product_id' => optional($product)->id,
            'quantity_ordered' => $qtyOrdered,
            'quantity_sold' => $qtySold,
            'msu_price' => $unitPrice,
            // 'sales_transaction_id' biarkan null; biasanya diisi via relasi ->create()
        ];
        if ($hasUnitPriceCol) {
            $data['unit_price'] = $unitPrice;
        }
        if ($hasProdDiscCol) {
            $data['product_discount_percent'] = $disc;
        }
        if ($hasUnitAfterCol) {
            $data['unit_price_after_product_discount'] = $disc > 0 ? $unitPrice * (1 - $disc) : $unitPrice;
        }
        if ($hasLineBeforeCol) {
            $data['line_total_before_order_discount'] = $lineBefore;
        }
        if ($hasLineAfterCol) {
            $data['line_total_after_product_discount'] = $lineAfter;
        }

        return $data;
    }
}
