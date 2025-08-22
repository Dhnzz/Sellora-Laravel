<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SupplierController
{
    public function index(Request $request)
    {
        $role = Auth::user()->getRoleNames()->first();
        $data = [
            'title' => 'Manajemen Supplier',
            'role' => $role,
            'active' => 'master_data_supplier',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen Supplier',
                    'link' => '#',
                ],
                [
                    'name' => 'Supplier',
                    'link' => route($role.'.master_data.supplier.index'),
                ],
            ],
        ];
        // Untuk tampilan awal, $admins sudah di-paginate di atas
        return view($role.'.master_data.supplier.page.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = Supplier::latest()->select('id', 'name', 'address', 'phone', 'created_at');

            return DataTables::of($data)
                ->addIndexColumn()
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
                        $query
                            ->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('address', 'like', "%{$searchValue}%")
                            ->orWhere('phone', 'like', "%{$searchValue}%")
                            ->orWhere('created_at', 'like', "%{$searchValue}%");
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
                        $sortableColumns = ['id', 'name', 'address', 'phone', 'created_at']; // Kolom yang bisa di-sort
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

    public function edit(Supplier $supplier, Request $request)
    {
        if ($request->ajax()) {
            if ($supplier) {
                return response()->json(['supplier' => $supplier]);
            } else {
                return response()->json(['error' => 'Supplier tidak ditemukan']);
            }
        }
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'name' => 'required',
                'address' => 'required',
                'phone' => 'required',
            ],
            [
                'name.required' => 'Nama supplier wajib diisi.',
                'address.required' => 'Alamat supplier wajib diisi.',
                'phone.required' => 'Nomor telepon supplier wajib diisi.',
            ],
        );

        $supplier = Supplier::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
        ]);

        if (!$supplier) {
            // Untuk AJAX
            if ($request->ajax()) {
                return response()->json(['error' => 'Supplier gagal ditambahkan'], 500);
            }
        }

        // Untuk AJAX
        if ($request->ajax()) {
            return response()->json(['success' => 'Supplier berhasil ditambahkan!']);
        }
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate(
            [
                'name' => 'required',
                'address' => 'required',
                'phone' => 'required',
            ],
            [
                'name.required' => 'Nama supplier wajib diisi.',
                'address.required' => 'Alamat supplier wajib diisi.',
                'phone.required' => 'Nomor telepon supplier wajib diisi.',
            ],
        );

        $supplierUpdate = $supplier->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
        ]);

        if (!$supplierUpdate) {
            // Untuk AJAX
            if ($request->ajax()) {
                return response()->json(['error' => 'Supplier gagal diubah'], 500);
            }
        }

        // Untuk AJAX
        if ($request->ajax()) {
            return response()->json(['success' => 'Supplier berhasil diubah!']);
        }
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        if ($request->ajax()) {
            try {
                $supplier->delete();
                return response()->json(['success' => 'Supplier berhasil dihapus!']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus supplier: ' . $e->getMessage(), ['product_brand_id' => $supplier->id]);
                return response()->json(['error' => 'Gagal menghapus supplier. ' . $e->getMessage()], 500);
            }
        } else {
            $supplier->delete();
            return redirect()->route('owner.master_data.product_brand.index')->with('success', 'Supplier berhasil dihapus!');
        }
    }
}
