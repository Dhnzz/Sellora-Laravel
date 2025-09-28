<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SalesAgent;
use App\Models\ProductUnit;
use App\Models\ProductBundle;
use App\Models\StockAdjustment;
use Illuminate\Database\Seeder;
use App\Models\SalesTransaction;
use App\Models\SupplierPurchase;
use App\Models\WarehouseManager;
use Database\Seeders\AdminSeeder;
use Database\Seeders\MasterSeeder;
use Illuminate\Support\Facades\DB;
use App\Models\SalesTransactionItem;
use App\Models\SupplierPurchaseItem;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\SalesAgentSeeder;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\ProductRelatedSeeder;
use Faker\Factory as Faker; // Import Faker
use Database\Seeders\WarehouseManagerSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PurchaseAndSalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $admins = Admin::all();
        $salesAgents = SalesAgent::all();
        $customers = Customer::all();
        $products = Product::all();
        $suppliers = Supplier::all();
        $productUnits = ProductUnit::all();
        $productBundles = ProductBundle::where('is_active', true)->get(); // Hanya bundle aktif

        DB::beginTransaction();
        try {
            // ============================ Sales Transactions (langsung tanpa PurchaseOrder) ============================
            // Buat 20 Sales Transaction, sebar ke hari ini, bulan ini, tahun ini, dan tahun kemarin
            $salesTransactions = [];
            $totalSalesTransactionAmount = 0;
            $salesTransactionDates = [];

            // Bagi 20 ST: 5 hari ini, 5 bulan ini (selain hari ini), 5 tahun ini (selain bulan ini), 5 tahun kemarin
            $today = Carbon::today();
            $startOfMonth = Carbon::now()->startOfMonth();
            $startOfYear = Carbon::now()->startOfYear();
            $startOfLastYear = Carbon::now()->subYear()->startOfYear();
            $endOfLastYear = Carbon::now()->subYear()->endOfYear();

            // 5 hari ini
            for ($i = 0; $i < 5; $i++) {
                $salesTransactionDates[] = $today->copy();
            }
            // 5 bulan ini (selain hari ini)
            for ($i = 0; $i < 5; $i++) {
                $salesTransactionDates[] = $faker->dateTimeBetween($startOfMonth, $today->copy()->subDay());
            }
            // 5 tahun ini (selain bulan ini)
            for ($i = 0; $i < 5; $i++) {
                $salesTransactionDates[] = $faker->dateTimeBetween($startOfYear, $startOfMonth->copy()->subDay());
            }
            // 5 tahun kemarin
            for ($i = 0; $i < 5; $i++) {
                $salesTransactionDates[] = $faker->dateTimeBetween($startOfLastYear, $endOfLastYear);
            }

            // Acak urutan tanggal agar tidak berurutan
            shuffle($salesTransactionDates);

            // ============================ Sales Transactions ============================
            $createdCount = 0;
            $successCount = 0;
            $totalSalesTransactionAmount = 0;

            // Siapkan pool status: 100 success + 50 status lain (acak)
            $statusPool = array_fill(0, 100, 'success');
            $otherStatuses = ['pending', 'process', 'cancelled'];
            for ($i = 0; $i < 50; $i++) {
                $statusPool[] = $otherStatuses[array_rand($otherStatuses)];
            }
            shuffle($statusPool); // acak urutan supaya tidak berderet success semua

            foreach ($statusPool as $idx => $paymentStatus) {
                // Tanggal acak 4 bulan terakhir
                $orderDate = $faker->dateTimeBetween('-4 months', 'now');
                $invoiceDate = $faker->dateTimeBetween($orderDate, 'now');

                // delivery_confirmed_at hanya untuk success/process
                $deliveryConfirmedAt = in_array($paymentStatus, ['success', 'process']) ? (clone $invoiceDate)->modify('+' . rand(0, 7) . ' days') : null;

                // Generate invoice_id unik per hari: INV-ddmmyyyy-####
                $invoiceDateStr = Carbon::instance($invoiceDate)->format('Y-m-d');
                $invoiceDateForId = Carbon::instance($invoiceDate)->format('dmY');
                $todayCount = SalesTransaction::whereDate('invoice_date', $invoiceDateStr)->count() + 1;
                $invoiceId = 'INV-' . $invoiceDateForId . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

                try {
                    $salesTransaction = SalesTransaction::factory()
                        ->state([
                            'invoice_id' => $invoiceId,
                            'invoice_date' => $invoiceDateStr,
                            'transaction_status' => $paymentStatus,
                            'delivery_confirmed_at' => $deliveryConfirmedAt,
                        ])
                        ->create();

                    $createdCount++;
                    if ($paymentStatus === 'success') {
                        $successCount++;
                    }

                    $totalSalesTransactionAmount += (int) $salesTransaction->final_total_amount;

                    // Update stok berdasarkan quantity_sold
                    foreach ($salesTransaction->sales_transaction_items as $item) {
                        $product = Product::find($item->product_id);
                        $qtySold = (int) ($item->quantity_sold ?? 0);
                        if ($product && $qtySold > 0) {
                            // sesuaikan kalau relasi/kolom stok kamu berbeda
                            $product->stock()->decrement('quantity', $qtySold);
                        }
                    }
                } catch (\Exception $e) {
                    $this->command->error('Error creating Sales Transaction (via factory): ' . $e->getMessage());
                }
            }

            $this->command->info("Created {$createdCount} Sales Transactions ({$successCount} success).");

            // ============================ Supplier Purchases ============================
            // Buat 3 Supplier Purchase: bulan ini, tahun ini (selain bulan ini), tahun kemarin
            $supplierPurchaseDates = [
                Carbon::now(), // bulan ini (hari ini)
                $faker->dateTimeBetween($startOfYear, $startOfMonth->copy()->subDay()), // tahun ini (selain bulan ini)
                $faker->dateTimeBetween($startOfLastYear, $endOfLastYear), // tahun kemarin
            ];

            $successfulSupplierPurchases = 0;
            $totalSupplierPurchaseAmount = 0;
            foreach ($supplierPurchaseDates as $purchaseDate) {
                $adminUser = $admins->random();
                $supplier = $suppliers->random();

                $supplierPurchase = SupplierPurchase::create([
                    'supplier_id' => $supplier->id,
                    'purchase_date' => Carbon::instance($purchaseDate)->format('Y-m-d'),
                    'invoice_number' => 'INV-SUP-' . Carbon::instance($purchaseDate)->format('Ymd') . '-' . $supplier->id,
                    'total_amount' => 0,
                    'notes' => $faker->sentence(),
                ]);
                $successfulSupplierPurchases++;

                $itemCount = rand(1, 3);
                $totalAmount = 0;
                $purchasedProducts = collect();

                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $products->whereNotIn('id', $purchasedProducts->pluck('id'))->random();
                    $purchasedProducts->push($product);

                    $quantity = rand(10, 50); // Batasi quantity agar tidak terlalu besar
                    $possibleUnits = $productUnits->where('id', '!=', $product->minimum_selling_unit_id);
                    $selectedUnit = $possibleUnits->isNotEmpty() ? $possibleUnits->random() : $productUnits->firstWhere('id', $product->minimum_selling_unit_id);

                    // Harga beli per unit yang dipesan, pastikan lebih murah dari harga jual
                    $unitPurchasePrice = $product->selling_price * $faker->randomFloat(2, 0.4, 0.7);

                    $supplierPurchase->supplier_purchase_item()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => round($unitPurchasePrice, 2),
                        'total' => $unitPurchasePrice * $quantity,
                    ]);
                    $totalAmount += $quantity * $unitPurchasePrice;
                }
                $supplierPurchase->update(['total_amount' => round($totalAmount, 2)]);
                $totalSupplierPurchaseAmount += $totalAmount;
            }

            // Pastikan total supplier purchase tidak lebih mahal dari total sales
            if ($totalSupplierPurchaseAmount > $totalSalesTransactionAmount) {
                $this->command->warn('Total Supplier Purchase lebih besar dari total Sales, mohon cek harga dan quantity di seeder.');
            }
            $this->command->info('Created ' . $successfulSupplierPurchases . ' Supplier Purchases.');

            DB::commit(); // Commit all transactions if successful
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error
            $this->command->error('Seeding failed: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
