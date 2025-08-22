<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductBrand;
use Illuminate\Http\Request;
use App\Models\UnitConvertion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ProductController
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Manajemen Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'master_data_product',
            'breadcrumbs' => [
                [
                    'name' => 'Master Data',
                    'link' => '#',
                ],
                [
                    'name' => 'Produk',
                    'link' => route('owner.master_data.product.index'),
                ],
            ],
        ];
        // Untuk tampilan awal, $products sudah di-paginate di atas
        return view('owner.master_data.product.page.index', compact('data'));
    }

    public function test()
    {
        $data = Product::latest()->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')->rightJoin('product_units', 'product_units.id', '=', 'products.minimum_selling_unit_id')->rightJoin('product_brands', 'product_brands.id', '=', 'products.product_brand_id')->select('products.name', 'products.created_at', 'discount', 'selling_price')->orderBy('discount', 'desc')->get();
        $discount = '';

        dd($data);
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = Product::latest()->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')->rightJoin('product_units', 'product_units.id', '=', 'products.minimum_selling_unit_id')->rightJoin('product_brands', 'product_brands.id', '=', 'products.product_brand_id')->select('products.id', 'products.name as product_name', 'products.discount as product_discount', 'products.selling_price', 'product_units.name as msu_name', 'stocks.quantity', 'products.created_at')->whereHas('stock')->whereHas('product_brand')->whereHas('product_unit');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('options', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-1">';
                    $btn .= '<a href="' . route('owner.master_data.product.detail', $row->id) . '" class="btn btn-sm btn-primary"><i class="ti ti-eye"></i></a>';
                    $btn .= '<a href="' . route('owner.master_data.product.edit', $row->id) . '" class="btn btn-sm btn-warning"><i class="ti ti-pencil"></i></a>';
                    $btn .= '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '"><i class="ti ti-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->editColumn('selling_price', function ($row) {
                    // Format angka ke dalam format rupiah
                    return 'Rp ' . number_format($row->selling_price, 0, ',', '.');
                })
                ->editColumn('quantity', function ($row) {
                    return $row->quantity . ' ' . ucfirst($row->msu_name);
                })
                ->editColumn('product_discount', function ($row) {
                    // Ubah dari 0.12 menjadi 12%
                    return $row->product_discount * 100 . '%';
                })
                ->rawColumns(['options'])
                ->filter(function ($query) use ($request) {
                    // Filter berdasarkan pencarian global DataTables
                    if ($request->has('search') && !empty($request->input('search')['value'])) {
                        $searchValue = $request->input('search')['value'];
                        $query
                            ->where('products.name', 'like', "%{$searchValue}%")
                            ->orWhere('products.selling_price', 'like', "%{$searchValue}%")
                            ->orWhere('products.discount', 'like', "%{$searchValue}%")
                            ->orWhere('product_units.name', 'like', "%{$searchValue}%")
                            ->orWhere('stocks.quantity', 'like', "%{$searchValue}%");
                    }
                })
                ->order(function ($query) use ($request) {
                    // Sortir berdasarkan kolom yang diminta DataTables
                    if ($request->has('order')) {
                        $order = $request->input('order')[0];
                        $columnIndex = $order['column'];
                        $columnName = $request->input('columns')[$columnIndex]['data'];
                        $sortDirection = $order['dir'];

                        // Pastikan hanya kolom yang boleh di-sort yang diproses
                        $sortableColumns = ['id', 'product_name', 'selling_price', 'msu_name', 'product_discount', 'quantity']; // Kolom yang bisa di-sort
                        if (in_array($columnName, $sortableColumns)) {
                            $query->orderBy($columnName, $sortDirection);
                        } elseif ($columnName === 'DT_RowIndex') {
                            // Abaikan sorting untuk kolom nomor urut jika tidak diperlukan
                            // Atau tambahkan default sorting jika perlu
                            $query->orderBy('created_at', 'desc');
                        }
                    }
                })
                ->make(true);
        }
    }

    public function getById(Product $product)
    {
        $unit_to_convert = ProductUnit::where('id', '!=', $product->minimum_selling_unit_id)->get();
        $data = [
            'title' => 'Detail Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'master_data_product',
            'breadcrumbs' => [
                [
                    'name' => 'Master Data',
                    'link' => '#',
                ],
                [
                    'name' => 'Produk',
                    'link' => route('owner.master_data.product.index'),
                ],
                [
                    'name' => 'Detail Produk',
                    'link' => route('owner.master_data.product.detail', $product->id),
                ],
            ],
            'unit_to_convert' => $unit_to_convert,
        ];

        return view('owner.master_data.product.page.detail', compact('data', 'product'));
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'master_data_product',
            'breadcrumbs' => [
                [
                    'name' => 'Master Data',
                    'link' => '#',
                ],
                [
                    'name' => 'Produk',
                    'link' => route('owner.master_data.product.index'),
                ],
                [
                    'name' => 'Tambah Produk',
                    'link' => route('owner.master_data.product.create'),
                ],
            ],
            'product_brands' => ProductBrand::all(),
            'product_units' => ProductUnit::all(),
        ];
        return view('owner.master_data.product.page.create', compact('data'));
    }

    public function edit(Product $product)
    {
        $data = [
            'title' => 'Edit Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'master_data_product',
            'breadcrumbs' => [
                [
                    'name' => 'Master Data',
                    'link' => '#',
                ],
                [
                    'name' => 'Produk',
                    'link' => route('owner.master_data.product.index'),
                ],
                [
                    'name' => 'Edit Produk',
                    'link' => route('owner.master_data.product.edit', $product->id),
                ],
            ],
            'product_brands' => ProductBrand::all(),
            'product_units' => ProductUnit::all(),
        ];
        return view('owner.master_data.product.page.edit', compact('data', 'product'));
    }

    public function deleteImage(Product $product, Request $request)
    {
        if ($request->ajax()) {
            try {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                    $imagePath = 'uploads/images/products/product-1.png';
                    $product->update([
                        'image' => $imagePath,
                    ]);
                    return response()->json(['success' => 'Berhasil menghapus gambar ' . $product->name, 'image' => $imagePath]);
                }
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus gambar produk: ' . $e->getMessage(), ['product_id' => $product->id]);
                return response()->json(['error' => 'Gagal menghapus gambar produk. ' . $e->getMessage()], 500);
            }
        }
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'name' => 'required',
                'product_brand' => 'required',
                'product_unit' => 'required',
                'selling_price' => 'required|numeric',
                'stock' => 'nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ],
            [
                'name.required' => 'Nama produk wajib diisi.',
                'product_brand.required' => 'Brand produk wajib diisi.',
                'product_unit.required' => 'MSU wajib diisi.',
                'selling_price.required' => 'Harga jual wajib diisi.',
                'image.image' => 'File gambar harus berupa gambar.',
                'image.mimes' => 'Gambar harus berformat jpeg, png, jpg, atau gif.',
                'image.max' => 'Ukuran gambar maksimal 2MB.',
            ],
        );

        // Cek apakah ada file foto yang diupload
        $imagePath = 'uploads/images/products/product-1.png';
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $imagePath = Storage::disk('public')->putFileAs('uploads/images/products', $file, $filename);
        }

        $product = Product::create([
            'name' => $request->name,
            'product_brand_id' => $request->product_brand,
            'minimum_selling_unit_id' => $request->product_unit,
            'discount' => 0.0,
            'selling_price' => $request->selling_price,
            'image' => $imagePath,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'quantity' => $request->stock ?? 0,
        ]);

        if (!$product) {
            return redirect()->route('owner.master_data.product.index')->with('error', 'Produk gagal ditambahkan');
        }

        return redirect()->route('owner.master_data.product.index')->with('success', 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate(
            [
                'name' => 'required',
                'product_brand' => 'required',
                'product_unit' => 'required',
                'discount' => 'nullable|numeric|min:10|max:100',
                'selling_price' => 'required|numeric',
                'stock' => 'required',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ],
            [
                'name.required' => 'Nama produk wajib diisi.',
                'product_brand.required' => 'Brand produk wajib diisi.',
                'product_unit.required' => 'MSU wajib diisi.',
                'discount.numeric' => 'Diskon harus berupa angka.',
                'discount.min' => 'Diskon minimal 0%.',
                'discount.max' => 'Diskon maksimal 100%.',
                'selling_price.required' => 'Harga jual wajib diisi.',
                'stock.required' => 'Stock wajib diisi.',
                'image.image' => 'File gambar harus berupa gambar.',
                'image.mimes' => 'Gambar harus berformat jpeg, png, jpg, atau gif.',
                'image.max' => 'Ukuran gambar maksimal 2MB.',
            ],
        );

        // Cek apakah ada file foto yang diupload
        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $imagePath = Storage::disk('public')->putFileAs('uploads/images/products', $file, $filename);
        }

        $product->stock->update([
            'quantity' => $request->stock,
        ]);

        $product->update([
            'name' => $request->name,
            'product_brand_id' => $request->product_brand,
            'minimum_selling_unit_id' => $request->product_unit,
            'discount' => floatval($request->discount) / 100,
            'selling_price' => $request->selling_price,
            'image' => $imagePath,
        ]);

        if (!$product) {
            return redirect()->route('owner.master_data.product.index')->with('error', 'Produk gagal diupdate');
        }

        return redirect()->route('owner.master_data.product.index')->with('success', 'Produk berhasil diupdate');
    }

    public function destroy(Request $request, Product $product)
    {
        if ($product->image != 'uploads/images/products/product-1.png') {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete(paths: $product->image);
            } else {
                // Opsional: Log peringatan jika path gambar ada di DB tapi file tidak ditemukan di storage
                // Ini bisa terjadi jika file sudah dihapus secara manual atau ada inkonsistensi data
                if ($product->image) {
                    \Log::warning('File gambar tidak ditemukan di storage saat mencoba menghapus: ' . $product->image);
                }
            }
        }

        if ($request->ajax()) {
            try {
                $product->delete();
                return response()->json(['success' => 'Produk berhasil dihapus!']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus produk: ' . $e->getMessage(), ['product' => $product->id]);
                return response()->json(['error' => 'Gagal menghapus produk. ' . $e->getMessage()], 500);
            }
        } else {
            $product->delete();
            return redirect()->route('owner.master_data.product.index')->with('success', 'Produk berhasil dihapus!');
        }
    }
}
