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
        <form action="{{ route('owner.user_management.customer.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <a href="{{ route('owner.user_management.customer.index') }}" class="btn btn-sm btn-primary mb-3"><i class="ti ti-arrow-left"></i>
                    Kembali</a>
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" />
                            @error('name')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Email <span class="text-danger">*</span></label>
                            <input type="text" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" />
                            @error('email')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Password</label>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                value="{{ old('password') }}" />
                            <small>*Jika kosong maka password akan otomatis diisi : customer123</small>
                            @error('password')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}" />
                            @error('phone')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Alamat <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="4">{{ old('address') }}</textarea>
                        </div>
                        <div class="mb-3 row row-cols-1 row-cols-md-2 justify-content-md-between">
                            <div class="col mb-2">
                                <label class="control-label mb-1">Foto </label>
                                <input type="file" name="photo" id="photoInput"
                                    class="form-control @error('photo') is-invalid @enderror" />
                                @error('photo')
                                    <small class="invalid-feedback">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6 py-auto">
                                {{-- Add an ID to the image tag --}}
                                <img src="{{ asset('uploads/images/users/user-1.jpg') }}" class="img-fluid rounded w-50"
                                    alt="Image preview" id="photoPreview">
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
                    <button type="submit" class="btn btn-success rounded-pill px-4">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-device-floppy me-1 fs-4"></i>
                            Simpan
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
                    $('#photoPreview').attr('src', "{{ asset('storage/default_placeholder.png') }}");
                    // Or clear it:
                    // $('#photoPreview').attr('src', "");
                }
            });
        });
    </script>
@endpush
