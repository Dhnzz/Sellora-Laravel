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

    <div class="shop-detail">
        <div class="card shadow-none border">
            <div class="card-body p-4">
                <a href="{{ route('owner.user_management.sales.index') }}" class="btn btn-sm btn-primary mb-3"><i class="ti ti-arrow-left"></i>
                    Kembali</a>

                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="item rounded overflow-hidden">
                            @if ($sales->photo == 'uploads/images/users/user-1.jpg')
                                <img src="{{ asset('uploads/images/users/user-1.jpg') }}" alt="" class="img-fluid">
                            @else
                                <img src="{{ asset('storage/' . $sales->photo) }}" alt="" class="img-fluid">
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="shop-content">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="fs-2 fw-bolder">Agen Sales</span>
                            </div>

                            <h4 class="fw-semibold">{{ $sales->name ?? '' }}</h4>

                            <div class="border-top border-bottom py-3 row">
                                <div class="col-12">
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Email</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">{{ $sales->user->email }}</p>
                                    </div>
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Nomor Telepon</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">{{ $sales->phone }}</p>
                                    </div>
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Alamat</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">{{ $sales->address }}</p>
                                    </div>
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Didaftarkan Pada</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">
                                            {{ \Carbon\Carbon::parse($sales->created_at)->format('d M Y, H:i:s') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
                            <a href="{{ route('owner.user_management.sales.edit', $sales->id) }}" class="btn btn-warning">
                                <i class="ti ti-pencil"></i> Edit Agen Sales
                            </a>
                            <form action="{{ route('owner.user_management.sales.destroy', $sales->id) }}" method="post"
                                class="d-inline">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-danger w-100"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus agen sales ini? Tindakan ini tidak dapat dibatalkan!')">
                                    <i class="ti ti-trash"></i> Delete Agen Sales
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
