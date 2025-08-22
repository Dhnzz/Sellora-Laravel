<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ProductUnitController
{
    public $role;

    public function __construct() {
        $this->role = Auth::user()->getRoleNames()->first();
    }
    public function index(Request $request)
    {
        $data = [
            'title' => 'Manajemen Unit Produk',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'master_data_product_unit',
            'breadcrumbs' => [
                [
                    'name' => 'Master Data',
                    'link' => '#',
                ],
                [
                    'name' => 'Unit Produk',
                    'link' => route($this->role.'.master_data.product_unit.index'),
                ],
            ],
        ];
        // Untuk tampilan awal, $product_units sudah di-paginate di atas
        return view($this->role.'.master_data.product_unit.page.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = ProductUnit::latest()->select('id', 'name', 'created_at');

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

    public function edit(ProductUnit $productUnit, Request $request)
    {
        if ($request->ajax()) {
            if ($productUnit) {
                return response()->json(['productUnit' => $productUnit]);
            } else {
                return response()->json(['error' => 'Unit produk tidak ditemukan']);
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
                'name.required' => 'Nama unit produk wajib diisi.',
            ],
        );

        $unit = ProductUnit::create([
            'name' => $request->name,
        ]);

        if (!$unit) {
            // Untuk AJAX
            if ($request->ajax()) {
                return response()->json(['error' => 'Unit produk gagal ditambahkan'], 500);
            }
        }

        // Untuk AJAX
        if ($request->ajax()) {
            return response()->json(['success' => 'Unit produk berhasil ditambahkan!']);
        }
    }

    public function update(Request $request, ProductUnit $productUnit)
    {
        $request->validate(
            [
                'name' => 'required',
            ],
            [
                'name.required' => 'Nama unit produk wajib diisi.',
            ],
        );

        $unit = $productUnit->update([
            'name' => $request->name,
        ]);

        if (!$unit) {
            // Untuk AJAX
            if ($request->ajax()) {
                return response()->json(['error' => 'Unit produk gagal diubah'], 500);
            }
        }

        // Untuk AJAX
        if ($request->ajax()) {
            return response()->json(['success' => 'Unit produk berhasil diubah!']);
        }
    }

    public function destroy(Request $request, ProductUnit $productUnit)
    {
        if ($request->ajax()) {
            try {
                $productUnit->delete();
                return response()->json(['success' => 'Unit produk berhasil dihapus!']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus unit produk: ' . $e->getMessage(), ['product_unit_id' => $productUnit->id]);
                return response()->json(['error' => 'Gagal menghapus unit produk. ' . $e->getMessage()], 500);
            }
        } else {
            $productUnit->delete();
            return redirect()->route('owner.master_data.product_unit.index')->with('success', 'Unit produk berhasil dihapus!');
        }
    }
}
