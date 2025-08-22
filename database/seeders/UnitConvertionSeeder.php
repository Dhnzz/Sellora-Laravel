<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\UnitConvertion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class UnitConvertionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $products = Product::all();
        $productUnits = ProductUnit::all();
        $recordsCreated = 0;

        if ($products->isEmpty() || $productUnits->isEmpty()) {
            $this->command->info('Produk atau Unit Satuan tidak ditemukan. Mohon jalankan ProductsSeeder dan ProductUnitSeeder terlebih dahulu.');
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($products as $product) {
                // Pastikan produk memiliki MSU
                $msuUnit = $productUnits->find($product->minimum_selling_unit_id);
                if (!$msuUnit) {
                    $this->command->warn("Produk '{$product->name}' tidak memiliki Minimum Selling Unit, konversi tidak akan dibuat.");
                    continue;
                }

                // Ambil unit yang bukan MSU sebagai unit 'asal' (from_unit)
                $otherUnits = $productUnits->where('id', '!=', $msuUnit->id);

                if ($otherUnits->isEmpty()) {
                    $this->command->warn("Tidak ada unit lain selain MSU untuk produk '{$product->name}', konversi tidak dapat dibuat.");
                    continue;
                }

                $fromUnit = $otherUnits->random(); // Pilih unit lain secara acak

                // Pastikan konversi belum ada sebelum membuat
                $existingConversion = UnitConvertion::where('product_id', $product->id)->where('from_unit_id', $fromUnit->id)->where('to_unit_id', $msuUnit->id)->exists();

                if (!$existingConversion) {
                    UnitConvertion::create([
                        'product_id' => $product->id,
                        'from_unit_id' => $fromUnit->id,
                        'to_unit_id' => $msuUnit->id,
                        'convertion_factor' => $faker->numberBetween(2, 50), // Faktor konversi acak
                    ]);
                    $recordsCreated++;
                }
            }

            DB::commit();
            $this->command->info("Unit conversion seeder berhasil dijalankan. Dibuat {$recordsCreated} konversi.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Seeder gagal: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
