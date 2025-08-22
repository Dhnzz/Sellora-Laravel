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
use App\Models\PurchaseOrder;
use App\Models\DeliveryReturn;
use App\Models\StockAdjustment;
use Illuminate\Database\Seeder;
use App\Models\SalesTransaction;
use App\Models\SupplierPurchase;
use App\Models\WarehouseManager;
use App\Models\PurchaseOrderItem;
use Database\Seeders\AdminSeeder;
use App\Models\DeliveryReturnItem;
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
            // ============================ Purchase Orders (Customers) ============================
            // Buat 20 Purchase Order, sebar ke hari ini, bulan ini, tahun ini, dan tahun kemarin
            $confirmedPurchaseOrders = [];
            $totalPurchaseOrderAmount = 0;
            $purchaseOrderDates = [];

            // Bagi 20 PO: 5 hari ini, 5 bulan ini (selain hari ini), 5 tahun ini (selain bulan ini), 5 tahun kemarin
            $today = Carbon::today();
            $startOfMonth = Carbon::now()->startOfMonth();
            $startOfYear = Carbon::now()->startOfYear();
            $startOfLastYear = Carbon::now()->subYear()->startOfYear();
            $endOfLastYear = Carbon::now()->subYear()->endOfYear();

            // 5 hari ini
            for ($i = 0; $i < 5; $i++) {
                $purchaseOrderDates[] = $today->copy();
            }
            // 5 bulan ini (selain hari ini)
            for ($i = 0; $i < 5; $i++) {
                $purchaseOrderDates[] = $faker->dateTimeBetween($startOfMonth, $today->copy()->subDay());
            }
            // 5 tahun ini (selain bulan ini)
            for ($i = 0; $i < 5; $i++) {
                $purchaseOrderDates[] = $faker->dateTimeBetween($startOfYear, $startOfMonth->copy()->subDay());
            }
            // 5 tahun kemarin
            for ($i = 0; $i < 5; $i++) {
                $purchaseOrderDates[] = $faker->dateTimeBetween($startOfLastYear, $endOfLastYear);
            }

            // Acak urutan tanggal agar tidak berurutan
            shuffle($purchaseOrderDates);

            foreach ($purchaseOrderDates as $orderDate) {
                $orderDate = $orderDate instanceof \DateTime ? Carbon::instance($orderDate) : $orderDate;
                $minDelivDate = (clone $orderDate)->addDays(3);
                $maxDelivDate = (clone $orderDate)->addDays(7);
                $deliveryDueDate = $faker->dateTimeBetween($minDelivDate, $maxDelivDate);

                $po = PurchaseOrder::create([
                    'customer_id' => $customers->random()->id,
                    'order_date' => $orderDate,
                    'delivery_date' => $deliveryDueDate,
                    'status' => 'confirmed',
                ]);
                $confirmedPurchaseOrders[] = $po;

                // PurchaseOrderItem
                $itemCount = rand(1, 3);
                $orderedProducts = collect();
                $poTotal = 0;
                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $products->whereNotIn('id', $orderedProducts->pluck('id'))->random();
                    $orderedProducts->push($product);

                    $qty = rand(1, 5);
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                    ]);
                    $poTotal += $qty * $product->selling_price;
                }
                $totalPurchaseOrderAmount += $poTotal;
            }

            // ============================ Sales Transactions ============================
            $successfulSalesCount = 0;
            $totalSalesTransactionAmount = 0;
            foreach ($confirmedPurchaseOrders as $po) {
                if (SalesTransaction::where('purchase_order_id', $po->id)->exists()) {
                    continue;
                }

                // invoice_date harus >= order_date
                $invoiceDate = $faker->dateTimeBetween($po->order_date, 'now');

                // Status & delivery (random)
                $deliveryConfirmedAt = null;
                $paymentStatus = 'success';
                if ($faker->boolean(80)) {
                    $deliveryConfirmedAt = (clone $invoiceDate)->modify('+' . rand(0, 7) . ' days');
                    $paymentStatus = $faker->randomElement(['process', 'success']);
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
                $poItems = $po->purchase_order_items()->get();

                foreach ($poItems as $poProduct) {
                    $product = $products->find($poProduct->product_id);
                    if (!$product) {
                        continue;
                    }

                    $qtyOrdered = (int) $poProduct->quantity;
                    $qtySold = $qtyOrdered;

                    // simulasi retur di tempat (kalau bukan success)
                    if ($paymentStatus !== 'success' && $qtyOrdered > 1 && $faker->boolean(30)) {
                        $qtySold = rand(1, $qtyOrdered - 1);
                        if ($qtySold <= 0) {
                            $qtySold = 0;
                        }
                    }

                    $unitPrice = (float) $product->selling_price; // harga asli saat ini
                    $pdisc = (float) ($product->discount_percent ?? 0); // diskon produk (%)
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
                $orderDiscountPercent = $faker->numberBetween(0, 15);
                $totalAmountAfterDiscount = round($subtotalBeforeOrderDiscount * (1 - $orderDiscountPercent / 100), 2);

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
                        'purchase_order_id' => $po->id,
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
                    $this->command->error('Error creating Sales Transaction for PO ' . $po->id . ': ' . $e->getMessage());
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

            // Pastikan total supplier purchase tidak lebih mahal dari total purchase order/sales
            if ($totalSupplierPurchaseAmount > min($totalPurchaseOrderAmount, $totalSalesTransactionAmount)) {
                $this->command->warn('Total Supplier Purchase lebih besar dari total PO/Sales, mohon cek harga dan quantity di seeder.');
            }
            $this->command->info('Created ' . $successfulSupplierPurchases . ' Supplier Purchases.');

            // ============================ Delivery Returns ============================
            $successfulDeliveryReturns = 0;
            if (!empty($salesTransactionsWithPotentialReturns)) {
                $numDeliveryReturns = rand(1, min(5, count($salesTransactionsWithPotentialReturns))); // max 5 returns for now

                for ($i = 0; $i < $numDeliveryReturns; $i++) {
                    $salesTransaction = $faker->randomElement($salesTransactionsWithPotentialReturns);

                    // Only create a return if it hasn't been returned yet (avoid unique constraint for demo)
                    if (DeliveryReturn::where('sales_transaction_id', $salesTransaction->id)->exists()) {
                        continue;
                    }

                    $returnDate = (clone $salesTransaction->delivery_confirmed_at ?? $salesTransaction->invoice_date)->addDays(rand(0, 5)); // Return happens shortly after delivery/invoice
                    if ($returnDate->isAfter(Carbon::now())) {
                        continue; // Don't create future returns
                    }

                    $deliveryReturn = DeliveryReturn::create([
                        'sales_transaction_id' => $salesTransaction->id,
                        'sales_agent_user_id' => $salesTransaction->sales_agent_user_id, // Sales agent yang mengantar
                        'return_date' => $returnDate->format('Y-m-d'),
                        'reason' => $faker->randomElement(['Barang tidak diambil', 'Ukuran salah dari pesanan', 'Barang rusak saat pengantaran']),
                        'status' => $faker->randomElement(['pending_admin_confirmation', 'confirmed_by_admin']), // Langsung ke status awal atau dikonfirmasi
                        'confirmed_by_admin_user_id' => $faker->boolean(70) ? $admins->random()->id : null, // 70% chance to be confirmed by an admin
                        'confirmed_at' => $faker->boolean(70) ? (clone $returnDate)->addDays(rand(0, 2)) : null,
                    ]);
                    $successfulDeliveryReturns++;

                    // DeliveryReturnItem - Only return items that were *not* sold in SalesTransactionItem
                    $notSoldItems = $salesTransaction->items->filter(function ($item) {
                        return $item->quantity_sold < $item->quantity_ordered;
                    });

                    if ($notSoldItems->isNotEmpty()) {
                        $itemToReturn = $notSoldItems->random(); // Randomly pick one item that wasn't fully sold

                        $quantityReturned = $itemToReturn->quantity_ordered - $itemToReturn->quantity_sold;
                        $quantityReturned = rand(1, max(1, $quantityReturned)); // Ensure at least 1 returned, max what wasn't sold

                        $deliveryReturn->items()->create([
                            'product_id' => $itemToReturn->product_id,
                            'quantity_returned' => $quantityReturned, // Quantity in MSU
                        ]);

                        // Simulate Stock Adjustment for returned items by Warehouse Manager
                        if ($deliveryReturn->status === 'confirmed_by_admin') {
                            $warehouseManager = $admins->random()->warehouseManager ?? $faker->randomElement($warehouseManagers); // Assume admin can also be WM or pick random WM
                            if ($warehouseManager) {
                                StockAdjustment::create([
                                    'warehouse_manager_id' => $warehouseManager->id,
                                    'product_id' => $itemToReturn->product_id,
                                    'reason' => 'Pengembalian dari Pengantaran Transaksi #' . $salesTransaction->id,
                                    'quantity' => $quantityReturned,
                                    'adjustment_type' => 'increase',
                                    'source_type' => 'delivery_return',
                                    'source_id' => $deliveryReturn->id,
                                    'adjustment_date' => Carbon::parse($deliveryReturn->confirmed_at ?? $deliveryReturn->return_date)->format('Y-m-d'),
                                ]);
                                // Update actual stock
                                $stock = Stock::where('product_id', $itemToReturn->product_id)->first();
                                if ($stock) {
                                    $stock->increment('quantity', $quantityReturned);
                                } else {
                                    Stock::create(['product_id' => $itemToReturn->product_id, 'quantity' => $quantityReturned]);
                                }
                            }
                        }
                    }
                }
            }
            $this->command->info('Created ' . $successfulDeliveryReturns . ' Delivery Returns.');

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
