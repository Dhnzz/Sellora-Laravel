<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class WarehouseManagerController
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Manajemen Manajer Warehouse',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_warehouse_manager',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Manajer Warehouse',
                    'link' => route('owner.user_management.warehouse_manager.index'),
                ],
            ],
        ];
        return view('owner.user_management.warehouse_manager.page.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = WarehouseManager::latest()->select('id', 'name', 'phone', 'created_at');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i:s');
                })
                ->addColumn('options', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-1">';
                    $btn .= '<a href="' . route('owner.user_management.warehouse_manager.detail', $row->id) . '" class="btn btn-sm btn-primary"><i class="ti ti-eye"></i></a>';
                    $btn .= '<a href="' . route('owner.user_management.warehouse_manager.edit', $row->id) . '" class="btn btn-sm btn-warning"><i class="ti ti-pencil"></i></a>';
                    $btn .= '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '"><i class="ti ti-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['options'])
                ->filter(function ($query) use ($request) {
                    // Filter berdasarkan pencarian global DataTables
                    if ($request->has('search') && !empty($request->input('search')['value'])) {
                        $searchValue = $request->input('search')['value'];
                        $query->where('name', 'like', "%{$searchValue}%")->orWhere('phone', 'like', "%{$searchValue}%");
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
                        $sortableColumns = ['id', 'name', 'phone', 'created_at']; // Kolom yang bisa di-sort
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

    public function getById(WarehouseManager $warehouseManager)
    {
        $data = [
            'title' => 'Detail Manajer Warehouse',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_warehouse_manager',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Manajer Warehouse',
                    'link' => route('owner.user_management.warehouse_manager.index'),
                ],
                [
                    'name' => 'Detail Manajer Warehouse',
                    'link' => route('owner.user_management.warehouse_manager.detail', $warehouseManager->id),
                ],
            ],
        ];

        return view('owner.user_management.warehouse_manager.page.detail', compact('data', 'warehouseManager'));
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Manajer Warehouse',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_warehouse_manager',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Manajer Warehouse',
                    'link' => route('owner.user_management.warehouse_manager.index'),
                ],
                [
                    'name' => 'Tambah Manajer Warehouse',
                    'link' => route('owner.user_management.warehouse_manager.create'),
                ],
            ],
        ];
        return view('owner.user_management.warehouse_manager.page.create', compact('data'));
    }

    public function edit(WarehouseManager $warehouseManager, Request $request)
    {
        $data = [
            'title' => 'Edit Manajer Warehouse',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_warehouse_manager',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Manajer Warehouse',
                    'link' => route('owner.user_management.warehouse_manager.index'),
                ],
                [
                    'name' => 'Edit Manajer Warehouse',
                    'link' => route('owner.user_management.warehouse_manager.edit', $warehouseManager->id),
                ],
            ],
        ];
        return view('owner.user_management.warehouse_manager.page.edit', compact('data', 'warehouseManager'));
    }

    public function resetPassword(WarehouseManager $warehouseManager, Request $request)
    {
        if ($request->ajax()) {
            try {
                $userWarehouseManager = $warehouseManager->user;
                $userWarehouseManager->update([
                    'password' => Hash::make('warehouse_manager123'),
                ]);
                return response()->json(['success' => 'Password ' . $warehouseManager->name . ' berhasil direset']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal mereset password manajer warehouse: ' . $e->getMessage(), ['warehouse_manager_id' => $warehouseManager->id]);
                return response()->json(['error' => 'Gagal mereset password manajer warehouse. ' . $e->getMessage()], 500);
            }
        }
    }

    public function deletePhoto(WarehouseManager $warehouseManager, Request $request)
    {
        if ($request->ajax()) {
            try {
                if ($warehouseManager->photo && Storage::disk('public')->exists($warehouseManager->photo)) {
                    Storage::disk('public')->delete($warehouseManager->photo);
                    $photoPath = 'uploads/images/users/user-1.jpg';
                    $warehouseManager->update([
                        'photo' => $photoPath
                    ]);
                    return response()->json(['success' => 'Berhasil menghapus foto '.$warehouseManager->name, 'photo' => $photoPath]);
                }
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus foto manajer warehouse: ' . $e->getMessage(), ['warehouse_manager_id' => $warehouseManager->id]);
                return response()->json(['error' => 'Gagal menghapus foto manajer warehouse. ' . $e->getMessage()], 500);
            }
        }
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email|unique:users,email',
                'name' => 'required',
                'phone' => 'required|min:12',
                'address' => 'required',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ],
            [
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah terdaftar.',
                'name.required' => 'Nama wajib diisi.',
                'phone.required' => 'Nomor telepon wajib diisi.',
                'phone.min' => 'Nomor telepon minimal 12 digit.',
                'address.required' => 'Alamat wajib diisi.',
                'photo.image' => 'File foto harus berupa gambar.',
                'photo.mimes' => 'Foto harus berformat jpeg, png, jpg, atau gif.',
                'photo.max' => 'Ukuran foto maksimal 2MB.',
            ],
        );

        // Cek apakah ada file foto yang diupload
        $photoPath = 'uploads/images/users/user-1.jpg';
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $photoPath = Storage::disk('public')->putFileAs('uploads/images/users', $file, $filename);
        }

        $password = 'warehouse_manager123';
        if ($request->password != null) {
            $password = $request->password;
        }
        $userWarehouseManager = User::create([
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);
        $userWarehouseManager->assignRole('warehouse');

        $warehouse_manager = WarehouseManager::create([
            'user_id' => $userWarehouseManager->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'photo' => $photoPath,
            'address' => $request->address,
        ]);

        if (!$warehouse_manager) {
            return redirect()->route('owner.user_management.warehouse_manager.index')->with('error', 'Manajer warehouse gagal ditambahkan');
        }

        return redirect()->route('owner.user_management.warehouse_manager.index')->with('success', 'Manajer warehouse berhasil ditambahkan');
    }

    public function update(Request $request, WarehouseManager $warehouseManager)
    {
        $request->validate(
            [
                'email' => 'required|email|unique:users,email,' . $warehouseManager->user_id,
                'name' => 'required',
                'phone' => 'required|min:12',
                'address' => 'required',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ],
            [
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah terdaftar.',
                'name.required' => 'Nama wajib diisi.',
                'phone.required' => 'Nomor telepon wajib diisi.',
                'phone.min' => 'Nomor telepon minimal 12 digit.',
                'address.required' => 'Alamat wajib diisi.',
                'photo.image' => 'File foto harus berupa gambar.',
                'photo.mimes' => 'Foto harus berformat jpeg, png, jpg, atau gif.',
                'photo.max' => 'Ukuran foto maksimal 2MB.',
            ],
        );

        // Cek apakah ada file foto yang diupload
        $photoPath = $warehouseManager->photo;
        if ($request->hasFile('photo')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $photoPath = Storage::disk('public')->putFileAs('uploads/images/users', $file, $filename);
        }

        $warehouseManager->user->update([
            'email' => $request->email,
        ]);

        $warehouseManager->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'photo' => $photoPath,
            'address' => $request->address,
        ]);

        if (!$warehouseManager) {
            return redirect()->route('owner.user_management.warehouse_manager.index')->with('error', 'Manajer warehouse gagal diupdate');
        }

        return redirect()->route('owner.user_management.warehouse_manager.index')->with('success', 'Manajer warehouse berhasil diupdate');
    }

    public function destroy(Request $request, WarehouseManager $warehouseManager)
    {
        if ($warehouseManager->photo != 'uploads/images/users/user-1.jpg') {
            if ($warehouseManager->photo && Storage::disk('public')->exists($warehouseManager->photo)) {
                Storage::disk('public')->delete(paths: $warehouseManager->photo);
            } else {
                // Opsional: Log peringatan jika path gambar ada di DB tapi file tidak ditemukan di storage
                // Ini bisa terjadi jika file sudah dihapus secara manual atau ada inkonsistensi data
                if ($warehouseManager->photo) {
                    \Log::warning('File gambar tidak ditemukan di storage saat mencoba menghapus: ' . $warehouseManager->photo);
                }
            }
        }

        if ($request->ajax()) {
            try {
                $userWarehouseManager = $warehouseManager->user;
                $userWarehouseManager->delete();
                $warehouseManager->delete();
                return response()->json(['success' => 'Manajer warehouse berhasil dihapus!']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus manajer warehouse: ' . $e->getMessage(), ['warehouse_manager_id' => $warehouseManager->id]);
                return response()->json(['error' => 'Gagal menghapus manajer warehouse. ' . $e->getMessage()], 500);
            }
        } else {
            $warehouseManager->delete();
            return redirect()->route('owner.user_management.warehouse_manager.index')->with('success', 'Manajer warehouse berhasil dihapus!');
        }
    }
}
