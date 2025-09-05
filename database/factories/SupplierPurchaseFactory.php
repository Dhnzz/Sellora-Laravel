<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplierPurchase>
 */
class SupplierPurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'purchase_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'invoice_number' => 'INV-SUP-' . $this->faker->unique()->numberBetween(1000, 9999),
            'total_amount' => $this->faker->randomFloat(2, 100000, 5000000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
