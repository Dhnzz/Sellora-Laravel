<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User; // Asumsi admin adalah user dengan role 'admin'

class AdminService
{
    const DEFAULT_PHOTO_PATH = 'uploads/images/users/user-1.jpg';

    private $validationRules = [
        'email' => 'required|email|unique:users,email',
        'password' => 'nullable|min:3',
        'name' => 'required',
        'phone' => 'required|min:12',
        'address' => 'required',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ];

    private $validationMessages = [
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email sudah terdaftar.',
        'password.min' => 'Password harus lebih dari 3 karakter.',
        'name.required' => 'Nama wajib diisi.',
        'phone.required' => 'Nomor telepon wajib diisi.',
        'phone.min' => 'Nomor telepon minimal 12 digit.',
        'address.required' => 'Alamat wajib diisi.',
        'photo.image' => 'File foto harus berupa gambar.',
        'photo.mimes' => 'Foto harus berformat jpeg, png, jpg, atau gif.',
        'photo.max' => 'Ukuran foto maksimal 2MB.',
    ];

    public function store(array $data, ?UploadedFile $photoFile): Admin
    {
        $data['photo'] = $this->handlePhotoUpload($photoFile) ?? self::DEFAULT_PHOTO_PATH;
        $data['password'] = Hash::make($data['password'] ?? 'admin123');

        $user = User::create([
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->assignRole('admin'); // Assign role 'admin' (Spatie)

        $admin = Admin::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'photo' => $data['photo'],
        ]);

        return $admin;
    }

    public function update(Admin $admin, array $data, ?UploadedFile $photoFile, bool $removePhoto = false): Admin
    {
        $photoPath = $admin->photo;

        if ($removePhoto) {
            $this->handlePhotoDeletion($admin->photo);
            $photoPath = 'uploads/images/users/user-1.jpg'; // Path foto default
        } elseif ($photoFile) {
            $this->handlePhotoDeletion($admin->photo); // Hapus foto lama sebelum upload baru
            $photoPath = $this->handlePhotoUpload($photoFile);
        }

        // Update record User terkait
        $admin->user->update([
            'email' => $data['email'],
        ]);

        // Update record Admin
        $admin->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'photo' => $photoPath,
        ]);
        return $admin;
    }

    public function destroy(Admin $admin): bool
    {
        $this->handlePhotoDeletion($admin->photo);
        $admin->user->delete();
        $admin->delete();
        return true;
    }

    private function handlePhotoUpload(?UploadedFile $photoFile): ?string
    {
        if ($photoFile) {
            $filename = time() . '_' . uniqid() . '.' . $photoFile->getClientOriginalExtension();
            return $photoFile->storeAs('uploads/images/users', $filename, 'public');
        }
        return 'uploads/images/users/user-1.jpg'; // Path foto default
    }

    private function handlePhotoDeletion(?string $photoPath): void
    {
        if ($photoPath && Storage::disk('public')->exists($photoPath) && $photoPath != 'uploads/images/users/user-1.jpg') {
            Storage::disk('public')->delete($photoPath);
        }
    }

    public function resetPassword(Admin $admin): void
    {
        $admin->user->update([
            'password' => Hash::make('admin123'),
        ]);
    }

    public function deletePhoto(Admin $admin): string
    {
        $this->handlePhotoDeletion($admin->photo);
        $admin->update(['photo' => 'uploads/images/users/user-1.jpg']); // Update di DB
        return $admin->photo;
    }

    public function getStoreValidationRules(): array
    {
        return $this->validationRules;
    }

    public function getUpdateValidationRules(int $userId): array
    {
        $rules = $this->validationRules;
        $rules['email'] = ['required', 'email', Rule::unique('users', 'email')->ignore($userId)];
        $rules['password'] = ['nullable', 'min:8']; // Password bisa null di update
        $rules['photo'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'; // photo di update bisa null
        return $rules;
    }

    /**
     * Mendapatkan pesan validasi.
     *
     * @return array
     */
    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }
}
