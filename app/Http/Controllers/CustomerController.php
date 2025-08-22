<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class CustomerController
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Manajemen Customer',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_customer',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Customer',
                    'link' => route('owner.user_management.customer.index'),
                ],
            ],
        ];
        return view('owner.user_management.customer.page.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = Customer::latest()->select('id', 'name', 'phone', 'created_at');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i:s');
                })
                ->addColumn('options', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-1">';
                    $btn .= '<a href="' . route('owner.user_management.customer.detail', $row->id) . '" class="btn btn-sm btn-primary"><i class="ti ti-eye"></i></a>';
                    $btn .= '<a href="' . route('owner.user_management.customer.edit', $row->id) . '" class="btn btn-sm btn-warning"><i class="ti ti-pencil"></i></a>';
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

    public function getById(Customer $customer)
    {
        $data = [
            'title' => 'Detail Customer',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_customer',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Customer',
                    'link' => route('owner.user_management.customer.index'),
                ],
                [
                    'name' => 'Detail Customer',
                    'link' => route('owner.user_management.customer.detail', $customer->id),
                ],
            ],
        ];

        return view('owner.user_management.customer.page.detail', compact('data', 'customer'));
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Customer',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_customer',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Customer',
                    'link' => route('owner.user_management.customer.index'),
                ],
                [
                    'name' => 'Tambah Customer',
                    'link' => route('owner.user_management.customer.create'),
                ],
            ],
        ];
        return view('owner.user_management.customer.page.create', compact('data'));
    }

    public function edit(Customer $customer)
    {
        $data = [
            'title' => 'Edit Customer',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_customer',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Customer',
                    'link' => route('owner.user_management.customer.index'),
                ],
                [
                    'name' => 'Edit Customer',
                    'link' => route('owner.user_management.customer.edit', $customer->id),
                ],
            ],
        ];
        return view('owner.user_management.customer.page.edit', compact('data', 'customer'));
    }

    public function resetPassword(Customer $customer, Request $request)
    {
        if ($request->ajax()) {
            try {
                $userCustomer = $customer->user;
                $userCustomer->update([
                    'password' => Hash::make('customer123'),
                ]);
                return response()->json(['success' => 'Password ' . $customer->name . ' berhasil direset']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal mereset password customer: ' . $e->getMessage(), ['customer_id' => $customer->id]);
                return response()->json(['error' => 'Gagal mereset password customer. ' . $e->getMessage()], 500);
            }
        }
    }

    public function deletePhoto(Customer $customer, Request $request)
    {
        if ($request->ajax()) {
            try {
                if ($customer->photo && Storage::disk('public')->exists($customer->photo)) {
                    Storage::disk('public')->delete($customer->photo);
                    $photoPath = 'uploads/images/users/user-1.jpg';
                    $customer->update([
                        'photo' => $photoPath
                    ]);
                    return response()->json(['success' => 'Berhasil menghapus foto '.$customer->name, 'photo' => $photoPath]);
                }
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus foto customer: ' . $e->getMessage(), ['customer_id' => $customer->id]);
                return response()->json(['error' => 'Gagal menghapus foto customer. ' . $e->getMessage()], 500);
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

        $password = 'customer123';
        if ($request->password != null) {
            $password = $request->password;
        }
        $userCustomer = User::create([
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);
        $userCustomer->assignRole('customer');

        $customer = Customer::create([
            'user_id' => $userCustomer->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'photo' => $photoPath,
            'address' => $request->address,
        ]);

        if (!$customer) {
            return redirect()->route('owner.user_management.customer.index')->with('error', 'Customer gagal ditambahkan');
        }

        return redirect()->route('owner.user_management.customer.index')->with('success', 'Customer berhasil ditambahkan');
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate(
            [
                'email' => 'required|email|unique:users,email,' . $customer->user_id,
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
        $photoPath = $customer->photo;
        if ($request->hasFile('photo')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $photoPath = Storage::disk('public')->putFileAs('uploads/images/users', $file, $filename);
        }

        $customer->user->update([
            'email' => $request->email,
        ]);

        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'photo' => $photoPath,
            'address' => $request->address,
        ]);

        if (!$customer) {
            return redirect()->route('owner.user_management.customer.index')->with('error', 'Customer gagal diupdate');
        }

        return redirect()->route('owner.user_management.customer.index')->with('success', 'Customer berhasil diupdate');
    }

    public function destroy(Request $request, Customer $customer)
    {
        if ($customer->photo != 'uploads/images/users/user-1.jpg') {
            if ($customer->photo && Storage::disk('public')->exists($customer->photo)) {
                Storage::disk('public')->delete(paths: $customer->photo);
            } else {
                // Opsional: Log peringatan jika path gambar ada di DB tapi file tidak ditemukan di storage
                // Ini bisa terjadi jika file sudah dihapus secara manual atau ada inkonsistensi data
                if ($customer->photo) {
                    \Log::warning('File gambar tidak ditemukan di storage saat mencoba menghapus: ' . $customer->photo);
                }
            }
        }

        if ($request->ajax()) {
            try {
                $userCustomer = $customer->user;
                $userCustomer->delete();
                $customer->delete();
                return response()->json(['success' => 'Customer berhasil dihapus!']);
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus customer: ' . $e->getMessage(), ['customer_id' => $customer->id]);
                return response()->json(['error' => 'Gagal menghapus customer. ' . $e->getMessage()], 500);
            }
        } else {
            $customer->delete();
            return redirect()->route('owner.user_management.customer.index')->with('success', 'Customer berhasil dihapus!');
        }
    }
}
