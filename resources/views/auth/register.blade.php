@extends('layouts.auth.app')

@push('styles')
    <style>
        .shbtn-group {
            position: relative;
            overflow: hidden;
        }

        .shbtn {
            cursor: pointer;
            position: absolute;
            right: 0;
            top: 0;
            transform: translate(-50%, 50%);
            background: transparent;
            padding: 0 5px;
            z-index: 99;
            border: none;
        }

        .shbtn i {
            font-size: 18px;
            color: #333;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full" data-sidebar-position="fixed"
        data-header-position="fixed">
        <div
            class="position-relative overflow-hidden radial-gradient min-vh-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center justify-content-center w-100">
                <div class="row justify-content-center w-100">
                    <div class="col-md-8 col-lg-6 col-xxl-3">
                        <div class="card mb-0">
                            <div class="card-body">
                                <a href="{{ url('/') }}" class="text-nowrap logo-img text-center d-block mb-5 w-100">
                                    <img src="{{ asset('assets/front/img/logo.jpg') }}" width="180" alt="">
                                </a>
                                <div class="position-relative text-center my-4">
                                    <p class="mb-0 fs-4 px-3 d-inline-block bg-white text-dark z-index-5 position-relative">
                                        Daftar Akun</p>
                                    <span
                                        class="border-top w-100 position-absolute top-50 start-50 translate-middle"></span>
                                    @if (session('message'))
                                        <div class="alert alert-danger mt-3">
                                            {{ session('message') }}
                                        </div>
                                    @endif
                                </div>
                                <form action="{{ route('register') }}" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="control-label mb-1">Nama Lengkap <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="name"
                                                    class="form-control @error('name') is-invalid @enderror"
                                                    value="{{ old('name') }}" />
                                                @error('name')
                                                    <small class="invalid-feedback">
                                                        {{ $message }}
                                                    </small>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label class="control-label mb-1">Email <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="email"
                                                    class="form-control @error('email') is-invalid @enderror"
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
                                                <label class="control-label mb-1">Nomor Telepon <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="phone"
                                                    class="form-control @error('phone') is-invalid @enderror"
                                                    value="{{ old('phone') }}" />
                                                @error('phone')
                                                    <small class="invalid-feedback">
                                                        {{ $message }}
                                                    </small>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label class="control-label mb-1">Alamat <span
                                                        class="text-danger">*</span></label>
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
                                                    <img src="{{ asset('uploads/images/users/user-1.jpg') }}"
                                                        class="img-fluid rounded w-50" alt="Image preview"
                                                        id="photoPreview">
                                                    {{-- You might want to update the src="{{ asset('storage') }}" to a default placeholder if no image exists --}}
                                                    {{-- If you're in an "edit" form and photo might already exist, make it conditional: --}}
                                                    {{-- <img src="{{ isset($item) && $item->photo ? Storage::url($item->photo) : asset('storage/default_placeholder.png') }}" class="img-fluid rounded" alt="Image preview" id="photoPreview"> --}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-dark w-100 py-8 mb-2 rounded-2">Daftar</button>
                                </form>
                                <a class="mt-2 text-center" style="width: 100%" href="{{ route('login') }}">Sudah punya akun?</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // show hide password jquery
        $(document).ready(function() {
            $("#show_hide_password span").on('click', function(event) {
                event.preventDefault();
                if ($('#show_hide_password input').attr("type") == "text") {
                    $('#show_hide_password input').attr('type', 'password');
                    $('#show_hide_password i').addClass("ti-eye-off");
                    $('#show_hide_password i').removeClass("ti-eye");
                } else if ($('#show_hide_password input').attr("type") == "password") {
                    $('#show_hide_password input').attr('type', 'text');
                    $('#show_hide_password i').removeClass("ti-eye-off");
                    $('#show_hide_password i').addClass("ti-eye");
                }
            });

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
