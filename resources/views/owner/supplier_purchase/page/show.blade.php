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
                <a href="{{ route('owner.supplier_purchase.index') }}" class="btn btn-sm btn-primary mb-3">
                    <i class="ti ti-arrow-left"></i> Kembali
                </a>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>Invoice Number:</strong></td>
                                <td>{{ $supplierPurchase->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td><strong>Supplier:</strong></td>
                                <td>{{ $supplierPurchase->supplier->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal Belanja:</strong></td>
                                <td>{{ date('d/m/Y', strtotime($supplierPurchase->purchase_date)) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount:</strong></td>
                                <td><strong class="text-primary">Rp
                                        {{ number_format($supplierPurchase->total_amount, 0, ',', '.') }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Catatan:</strong></td>
                                <td>{{ $supplierPurchase->notes ?: '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr class="my-4">

                <h4 class="mb-3">Detail Produk yang Dibeli</h4>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Produk</th>
                                <th>Jumlah</th>
                                <th>Harga Beli per Unit</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($supplierPurchase->supplier_purchase_item as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $item->product->name }}</strong>
                                            @if ($item->product->productBrand)
                                                <br><small
                                                    class="text-muted">{{ $item->product->productBrand->name }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        {{ $item->quantity }}
                                        @if ($item->product->product_unit)
                                            {{ $item->product->product_unit->name }}
                                        @endif
                                    </td>
                                    <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td><strong>Rp
                                            {{ number_format($item->total, 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                <td><strong class="text-primary">Rp
                                        {{ number_format($supplierPurchase->total_amount, 0, ',', '.') }}</strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row gap-2 mt-3">
                    <a href="{{ route('owner.supplier_purchase.edit', $supplierPurchase->id) }}" class="btn btn-warning">
                        <i class="ti ti-pencil"></i> Edit Belanja
                    </a>
                    <form action="{{ route('owner.supplier_purchase.destroy', $supplierPurchase->id) }}" method="post"
                        class="d-inline">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-danger w-100"
                            onclick="return confirm('Apakah Anda yakin ingin menghapus belanja ini? Tindakan ini tidak dapat dibatalkan!')">
                            <i class="ti ti-trash"></i> Hapus Belanja
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>
    </div>
@endsection
