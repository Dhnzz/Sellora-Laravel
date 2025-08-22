<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\ProductBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ProductBrandController
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Manajemen Brand Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'master_data_product_brand',
            'breadcrumbs' => [
                [
                    'name' => 'Master Data',
                    'link' => '#',
                ],
                [
                    'name' => 'Brand Produk',
                    'link' => route('owner.master_data.product_brand.index'),
                ],
            ],
        ];
        // Untuk tampilan awal, $product_brands sudah di-paginate di atas
        return view('owner.master_data.product_brand.page.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = ProductBrand::latest()->select('id', 'name', 'created_at');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i:s');
                })
                ->addColumn('options', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-1">';
                    $btn .= '<button type="button" class="btn btn-sm btn-warning edit-btn" data-id="' . $row->id . '"><i class="ti ti-pencil"></i></a>';
                    $btn .= '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '"><i class="ti ti-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['options'])
                ->filter(function ($query) use ($request) {
                    // Filter berdasarkan pencarian global DataTables
                    if ($request->has('search') && !empty($request->input('search')['value'])) {
                        $searchValue = $request->input('search')['value'];
                        $query->where('name', 'like', "%{$searchValue}%")->orWhere('created_at', 'like', "%{$searchValue}%");
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
                        $sortableColumns = ['id', 'name', 'created_at']; // Kolom yang bisa di-sort
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

    public function edit(ProductBrand $productBrand, Request $request)
    {
        if ($request->ajax()) {
            if ($productBrand) {
                return response()->json(['productBrand' => $productBrand]);
            } else {
                return response()->json(['error' => 'Brand produk tidak ditemukan']);
            }
        }
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'name' => 'required',
            ],
            [
                'name.required' => 'Nama brand produk wajib diisi.',
            ],
        );

        $brand = ProductBrand::create([
            'name' => $request->name,
        ]);

        if (!$brand) {
            // Untuk AJAX
            if ($request->ajax()) {
                return response()->json(['error' => 'Brand produk gagal ditambahkan'], 500);
            }
        }

        // Untuk AJAX
        if ($request->ajax()) {
            return response()->json(['success' => 'Brand produk berhasil ditambahkan!']);
        }
    }

    public function update(Request $request, ProductBrand $productBrand)
    {
        $request->validate(
            [
                'name' => 'required',
            ],
            [
                'name.required' => 'Nama brand produk wajib diisi.',
            ],
        );

        $brand = $productBrand->update([
            'name' => $request->name,
        ]);

        if (!$brand) {
            // Untuk AJAX
            if ($request->ajax()) {
                return response()->json(['error' => 'Brand produk gagal diubah'], 500);
            }
        }

        // Untuk AJAX
        if ($request->ajax()) {
            return response()->json(['success' => 'Brand produk berhasil diubah!']);
        }
    }

    public function destroy(Request $request, ProductBrand $productBrand)
    {
        if ($request->ajax()) {
            try {
                $productBrand->delete();
                return response()->json(['success' => 'Brand produk berhasil dihapus!']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus brand produk: ' . $e->getMessage(), ['product_brand_id' => $productBrand->id]);
                return response()->json(['error' => 'Gagal menghapus brand produk. ' . $e->getMessage()], 500);
            }
        } else {
            $productBrand->delete();
            return redirect()->route('owner.master_data.product_brand.index')->with('success', 'Brand produk berhasil dihapus!');
        }
    }
}
