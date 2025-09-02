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
        $warehouseManagers = WarehouseManager::all();
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
            $successfulSalesCount = 0;
            $totalSalesTransactionAmount = 0;
            foreach ($salesTransactionDates as $orderDate) {
                $orderDate = $orderDate instanceof \DateTime ? Carbon::instance($orderDate) : $orderDate;

                // invoice_date harus >= order_date
                $invoiceDate = $faker->dateTimeBetween($orderDate, 'now');

                // Status & delivery (random)
                $deliveryConfirmedAt = null;
                $paymentStatus = 'success';
                if ($faker->boolean(80)) {
                    $deliveryConfirmedAt = (clone $invoiceDate)->modify('+' . rand(0, 7) . ' days');
                    $paymentStatus = $faker->randomElement(['pending','process','cancelled','success']);
                }

                // ambil admin & sales
                $limitedAdmins = $admins->take(10);
                $limitedSales = $salesAgents->take(5);
                $adminUser = $limitedAdmins->random();
                $salesAgentUser = $limitedSales->random();

                // ====== RUMUS DISKON ======
                // 1) Diskon produk (line-level) dulu
                // 2) Subtotal = sum(line setelah diskon produk)
                // 3) Diskon transaksi (order-level) diapply ke subtotal

                // cek kolom snapshot item (biar aman kalau belum migrasi)
                $hasUnitPriceCol = Schema::hasColumn('sales_transaction_items', 'unit_price');
                $hasProdDiscCol = Schema::hasColumn('sales_transaction_items', 'product_discount_percent');
                $hasUnitAfterCol = Schema::hasColumn('sales_transaction_items', 'unit_price_after_product_discount');
                $hasLineBeforeCol = Schema::hasColumn('sales_transaction_items', 'line_total_before_order_discount');

                // rakit item + hitung subtotal SEBELUM diskon transaksi
                $subtotalBeforeOrderDiscount = 0;
                $salesTransactionItemsData = [];

                // Buat item secara acak (2-3 produk)
                $itemCount = rand(2, 3);
                $orderedProducts = collect();
                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $products->whereNotIn('id', $orderedProducts->pluck('id'))->random();
                    $orderedProducts->push($product);

                    $qtyOrdered = rand(1, 5);
                    $qtySold = $qtyOrdered;

                    // simulasi retur di tempat (kalau bukan success)
                    if ($paymentStatus !== 'success' && $qtyOrdered > 1 && $faker->boolean(30)) {
                        $qtySold = rand(1, $qtyOrdered - 1);
                        if ($qtySold <= 0) {
                            $qtySold = 0;
                        }
                    }

                    $unitPrice = (float) $product->selling_price; // harga asli saat ini
                    $pdisc = (float) ($product->discount ?? 0); // diskon produk (%)
                    $pdisc = max(0, min(100, $pdisc));
                    $unitAfter = round($unitPrice * (1 - $pdisc / 100), 2); // harga satuan setelah diskon produk
                    $lineBeforeOrder = round($unitAfter * $qtySold, 2); // subtotal line sebelum diskon order

                    $subtotalBeforeOrderDiscount += $lineBeforeOrder;

                    // siapkan payload item
                    $row = [
                        'product_id' => $product->id,
                        'quantity_ordered' => $qtyOrdered,
                        'quantity_sold' => $qtySold,
                        'msu_price' => $unitPrice, // kolom lama kamu; dipakai sebagai unit price asli
                    ];

                    // snapshot kolom tambahan kalau tersedia
                    if ($hasUnitPriceCol) {
                        $row['unit_price'] = $unitPrice;
                    }
                    if ($hasProdDiscCol) {
                        $row['product_discount_percent'] = $pdisc;
                    }
                    if ($hasUnitAfterCol) {
                        $row['unit_price_after_product_discount'] = $unitAfter;
                    }
                    if ($hasLineBeforeCol) {
                        $row['line_total_before_order_discount'] = $lineBeforeOrder;
                    }

                    $salesTransactionItemsData[] = $row;
                }

                // Diskon transaksi ORDER-LEVEL (misal 0–15%)
                $orderDiscountPercent = number_format($faker->randomFloat(2, 0.00, 0.50));
                $totalAmountAfterDiscount = number_format($subtotalBeforeOrderDiscount * (1 - $orderDiscountPercent / 100), 2, '.', '');

                // final_amount_paid (boleh kurang kalau retur/process)
                $finalAmountPaid = $totalAmountAfterDiscount;
                if ($paymentStatus === 'not_paid') {
                    $finalAmountPaid = 0;
                }

                try {
                    $invoiceDateStr = Carbon::instance($invoiceDate)->format('Y-m-d');
                    $invoiceDateForId = Carbon::instance($invoiceDate)->format('dmY');
                    $todayCount = SalesTransaction::whereDate('invoice_date', $invoiceDateStr)->count() + 1;
                    $invoiceId = 'INV-' . $invoiceDateForId . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

                    $salesTransaction = SalesTransaction::create([
                        'customer_id' => $customers->random()->id,
                        'admin_id' => $adminUser->id,
                        'sales_agent_id' => $salesAgentUser->id,
                        'invoice_id' => $invoiceId,
                        'invoice_date' => $invoiceDateStr,

                        // ==== snapshot total & diskon transaksi ====
                        // pakai field existing di model kamu:
                        'discount_percent' => $orderDiscountPercent, // diskon transaksi (%)
                        'initial_total_amount' => $subtotalBeforeOrderDiscount, // subtotal setelah diskon produk, sebelum diskon order
                        'final_total_amount' => $finalAmountPaid, // total akhir setelah diskon order

                        'note' => 'Lorem, ipsum dolor.',
                        'transaction_status' => $paymentStatus,
                        'delivery_confirmed_at' => $deliveryConfirmedAt,
                    ]);
                    $successfulSalesCount++;
                    $totalSalesTransactionAmount += $finalAmountPaid;

                    // Tambahkan item
                    foreach ($salesTransactionItemsData as $itemData) {
                        $salesTransaction->sales_transaction_items()->create($itemData);
                    }

                    // Update stok berdasarkan quantity_sold
                    foreach ($salesTransactionItemsData as $itemData) {
                        $product = Product::find($itemData['product_id']);
                        $qtySold = (int) ($itemData['quantity_sold'] ?? 0);
                        if ($product && $qtySold > 0) {
                            $product->stock()->decrement('quantity', $qtySold);
                        }
                    }
                } catch (\Exception $e) {
                    $this->command->error('Error creating Sales Transaction: ' . $e->getMessage());
                }
            }
            $this->command->info('Created ' . $successfulSalesCount . ' Sales Transactions.');

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
                    'admin_id' => $adminUser->id,
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
                        'product_unit_id' => $selectedUnit->id,
                        'product_unit_price' => round($unitPurchasePrice, 2),
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

            // ============================ Delivery Returns (dinonaktifkan) ============================
            // Bagian pengembalian pengantaran di-nonaktifkan sesuai instruksi.

            // ============================ Stock Adjustments (Manual/Other Reasons) ============================
            $numManualAdjustments = rand(5, 10);
            $successfulManualAdjustments = 0;
            $warehouseManagers = WarehouseManager::all(); // Ensure this is loaded if not already at the top

            if ($warehouseManagers->isNotEmpty()) {
                for ($i = 0; $i < $numManualAdjustments; $i++) {
                    $product = $products->random();
                    $warehouseManager = $warehouseManagers->random();
                    $adjustmentDate = $faker->dateTimeBetween('this year - 6 months', 'now');

                    $adjustmentType = $faker->randomElement(['increase', 'decrease']);
                    $quantity = rand(1, 20); // Random quantity for adjustment

                    // Adjust quantity sign based on type
                    $quantityAdjusted = $adjustmentType == 'decrease' ? -$quantity : $quantity;

                    StockAdjustment::create([
                        'warehouse_manager_id' => $warehouseManager->id,
                        'product_id' => $product->id,
                        'reason' => $faker->randomElement(['Barang rusak di gudang', 'Selisih stok fisik', 'Barang hilang', 'Penerimaan non-pembelian']),
                        'quantity' => $quantityAdjusted,
                        'adjustment_type' => $adjustmentType,
                        'source_type' => 'physical_check', // Or 'other'
                        'source_id' => null,
                        'adjustment_date' => $adjustmentDate->format('Y-m-d'),
                    ]);
                    $successfulManualAdjustments++;

                    // Update actual stock for manual adjustments
                    $stock = Stock::where('product_id', $product->id)->first();
                    if ($stock) {
                        $stock->increment('quantity', $quantityAdjusted);
                    } else {
                        // If no stock record, create one
                        Stock::create(['product_id' => $product->id, 'quantity' => $quantityAdjusted]);
                    }
                }
            }
            $this->command->info('Created ' . $successfulManualAdjustments . ' manual Stock Adjustments.');
            DB::commit(); // Commit all transactions if successful
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error
            $this->command->error('Seeding failed: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}
