<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\ProductUnit;
use App\Models\ProductBrand;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker; // Import Faker
class MasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        // ProductUnit (min 3, max 10)
        $initialUnits = ['pcs', 'box', 'karton', 'lusin', 'rim', 'set']; // Beberapa unit umum
        foreach ($initialUnits as $unitName) {
            ProductUnit::firstOrCreate(['name' => $unitName]);
        }

        // ProductBrand (min 3, max 10)
        $initialBrands = ['Indomie', 'SilverQueen', 'Wardah', 'Kapal Api', 'Eiger'];
        foreach ($initialBrands as $brandName) {
            ProductBrand::firstOrCreate(['name' => $brandName]);
        }

        // Supplier (min 3, max 10)
        while (Supplier::count() < rand(3, 10)) {
            Supplier::firstOrCreate(
                ['name' => $faker->unique()->company() . ' Supplies'],
                [
                    'address' => $faker->address(),
                    'phone' => $faker->phoneNumber(),
                ],
            );
        }
    }
}
