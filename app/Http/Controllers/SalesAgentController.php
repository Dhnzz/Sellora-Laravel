<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\SalesAgent;
use Illuminate\Http\Request;
use App\Models\WarehouseManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class SalesAgentController
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Manajemen Agen Sales',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_sales_agent',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Agen Sales',
                    'link' => route('owner.user_management.sales.index'),
                ],
            ],
        ];
        return view('owner.user_management.sales.page.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = SalesAgent::latest()->select('id', 'name', 'phone', 'created_at');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i:s');
                })
                ->addColumn('options', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-1">';
                    $btn .= '<a href="' . route('owner.user_management.sales.detail', $row->id) . '" class="btn btn-sm btn-primary"><i class="ti ti-eye"></i></a>';
                    $btn .= '<a href="' . route('owner.user_management.sales.edit', $row->id) . '" class="btn btn-sm btn-warning"><i class="ti ti-pencil"></i></a>';
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

    public function getById(SalesAgent $sales)
    {
        $data = [
            'title' => 'Detail Agen Sales',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_sales_agent',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Agen Sales',
                    'link' => route('owner.user_management.sales.index'),
                ],
                [
                    'name' => 'Detail Agen Sales',
                    'link' => route('owner.user_management.sales.detail', $sales->id),
                ],
            ],
        ];

        return view('owner.user_management.sales.page.detail', compact('data', 'sales'));
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Agen Sales',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_sales_agent',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Agen Sales',
                    'link' => route('owner.user_management.sales.index'),
                ],
                [
                    'name' => 'Tambah Agen Sales',
                    'link' => route('owner.user_management.sales.create'),
                ],
            ],
        ];
        return view('owner.user_management.sales.page.create', compact('data'));
    }

    public function edit(SalesAgent $sales)
    {
        $data = [
            'title' => 'Edit Agen Sales',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_sales_agent',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Agen Sales',
                    'link' => route('owner.user_management.sales.index'),
                ],
                [
                    'name' => 'Edit Agen Sales',
                    'link' => route('owner.user_management.sales.edit', $sales->id),
                ],
            ],
        ];
        return view('owner.user_management.sales.page.edit', compact('data', 'sales'));
    }

    public function resetPassword(SalesAgent $sales, Request $request)
    {
        if ($request->ajax()) {
            try {
                $userSalesAgent = $sales->user;
                $userSalesAgent->update([
                    'password' => Hash::make('sales_agent123'),
                ]);
                return response()->json(['success' => 'Password ' . $sales->name . ' berhasil direset']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal mereset password agen sales: ' . $e->getMessage(), ['sales_agent_id' => $sales->id]);
                return response()->json(['error' => 'Gagal mereset password agen sales. ' . $e->getMessage()], 500);
            }
        }
    }

    public function deletePhoto(SalesAgent $sales, Request $request)
    {
        if ($request->ajax()) {
            try {
                if ($sales->photo && Storage::disk('public')->exists($sales->photo)) {
                    Storage::disk('public')->delete($sales->photo);
                    $photoPath = 'uploads/images/users/user-1.jpg';
                    $sales->update([
                        'photo' => $photoPath
                    ]);
                    return response()->json(['success' => 'Berhasil menghapus foto '.$sales->name, 'photo' => $photoPath]);
                }
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus foto agen sales: ' . $e->getMessage(), ['sales_agent_id' => $sales->id]);
                return response()->json(['error' => 'Gagal menghapus foto agen sales. ' . $e->getMessage()], 500);
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

        $password = 'sales_agent123';
        if ($request->password != null) {
            $password = $request->password;
        }
        $userSalesAgent = User::create([
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);
        $userSalesAgent->assignRole('sales');

        $sales_agent = SalesAgent::create([
            'user_id' => $userSalesAgent->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'photo' => $photoPath,
            'address' => $request->address,
        ]);

        if (!$sales_agent) {
            return redirect()->route('owner.user_management.sales.index')->with('error', 'Agen sales gagal ditambahkan');
        }

        return redirect()->route('owner.user_management.sales.index')->with('success', 'Agen sales berhasil ditambahkan');
    }

    public function update(Request $request, SalesAgent $sales)
    {
        $request->validate(
            [
                'email' => 'required|email|unique:users,email,' . $sales->user_id,
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
        $photoPath = $sales->photo;
        if ($request->hasFile('photo')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $photoPath = Storage::disk('public')->putFileAs('uploads/images/users', $file, $filename);
        }

        $sales->user->update([
            'email' => $request->email,
        ]);

        $sales->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'photo' => $photoPath,
            'address' => $request->address,
        ]);

        if (!$sales) {
            return redirect()->route('owner.user_management.sales.index')->with('error', 'Agen sales gagal diupdate');
        }

        return redirect()->route('owner.user_management.sales.index')->with('success', 'Agen sales berhasil diupdate');
    }

    public function destroy(Request $request, SalesAgent $sales)
    {
        if ($sales->photo != 'uploads/images/users/user-1.jpg') {
            if ($sales->photo && Storage::disk('public')->exists($sales->photo)) {
                Storage::disk('public')->delete(paths: $sales->photo);
            } else {
                // Opsional: Log peringatan jika path gambar ada di DB tapi file tidak ditemukan di storage
                // Ini bisa terjadi jika file sudah dihapus secara manual atau ada inkonsistensi data
                if ($sales->photo) {
                    \Log::warning('File gambar tidak ditemukan di storage saat mencoba menghapus: ' . $sales->photo);
                }
            }
        }

        if ($request->ajax()) {
            try {
                $userSalesAgent = $sales->user;
                $userSalesAgent->delete();
                $sales->delete();
                return response()->json(['success' => 'Agen sales berhasil dihapus!']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus agen sales: ' . $e->getMessage(), ['sales_agent_id' => $sales->id]);
                return response()->json(['error' => 'Gagal menghapus agen sales. ' . $e->getMessage()], 500);
            }
        } else {
            $sales->delete();
            return redirect()->route('owner.user_management.sales.index')->with('success', 'Agen sales berhasil dihapus!');
        }
    }
}
