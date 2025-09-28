<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\SalesTransaction;
use App\Models\Customer;
use App\Models\Admin;
use App\Models\SalesAgent;
use App\Models\Product;

class SalesTransactionFactory extends Factory
{
    protected $model = SalesTransaction::class;

    public function definition(): array
    {
        // Tanggal order & invoice
        $orderDate = $this->faker->dateTimeBetween('-4 months', 'now');
        $invoiceDate = $this->faker->dateTimeBetween($orderDate, 'now');

        // Status random
        $paymentStatus = $this->faker->randomElement(['pending', 'process', 'cancelled', 'success']);

        // Generate invoice_id unik per hari: INV-ddmmyyyy-####
        $invoiceDateStr = Carbon::instance($invoiceDate)->format('Y-m-d');
        $invoiceDateForId = Carbon::instance($invoiceDate)->format('dmY');
        $todayCount = SalesTransaction::whereDate('invoice_date', $invoiceDateStr)->count() + 1;
        $invoiceId = 'INV-' . $invoiceDateForId . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

        return [
            'customer_id' => Customer::query()->inRandomOrder()->value('id'),
            'admin_id' => null, // diisi saat making kalau status success/process
            'sales_agent_id' => null, // diisi saat making kalau status success/process
            'invoice_id' => $invoiceId,
            'invoice_date' => $invoiceDateStr,
            'initial_total_amount' => 0,
            'final_total_amount' => 0,
            'note' => 'Lorem ipsum.',
            'transaction_status' => $paymentStatus,
            'delivery_confirmed_at' => in_array($paymentStatus, ['success', 'process']) ? (clone $invoiceDate)->modify('+' . rand(0, 7) . ' days') : null,
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (SalesTransaction $trx) {
            // Isi admin & sales jika status success/process
            if (in_array($trx->transaction_status, ['success', 'process'])) {
                $trx->admin_id = Admin::query()->inRandomOrder()->value('id');
                $trx->sales_agent_id = SalesAgent::query()->inRandomOrder()->value('id');
            } else {
                $trx->admin_id = null;
                $trx->sales_agent_id = null;
                $trx->delivery_confirmed_at = null;
            }
        })->afterCreating(function (SalesTransaction $trx) {
            // ====== Generate Items (FP-Growth friendly) ======
            $useCluster = $this->faker->boolean(70);
            $itemCount = rand(2, 3);

            $anchor = Product::query()->inRandomOrder()->first();
            if (!$anchor) {
                return;
            }

            $candidates = Product::query()
                ->when(
                    Schema::hasColumn('products', 'unit_id') && $anchor->unit_id,
                    function ($q) use ($anchor) {
                        $q->where('unit_id', $anchor->unit_id);
                    },
                    function ($q) use ($anchor) {
                        $q->where('id', '!=', $anchor->id);
                    },
                )
                ->inRandomOrder()
                ->limit(10)
                ->get();

            $picked = collect([$anchor]);
            if ($useCluster) {
                $picked = $picked->merge($candidates->take(max(0, $itemCount - 1)));
            } else {
                $picked = $picked->merge(
                    Product::query()
                        ->where('id', '!=', $anchor->id)
                        ->inRandomOrder()
                        ->limit($itemCount - 1)
                        ->get(),
                );
            }
            $picked = $picked->unique('id')->take($itemCount);

            // Cek kolom opsional
            $hasUnitPriceCol = Schema::hasColumn('sales_transaction_items', 'unit_price');
            $hasProdDiscCol = Schema::hasColumn('sales_transaction_items', 'product_discount_percent');
            $hasUnitAfterCol = Schema::hasColumn('sales_transaction_items', 'unit_price_after_product_discount');
            $hasLineBeforeCol = Schema::hasColumn('sales_transaction_items', 'line_total_before_order_discount');
            $hasLineAfterCol = Schema::hasColumn('sales_transaction_items', 'line_total_after_product_discount');

            $initialTotal = 0;
            $finalTotal = 0;

            foreach ($picked as $product) {
                $basePrice = $product->msu_price ?? $this->faker->numberBetween(5000, 120000);
                $qtyOrdered = $this->faker->numberBetween(1, 6);
                $qtySold = in_array($trx->transaction_status, ['pending', 'cancelled']) ? $this->faker->numberBetween(0, $qtyOrdered) : $qtyOrdered;

                // Diskon produk (0 / 5% / 10% / 15%), 40% kasus tanpa diskon
                $productDiscount = $this->faker->boolean(60) ? $this->faker->randomElement([0.05, 0.1, 0.15]) : 0.0;

                $lineBefore = $basePrice * $qtySold;
                $discAmount = $productDiscount > 0 ? $lineBefore * $productDiscount : 0;
                $lineAfter = $lineBefore - $discAmount;

                $initialTotal += $lineBefore;
                $finalTotal += floor($lineAfter);

                $row = [
                    'product_id' => $product->id,
                    'quantity_ordered' => $qtyOrdered,
                    'quantity_sold' => $qtySold,
                    'msu_price' => $basePrice,
                ];
                if ($hasUnitPriceCol) {
                    $row['unit_price'] = $basePrice;
                }
                if ($hasProdDiscCol) {
                    $row['product_discount_percent'] = $productDiscount;
                }
                if ($hasUnitAfterCol) {
                    $row['unit_price_after_product_discount'] = $productDiscount > 0 ? $basePrice * (1 - $productDiscount) : $basePrice;
                }
                if ($hasLineBeforeCol) {
                    $row['line_total_before_order_discount'] = $lineBefore;
                }
                if ($hasLineAfterCol) {
                    $row['line_total_after_product_discount'] = $lineAfter;
                }

                $trx->sales_transaction_items()->create($row);
            }

            // Update subtotal transaksi + konsistensi status
            $trx->update([
                'initial_total_amount' => $initialTotal,
                'final_total_amount' => $finalTotal,
                'admin_id' => in_array($trx->transaction_status, ['pending', 'cancelled']) ? null : $trx->admin_id,
                'sales_agent_id' => in_array($trx->transaction_status, ['pending', 'cancelled']) ? null : $trx->sales_agent_id,
                'delivery_confirmed_at' => in_array($trx->transaction_status, ['pending', 'cancelled']) ? null : $trx->delivery_confirmed_at,
            ]);
        });
    }

    /** Helper state kalau mau pasti sukses */
    public function successful()
    {
        return $this->state(
            fn() => [
                'transaction_status' => 'success',
                'delivery_confirmed_at' => now(),
            ],
        );
    }

    /** Helper state kalau mau status process */
    public function processing()
    {
        return $this->state(
            fn() => [
                'transaction_status' => 'process',
                'delivery_confirmed_at' => now(),
            ],
        );
    }
}
