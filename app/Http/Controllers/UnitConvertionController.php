<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\UnitConvertion;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class UnitConvertionController
{
    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = UnitConvertion::leftJoin('product_units as from_units', 'from_units.id', '=', 'unit_convertions.from_unit_id')->leftJoin('product_units as to_units', 'to_units.id', '=', 'unit_convertions.to_unit_id')->select('unit_convertions.id as id', 'unit_convertions.product_id as product_id', 'from_units.name as from_unit_name', 'to_units.name as to_unit_name', 'unit_convertions.convertion_factor as convertion_factor')->where('product_id', '=', $request->productId);

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('from_unit_name', function ($row) {
                    return ucfirst($row->from_unit_name);
                })
                ->editColumn('convertion_factor', function ($row) {
                    return $row->convertion_factor . ' ' . ucfirst($row->to_unit_name);
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
                        $query->where('from_units.name', 'like', "%{$searchValue}%")->orWhere('convertion_factor', 'like', "%{$searchValue}%");
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
                        $sortableColumns = ['id', 'from_unit_name', 'convertion_factor']; // Kolom yang bisa di-sort
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

    public function store(Request $request)
    {
        $request->validate(
            [
                'from_unit' => [
                    'required',
                    Rule::unique('unit_convertions', 'from_unit_id')->where(function ($query) use ($request) {
                        return $query->where('product_id', $request->product_id);
                    }),
                ],
                'convertion_factor' => 'required',
            ],
            [
                'from_unit.required' => 'Unit yang ingin dikonversi wajib diisi.',
                'from_unit.unique' => 'Unit yang ingin dikonversi sudah ada.',
                'convertion_factor.required' => 'Nilai konversi wajib diisi.',
            ],
        );

        $unitConvertion = UnitConvertion::create([
            'product_id' => $request->product_id,
            'from_unit_id' => $request->from_unit,
            'to_unit_id' => $request->minimum_selling_unit_id,
            'convertion_factor' => $request->convertion_factor,
        ]);

        if (!$unitConvertion) {
            // Untuk AJAX
            if ($request->ajax()) {
                return response()->json(['error' => 'Unit konversi gagal ditambahkan'], 500);
            }
        }

        // Untuk AJAX
        if ($request->ajax()) {
            return response()->json(['success' => 'Unit konversi berhasil ditambahkan!', 'request' => $request->all()]);
        }
    }

    public function edit(UnitConvertion $unit_convertion, Request $request)
    {
        if ($request->ajax()) {
            if ($unit_convertion) {
                return response()->json(['unit_convertion' => $unit_convertion]);
            } else {
                return response()->json(['error' => 'Konversi tidak ditemukan']);
            }
        }
    }

    public function update(Request $request, UnitConvertion $unit_convertion)
    {
        $request->validate(
            [
                'from_unit' => [
                    'required',
                    Rule::unique('unit_convertions', 'from_unit_id')
                        ->where(function ($query) use ($unit_convertion) {
                            return $query->where('product_id', $unit_convertion->product_id);
                        })
                        ->ignore($unit_convertion->id), // <-- Tambahkan ini,
                ],
                'convertion_factor' => 'required',
            ],
            [
                'from_unit.required' => 'Unit yang ingin dikonversi wajib diisi.',
                'from_unit.unique' => 'Unit yang ingin dikonversi sudah ada.',
                'convertion_factor.required' => 'Nilai konversi wajib diisi.',
            ],
        );

        $unit = $unit_convertion->update([
            'from_unit_id' => $request->from_unit,
            'convertion_factor' => $request->convertion_factor,
        ]);

        if (!$unit) {
            // Untuk AJAX
            if ($request->ajax()) {
                return response()->json(['error' => 'Konversi gagal diubah'], 500);
            }
        }

        // Untuk AJAX
        if ($request->ajax()) {
            return response()->json(['success' => 'Konversi berhasil diubah!']);
        }
    }

    public function destroy(Request $request, UnitConvertion $unit_convertion)
    {
        if ($request->ajax()) {
            try {
                $unit_convertion->delete();
                return response()->json(['success' => 'Konversi berhasil dihapus!']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus konversi: ' . $e->getMessage(), ['product_unit_id' => $unit_convertion->id]);
                return response()->json(['error' => 'Gagal menghapus konversi. ' . $e->getMessage()], 500);
            }
        } else {
            $unit_convertion->delete();
            return redirect()->route('owner.master_data.product.detail', $unit_convertion->product_id)->with('success', 'Konversi berhasil dihapus!');
        }
    }
}
