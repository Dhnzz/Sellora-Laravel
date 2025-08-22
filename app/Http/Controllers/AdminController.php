<?php

namespace App\Http\Controllers;

use Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Services\AdminService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class AdminController
{
    public function dashboard()
    {
        $data = [
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'dashboard',
            'breadcrumbs' => [
                [
                    'name' => 'Dashboard',
                    'link' => route('owner.dashboard'),
                ],
            ],
        ];
        return view('admin.dashboard', compact('data'));
    }

    public function index(Request $request)
    {
        $data = [
            'title' => 'Manajemen Admin',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_admin',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Admin',
                    'link' => route('owner.user_management.admin.index'),
                ],
            ],
        ];
        // Untuk tampilan awal, $admins sudah di-paginate di atas
        return view('owner.user_management.admin.page.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = Admin::latest()->select('id', 'name', 'phone', 'created_at');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i:s');
                })
                ->addColumn('options', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-1">';
                    $btn .= '<a href="' . route('owner.user_management.admin.detail', $row->id) . '" class="btn btn-sm btn-primary"><i class="ti ti-eye"></i></a>';
                    $btn .= '<a href="' . route('owner.user_management.admin.edit', $row->id) . '" class="btn btn-sm btn-warning"><i class="ti ti-pencil"></i></a>';
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

    public function getById(Admin $admin)
    {
        $data = [
            'title' => 'Detail Admin',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_admin',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Admin',
                    'link' => route('owner.user_management.admin.index'),
                ],
                [
                    'name' => 'Detail Admin',
                    'link' => route('owner.user_management.admin.detail', $admin->id),
                ],
            ],
        ];

        return view('owner.user_management.admin.page.detail', compact('data', 'admin'));
    }

    public function create()
    {
        $admins = Admin::all();
        $data = [
            'title' => 'Tambah Admin',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_admin',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Admin',
                    'link' => route('owner.user_management.admin.index'),
                ],
                [
                    'name' => 'Tambah Admin',
                    'link' => route('owner.user_management.admin.create'),
                ],
            ],
        ];
        return view('owner.user_management.admin.page.create', compact('data'));
    }

    public function edit(Admin $admin)
    {
        $data = [
            'title' => 'Edit Admin',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_admin',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Admin',
                    'link' => route('owner.user_management.admin.index'),
                ],
                [
                    'name' => 'Edit Admin',
                    'link' => route('owner.user_management.admin.edit', $admin->id),
                ],
            ],
        ];
        return view('owner.user_management.admin.page.edit', compact('data', 'admin'));
    }

    public function resetPassword(Admin $admin, Request $request)
    {
        if ($request->ajax()) {
            try {
                $userAdmin = $admin->user;
                $userAdmin->update([
                    'password' => Hash::make('admin123'),
                ]);
                return response()->json(['success' => 'Password ' . $admin->name . ' berhasil direset']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal mereset password admin: ' . $e->getMessage(), ['admin_id' => $admin->id]);
                return response()->json(['error' => 'Gagal mereset password admin. ' . $e->getMessage()], 500);
            }
        }
    }

    public function deletePhoto(Admin $admin, Request $request)
    {
        if ($request->ajax()) {
            try {
                if ($admin->photo && Storage::disk('public')->exists($admin->photo)) {
                    Storage::disk('public')->delete($admin->photo);
                    $photoPath = 'uploads/images/users/user-1.jpg';
                    $admin->update([
                        'photo' => $photoPath,
                    ]);
                    return response()->json(['success' => 'Berhasil menghapus foto ' . $admin->name, 'photo' => $photoPath]);
                }
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus foto admin: ' . $e->getMessage(), ['admin_id' => $admin->id]);
                return response()->json(['error' => 'Gagal menghapus foto admin. ' . $e->getMessage()], 500);
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

        $password = 'admin123';
        if ($request->password != null) {
            $password = $request->password;
        }
        $userAdmin = User::create([
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);
        $userAdmin->assignRole('admin');

        $admin = Admin::create([
            'user_id' => $userAdmin->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'photo' => $photoPath,
            'address' => $request->address,
        ]);

        if (!$admin) {
            return redirect()->route('owner.user_management.admin.index')->with('error', 'Admin gagal ditambahkan');
        }

        return redirect()->route('owner.user_management.admin.index')->with('success', 'Admin berhasil ditambahkan');
    }

    public function update(Request $request, Admin $admin)
    {
        $request->validate(
            [
                'email' => 'required|email|unique:users,email,' . $admin->user_id,
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
        $photoPath = $admin->photo;
        if ($request->hasFile('photo')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $photoPath = Storage::disk('public')->putFileAs('uploads/images/users', $file, $filename);
        }

        $admin->user->update([
            'email' => $request->email,
        ]);

        $admin->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'photo' => $photoPath,
            'address' => $request->address,
        ]);

        if (Auth::user()->getRoleNames()->first() != 'owner') {
            if (!$admin) {
                return redirect()->route('admin.dashboard')->with('error', 'Admin gagal diupdate');
            }
    
            return redirect()->route('admin.dashboard')->with('success', 'Admin berhasil diupdate');
        }

        if (!$admin) {
            return redirect()->route('owner.user_management.admin.index')->with('error', 'Admin gagal diupdate');
        }

        return redirect()->route('owner.user_management.admin.index')->with('success', 'Admin berhasil diupdate');
    }

    public function destroy(Request $request, Admin $admin)
    {
        if ($admin->photo != 'uploads/images/users/user-1.jpg') {
            if ($admin->photo && Storage::disk('public')->exists($admin->photo)) {
                Storage::disk('public')->delete(paths: $admin->photo);
            } else {
                // Opsional: Log peringatan jika path gambar ada di DB tapi file tidak ditemukan di storage
                // Ini bisa terjadi jika file sudah dihapus secara manual atau ada inkonsistensi data
                if ($admin->photo) {
                    \Log::warning('File gambar tidak ditemukan di storage saat mencoba menghapus: ' . $admin->photo);
                }
            }
        }

        if ($request->ajax()) {
            try {
                $userAdmin = $admin->user;
                $userAdmin->delete();
                $admin->delete();
                return response()->json(['success' => 'Admin berhasil dihapus!']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus admin: ' . $e->getMessage(), ['admin_id' => $admin->id]);
                return response()->json(['error' => 'Gagal menghapus admin. ' . $e->getMessage()], 500);
            }
        } else {
            $admin->delete();
            return redirect()->route('owner.user_management.admin.index')->with('success', 'Admin berhasil dihapus!');
        }
    }
}
