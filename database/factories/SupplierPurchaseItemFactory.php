<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SupplierPurchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplierPurchaseItem>
 */
class SupplierPurchaseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_purchase_id' => SupplierPurchase::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 100),
            'product_unit_price' => $this->faker->randomFloat(2, 1000, 100000),
        ];
    }
}
