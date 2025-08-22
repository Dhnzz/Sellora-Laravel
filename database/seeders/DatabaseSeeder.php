<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin;
use App\Models\Owner;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SalesAgent;
use App\Models\ProductUnit;
use App\Models\ProductBrand;
use App\Models\ProductBundle;
use App\Models\PurchaseOrder;
use App\Models\DeliveryReturn;
use App\Models\StockAdjustment;
use Illuminate\Database\Seeder;
use App\Models\SalesTransaction;
use App\Models\SupplierPurchase;
use App\Models\WarehouseManager;
use Database\Seeders\UserSeeder;
use App\Models\ProductBundleItem;
use App\Models\PurchaseOrderItem;
use App\Models\DeliveryReturnItem;
use App\Models\MonthlyBookClosing;
use App\Models\ProductAssociation;
use Database\Seeders\MasterSeeder;
use Spatie\Permission\Models\Role;
use App\Models\SalesTransactionItem;
use App\Models\SupplierPurchaseItem;
use App\Models\MonthlyRevenuePrediction;
use Database\Seeders\ProductRelatedSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User Seeder
        $this->call(UserSeeder::class);

        // Master Seeder (Produk Unit, Produk Brand, Supplier)
        $this->call(MasterSeeder::class);

        // Product Related Seeder (Product, Bundle, Bundle Items)
        $this->call(ProductRelatedSeeder::class);

        $this->call(UnitConvertionSeeder::class);

        // Purchase And Sales Seeder (PO, Sales Transaction, Supplier Purchase, Delivery Return, Stock Adjustment)
        $this->call(PurchaseAndSalesSeeder::class);

        

        // // ProductBundle
        // $productBundle = ProductBundle::create([
        //     'bundle_name' => 'Bundle A',
        //     'description' => 'Paket produk A',
        //     'start_date' => now()->startOfMonth(),
        //     'end_date' => now()->endOfMonth(),
        //     'special_bundle_price' => 18000,
        //     'original_price' => 20000,
        //     'is_active' => true,
        // ]);

        // // ProductBundleItem
        // $productBundleItem = ProductBundleItem::create([
        //     'product_bundle_id' => $productBundle->id,
        //     'product_id' => $product->id,
        //     'quantity' => 2,
        // ]);

        // // SupplierPurchase
        // $supplierPurchase = SupplierPurchase::create([
        //     'admin_id' => $admin->id,
        //     'supplier_id' => $supplier->id,
        //     'purchase_date' => now(),
        //     'invoice_number' => 'INV-001',
        //     'total_amount' => 500000,
        //     'notes' => 'Pembelian awal',
        // ]);

        // // SupplierPurchaseItem
        // $supplierPurchaseItem = SupplierPurchaseItem::create([
        //     'supplier_purchase_id' => $supplierPurchase->id,
        //     'product_id' => $product->id,
        //     'quantity' => 100,
        //     'product_unit_id' => $unit->id,
        //     'product_unit_price' => 5000,
        // ]);

        // // StockAdjustment
        // $stockAdjustment = StockAdjustment::create([
        //     'warehouse_manager_id' => $warehouseManager->id,
        //     'product_id' => $product->id,
        //     'reason' => 'Penyesuaian stok awal',
        //     'quantity' => 10,
        //     'adjustment_type' => 'increase',
        //     'source_type' => 'physical_check',
        //     'adjustment_date' => now(),
        // ]);

        // // MonthlyRevenuePrediction
        // $monthlyRevenuePrediction = MonthlyRevenuePrediction::create([
        //     'prediction_month' => now()->format('Y-m-01'),
        //     'predicted_revenue' => 1000000,
        //     'prediction_date' => now(),
        // ]);

        // // MonthlyBookClosing
        // $monthlyBookClosing = MonthlyBookClosing::create([
        //     'closing_month' => now()->month,
        //     'closing_year' => now()->year,
        //     'closed_at' => now(),
        //     'admin_id' => $admin->id,
        // ]);

        // // DeliveryReturn
        // $deliveryReturn = DeliveryReturn::create([
        //     'sales_transaction_id' => $salesTransaction->id,
        //     'return_date' => now(),
        //     'reason' => 'Barang rusak',
        //     'status' => 'pending',
        //     'admin_id' => $admin->id,
        //     'confirmed_at' => now(),
        // ]);

        // // DeliveryReturnItem
        // $deliveryReturnItem = DeliveryReturnItem::create([
        //     'delivery_return_id' => $deliveryReturn->id,
        //     'product_id' => $product->id,
        //     'quantity_returned' => 2,
        // ]);

        // // ProductAssociation
        // $productAssociation = ProductAssociation::create([
        //     'atecedent_product_ids' => json_encode([$product->id]),
        //     'consequent_product_ids' => json_encode([$product->id]),
        //     'support' => 0.5,
        //     'confidence' => 0.7,
        //     'lift' => 1.2,
        //     'analysis_date' => now(),
        // ]);
    }
}
