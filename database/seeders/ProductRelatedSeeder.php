<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductBrand;
use App\Models\ProductBundle;
use Illuminate\Database\Seeder;
use App\Models\ProductBundleItem;
use Database\Seeders\MasterSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker; // Import Faker

class ProductRelatedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $productBrands = ProductBrand::all();
        $productUnits = ProductUnit::where('name', 'pcs')->first();
        
        // Ensure master data exists before proceeding
        // if ($productBrands->isEmpty() || $productUnits->isEmpty()) {
        //     $this->call(MasterSeeder::class);
        //     $productBrands = ProductBrand::all();
        //     $productUnits = ProductUnit::all();
        // }
        
        // Product (buat 20 produk dengan format: Produk 1 (Brand Name), Produk 2 (Brand Name), dst)
        // 7 produk pertama akan memiliki diskon acak, sisanya diskon 0
        for ($i = 1; $i <= 20; $i++) {
            $brand = $productBrands->random();
            $discount = ($i <= 7) ? $faker->randomFloat(2, 0.05, 0.5) : 0; // 7 produk pertama diskon 5%-30%, sisanya 0
            $product = Product::create([
                'product_brand_id' => $brand->id,
                'name' => 'Produk ' . $i . ' (' . $brand->name . ')',
                'minimum_selling_unit_id' => $productUnits->id,
                'discount' => $discount,
                'selling_price' => round($faker->numberBetween(10000, 500000), -2), // Harga acak dibulatkan ke ratusan
                'image' => 'uploads/images/products/product-1.png', 
            ]);
            
            // Stock (kuantitas stok acak untuk setiap produk)
            Stock::create([
                'product_id' => $product->id,
                'quantity' => rand(50, 500), // Kuantitas stok acak
            ]);
        }
        
        $products = Product::all();
        $bundleItems = [];
        for ($i = 1; $i <= 2; $i++) {
            for ($item = 1; $item < rand(2, 5); $item++) {
                $bundleItems[] = $products->random();
            }

            // Hitung quantity untuk setiap item bundle di awal
            $originalPrice = 0;
            $bundleItemQuantities = [];
            foreach ($bundleItems as $item) {
                $qty = rand(1, 3);
                $bundleItemQuantities[$item->id] = $qty;
                $originalPrice += $item->selling_price * $qty;
            }
            $specialBundlePrice = $originalPrice * $faker->randomFloat(2, 0.7, 0.95); // Diskon 5-30%

            $bundle = ProductBundle::create([
                'bundle_name' => 'Bundle ' . $i,
                'flyer' => 'uploads/images/product_bundles/bundle-1.png',
                'description' => $faker->paragraph(),
                'start_date' => $faker->dateTimeBetween('-6 months', 'now'),
                'end_date' => $faker->dateTimeBetween('now', '+6 months'),
                'special_bundle_price' => round($specialBundlePrice, 0),
                'original_price' => $originalPrice,
                'is_active' => $faker->boolean(80), // 80% kemungkinan aktif
            ]);

            foreach ($bundleItems as $item) {
                ProductBundleItem::create([
                    'product_bundle_id' => $bundle->id,
                    'product_id' => $item->id,
                    'quantity' => $bundleItemQuantities[$item->id],
                ]);
            }
        }
        // Menampilkan nama produk dari setiap item di $bundleItems
    }
}
