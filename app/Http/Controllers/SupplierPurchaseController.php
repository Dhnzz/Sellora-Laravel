<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SupplierPurchaseController
{
    public function index()
    {
        $data = [
            'title' => 'Belanja Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'breadcrumbs' => [
                [
                    'name' => 'Operasional',
                    'link' => route('owner.supplier_purchase.index'),
                ],
                [
                    'name' => 'Daftar Belanja',
                    'link' => route('owner.supplier_purchase.index'),
                ],
            ],
        ];

        return view('owner.supplier_purchase.page.index', compact('data'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $products = Product::with(['product_unit', 'product_brand'])->get();

        $data = [
            'title' => 'Tambah Belanja Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'breadcrumbs' => [
                [
                    'name' => 'Operasional',
                    'link' => route('owner.supplier_purchase.index'),
                ],
                [
                    'name' => 'Daftar Belanja',
                    'link' => route('owner.supplier_purchase.index'),
                ],
                [
                    'name' => 'Tambah Belanja',
                    'link' => route('owner.supplier_purchase.create'),
                ],
            ],
        ];

        return view('owner.supplier_purchase.page.create', compact('data', 'suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'invoice_number' => 'required|string|unique:supplier_purchases,invoice_number',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ],
                422,
            );
        }

        try {
            DB::beginTransaction();

            // Hitung total amount
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            // Buat SupplierPurchase
            $supplierPurchase = SupplierPurchase::create([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'invoice_number' => $request->invoice_number,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
            ]);

            // Buat SupplierPurchaseItem
            foreach ($request->items as $item) {
                SupplierPurchaseItem::create([
                    'supplier_purchase_id' => $supplierPurchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity']
                ]);

                // Update stok produk
                $stock = Stock::where('product_id', $item['product_id'])->first();
                if ($stock) {
                    $stock->increment('quantity', $item['quantity']);
                } else {
                    Stock::create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Belanja produk berhasil ditambahkan',
                'data' => $supplierPurchase,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function show($id)
    {
        $supplierPurchase = SupplierPurchase::with(['supplier', 'supplier_purchase_item.product.product_unit'])->findOrFail($id);

        $data = [
            'title' => 'Detail Belanja Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'breadcrumbs' => [
                [
                    'name' => 'Operasional',
                    'link' => route('owner.supplier_purchase.index'),
                ],
                [
                    'name' => 'Daftar Belanja',
                    'link' => route('owner.supplier_purchase.index'),
                ],
                [
                    'name' => 'Detail Belanja',
                    'link' => route('owner.supplier_purchase.show', $id),
                ],
            ],
        ];

        return view('owner.supplier_purchase.page.show', compact('data', 'supplierPurchase'));
    }

    public function edit($id)
    {
        $supplierPurchase = SupplierPurchase::with(['supplier_purchase_item.product.product_unit'])->findOrFail($id);
        $suppliers = Supplier::all();
        $products = Product::with(['product_unit', 'product_brand'])->get();

        $data = [
            'title' => 'Edit Belanja Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'breadcrumbs' => [
                [
                    'name' => 'Operasional',
                    'link' => route('owner.supplier_purchase.index'),
                ],
                [
                    'name' => 'Daftar Belanja',
                    'link' => route('owner.supplier_purchase.index'),
                ],
                [
                    'name' => 'Edit Belanja',
                    'link' => route('owner.supplier_purchase.edit', $id),
                ],
            ],
        ];

        return view('owner.supplier_purchase.page.edit', compact('data', 'supplierPurchase', 'suppliers', 'products'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'invoice_number' => 'required|string|unique:supplier_purchases,invoice_number,' . $id,
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ],
                422,
            );
        }

        try {
            DB::beginTransaction();

            $supplierPurchase = SupplierPurchase::findOrFail($id);

            // Kembalikan stok lama
            foreach ($supplierPurchase->supplier_purchase_item as $item) {
                $stock = Stock::where('product_id', $item->product_id)->first();
                if ($stock) {
                    $stock->decrement('quantity', $item->quantity);
                }
            }

            // Hapus item lama
            $supplierPurchase->supplier_purchase_item()->delete();

            // Hitung total amount baru
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            // Update SupplierPurchase
            $supplierPurchase->update([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'invoice_number' => $request->invoice_number,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
            ]);

            // Buat SupplierPurchaseItem baru
            foreach ($request->items as $item) {
                SupplierPurchaseItem::create([
                    'supplier_purchase_id' => $supplierPurchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                ]);

                // Update stok produk
                $stock = Stock::where('product_id', $item['product_id'])->first();
                if ($stock) {
                    $stock->increment('quantity', $item['quantity']);
                } else {
                    Stock::create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Belanja produk berhasil diupdate',
                'data' => $supplierPurchase,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $supplierPurchase = SupplierPurchase::findOrFail($id);

            // Kembalikan stok
            foreach ($supplierPurchase->supplier_purchase_item as $item) {
                $stock = Stock::where('product_id', $item->product_id)->first();
                if ($stock) {
                    $stock->decrement('quantity', $item->quantity);
                }
            }

            // Hapus item dan purchase
            $supplierPurchase->supplier_purchase_item()->delete();
            $supplierPurchase->delete();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Belanja produk berhasil dihapus',
                ]);
            } else {
                return redirect()->route('owner.supplier_purchase.index')
                    ->with('success', 'Belanja produk berhasil dihapus');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = SupplierPurchase::with(['supplier'])
                ->latest()
                ->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('supplier_name', function ($row) {
                    return $row->supplier->name;
                })
                ->addColumn('total_amount_formatted', function ($row) {
                    return 'Rp ' . number_format($row->total_amount, 0, ',', '.');
                })
                ->addColumn('purchase_date_formatted', function ($row) {
                    return date('d/m/Y', strtotime($row->purchase_date));
                })
                ->addColumn('item_count', function ($row) {
                    return $row->supplier_purchase_item->count();
                })
                // Kolom 'note' tidak ditambahkan di sini sesuai instruksi
                ->addColumn('options', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-1">';
                    $btn .= '<a href="' . route('owner.supplier_purchase.show', $row->id) . '" class="btn btn-sm btn-primary"><i class="ti ti-eye"></i></a>';
                    $btn .= '<a href="' . route('owner.supplier_purchase.edit', $row->id) . '" class="btn btn-sm btn-warning"><i class="ti ti-pencil"></i></a>';
                    $btn .= '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '"><i class="ti ti-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['options'])
                ->make(true);
        }
    }

    public function getProducts()
    {
        $products = Product::with(['product_unit', 'product_brand'])->get();

        return response()->json([
            'status' => true,
            'data' => $products,
        ]);
    }
}
