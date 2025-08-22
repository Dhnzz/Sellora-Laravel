@extends('layouts.app')

@section('content')
    <div class="card bg-light-info shadow-none position-relative overflow-hidden">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-8">{{ $data['title'] ?? '' }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach ($data['breadcrumbs'] as $item)
                        @if ($loop->last)
                            <li class="breadcrumb-item active" aria-current="page">{{ $item['name'] }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ $item['link'] }}" class="text-muted">{{ $item['name'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <form action="{{ route('owner.user_management.warehouse_manager.update', $warehouseManager->id) }}" method="post"
            enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <div class="card-body">
                <a href="{{ route('owner.user_management.warehouse_manager.index') }}" class="btn btn-sm btn-primary mb-3"><i class="ti ti-arrow-left"></i>
                    Kembali</a>
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ $warehouseManager->name }}" />
                            @error('name')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Email <span class="text-danger">*</span></label>
                            <input type="text" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ $warehouseManager->user->email }}" />
                            @error('email')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Password</label>
                            <div class="input-group">
                                <input type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Password tidak bisa diubah" disabled />
                                <button class="btn btn-primary reset-pass-btn" data-id="{{ $warehouseManager->id }}"
                                    type="button"><i class="ti ti-restore"></i></button>
                            </div>
                            <small>*Tombol reset akan mengubah password menjadi : warehouse_manager123</small>
                            @error('password')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ $warehouseManager->phone }}" />
                            @error('phone')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Alamat <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="4">{{ $warehouseManager->address }}</textarea>
                        </div>
                        <div class="mb-3 row row-cols-1 row-cols-md-2 justify-content-md-between">
                            <div class="col mb-2">
                                <label class="control-label mb-1">Foto </label>
                                <div class="input-group">
                                    <input type="file" name="photo" id="photoInput"
                                        class="form-control @error('photo') is-invalid @enderror" />
                                    <button class="btn btn-danger delete-photo-btn" data-id="{{ $warehouseManager->id }}"
                                        type="button"><i class="ti ti-trash"></i></button>
                                </div>
                                @error('photo')
                                    <small class="invalid-feedback">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6 py-auto">
                                {{-- Add an ID to the image tag --}}
                                @if ($warehouseManager->photo == 'uploads/images/users/user-1.jpg')
                                    <img src="{{ asset('uploads/images/users/user-1.jpg') }}"
                                        class="img-fluid rounded w-50" alt="Image preview" id="photoPreview">
                                @else
                                    <img src="{{ asset('storage/' . $warehouseManager->photo) }}" class="img-fluid rounded w-50"
                                        alt="Image preview" id="photoPreview">
                                @endif
                                {{-- You might want to update the src="{{ asset('storage') }}" to a default placeholder if no image exists --}}
                                {{-- If you're in an "edit" form and photo might already exist, make it conditional: --}}
                                {{-- <img src="{{ isset($item) && $item->photo ? Storage::url($item->photo) : asset('storage/default_placeholder.png') }}" class="img-fluid rounded" alt="Image preview" id="photoPreview"> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <div class="card-body border-top">
                    <button type="submit" class="btn btn-warning rounded-pill px-4">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-device-floppy me-1 fs-4"></i>
                            Update
                        </div>
                    </button>
                    <button type="reset" class="btn btn-danger rounded-pill px-4 ms-2 text-white">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            // Listen for changes on the file input
            $('#photoInput').on('change', function(event) {
                console.log('Changed');

                // Get the selected file
                var file = event.target.files[0];

                // Check if a file was selected and if it's an image
                if (file) {
                    var reader = new FileReader(); // Create a new FileReader object

                    // Set up the FileReader's onload event
                    reader.onload = function(e) {
                        // Set the src of the image tag to the result of the FileReader
                        $('#photoPreview').attr('src', e.target.result);
                    }

                    // Read the file as a Data URL (base64 encoded string)
                    reader.readAsDataURL(file);
                } else {
                    // If no file is selected (e.g., user cancels file selection),
                    // revert to a default/placeholder image or clear the preview.
                    // For example, revert to your placeholder:
                    $('#photoPreview').attr('src', "{{ asset('uploads/images/users/user-1.jpg') }}");
                    // Or clear it:
                    // $('#photoPreview').attr('src', "");
                }
            });

            $(document).on('click', '.reset-pass-btn', function(e) {
                e.preventDefault();

                var warehouseManagerId = $(this).data('id');
                var resetPassUrl = "{{ route('owner.user_management.warehouse_manager.resetPassword', ':id') }}";
                resetPassUrl = resetPassUrl.replace(':id', warehouseManagerId);

                if (confirm(
                        'Apakah Anda yakin ingin mereset password manajer warehouse ini? Tindakan ini tidak dapat dibatalkan!'
                    )) {
                    $.ajax({
                        url: resetPassUrl,
                        type: 'PUT', // Menggunakan metode HTTP DELETE
                        data: {
                            _token: "{{ csrf_token() }}" // Kirim CSRF token untuk keamanan Laravel
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.success);
                            } else if (response.error) {
                                toastr.error(response.error);
                            }
                        },
                        error: function(xhr) {
                            console.error('AJAX Error:', xhr.responseText);
                            toastr.error('Terjadi kesalahan saat mereset password manajer warehouse.');
                        }
                    });
                }
            })

            $(document).on('click', '.delete-photo-btn', function(e) {
                e.preventDefault();

                var warehouseManagerId = $(this).data('id');
                var deletePhotoUrl = "{{ route('owner.user_management.warehouse_manager.deletePhoto', ':id') }}";
                deletePhotoUrl = deletePhotoUrl.replace(':id', warehouseManagerId);

                if (confirm(
                        'Apakah Anda yakin ingin menghapus foto manajer warehouse ini? Tindakan ini tidak dapat dibatalkan!'
                    )) {
                    $.ajax({
                        url: deletePhotoUrl,
                        type: 'PUT', // Menggunakan metode HTTP DELETE
                        data: {
                            _token: "{{ csrf_token() }}" // Kirim CSRF token untuk keamanan Laravel
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#photoPreview').attr('src', "{{ asset('') }}" + response.photo);
                                toastr.success(response.success);
                            } else if (response.error) {
                                toastr.error(response.error);
                            }
                        },
                        error: function(xhr) {
                            console.error('AJAX Error:', xhr.responseText);
                            toastr.error('Terjadi kesalahan saat menghapus foto manajer warehouse.');
                        }
                    });
                }
            })
        });
    </script>
@endpush
