<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController
{
    public function register(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('auth.register');
        } else {
            $request->validate(
                [
                    'email' => 'required|email|unique:users,email',
                    'name' => 'required',
                    'phone' => 'required|min:12',
                    'address' => 'required',
                    'photo' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
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
                return redirect()->route('login')->with('error', 'Customer gagal ditambahkan');
            }

            return redirect()->route('login')->with('success', 'Customer berhasil ditambahkan');
        }
    }

    public function login(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('auth.login');
        } else {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:3',
            ]);

            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();

                $role = Auth::user()->getRoleNames()->first();
                switch ($role) {
                    case 'owner':
                        return redirect()->intended('/owner/dashboard')->with('success', 'Login berhasil');
                    case 'admin':
                        return redirect()->intended('/admin/dashboard')->with('success', 'Login berhasil');
                    case 'warehouse':
                        return redirect()->intended('/warehouse/dashboard')->with('success', 'Login berhasil');
                    case 'sales':
                        return redirect()->intended('/sales/dashboard')->with('success', 'Login berhasil');
                    case 'customer':
                        return redirect()->intended('/customer/home')->with('success', 'Login berhasil');
                    default:
                        # code...
                        break;
                }
            }

            return back()
                ->withErrors([
                    'email' => 'Email atau password salah',
                ])
                ->onlyInput('email');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Anda berhasil logout');
    }

    public function me(Request $request)
    {
        // return Auth::user();
        dd(Auth::user());
    }
}
