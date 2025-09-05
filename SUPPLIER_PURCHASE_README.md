# Fitur Pembelanjaan Produk dari Supplier

## Deskripsi

Fitur ini memungkinkan owner untuk melakukan pembelanjaan produk dari supplier dengan sistem CRUD lengkap. Fitur ini mencakup pembuatan, pembacaan, pengeditan, dan penghapusan data pembelanjaan produk.

## Fitur Utama

### 1. Halaman Index (Daftar Belanja)

-   Menampilkan semua data SupplierPurchase yang pernah dilakukan
-   Menggunakan DataTables untuk tampilan yang responsif
-   Fitur pencarian dan pengurutan
-   Tombol aksi untuk view, edit, dan delete

### 2. CRUD Operations

-   **Create**: Membuat pembelanjaan produk baru
-   **Read**: Melihat detail pembelanjaan
-   **Update**: Mengubah data pembelanjaan
-   **Delete**: Menghapus data pembelanjaan

### 3. Fitur Khusus

-   **Tombol + dan -**: Untuk mengatur jumlah produk yang dibeli
-   **Auto-calculation**: Subtotal dan total amount otomatis terhitung
-   **Stock Management**: Stok produk otomatis terupdate saat pembelian
-   **Invoice Generation**: Nomor invoice otomatis dibuat

## Struktur File

### Controller

-   `app/Http/Controllers/SupplierPurchaseController.php` - Controller utama dengan semua method CRUD

### Views

-   `resources/views/owner/supplier_purchase/page/index.blade.php` - Halaman daftar belanja
-   `resources/views/owner/supplier_purchase/page/create.blade.php` - Halaman tambah belanja
-   `resources/views/owner/supplier_purchase/page/show.blade.php` - Halaman detail belanja
-   `resources/views/owner/supplier_purchase/page/edit.blade.php` - Halaman edit belanja

### Assets

-   `public/assets/css/supplier-purchase.css` - Styling khusus
-   `public/assets/js/supplier-purchase.js` - JavaScript functions

### Routes

```php
Route::prefix('supplier_purchase')
    ->name('supplier_purchase.')
    ->group(function () {
        Route::get('/', [SupplierPurchaseController::class, 'index'])->name('index');
        Route::get('/create', [SupplierPurchaseController::class, 'create'])->name('create');
        Route::post('/store', [SupplierPurchaseController::class, 'store'])->name('store');
        Route::get('/{supplierPurchase}', [SupplierPurchaseController::class, 'show'])->name('show');
        Route::get('/{supplierPurchase}/edit', [SupplierPurchaseController::class, 'edit'])->name('edit');
        Route::put('/{supplierPurchase}', [SupplierPurchaseController::class, 'update'])->name('update');
        Route::delete('/{supplierPurchase}', [SupplierPurchaseController::class, 'destroy'])->name('destroy');
        Route::get('/data/getAll', [SupplierPurchaseController::class, 'getAll'])->name('getAll');
        Route::get('/data/products', [SupplierPurchaseController::class, 'getProducts'])->name('getProducts');
    });
```

## Alur Kerja

### 1. Membuat Pembelian Baru

1. Owner mengakses halaman create
2. Memilih supplier dari dropdown
3. Mengisi tanggal belanja (otomatis hari ini)
4. Nomor invoice otomatis dibuat
5. Menambahkan produk yang akan dibeli:
    - Pilih produk dari dropdown
    - Atur jumlah dengan tombol + dan -
    - Masukkan harga beli per unit
    - Subtotal otomatis terhitung
6. Total amount otomatis terhitung
7. Simpan data

### 2. Mengedit Pembelian

1. Owner mengakses halaman edit
2. Data lama ditampilkan dalam form
3. Owner dapat mengubah semua field
4. Stok lama dikembalikan, stok baru diupdate
5. Simpan perubahan

### 3. Menghapus Pembelian

1. Owner klik tombol delete
2. Konfirmasi penghapusan
3. Stok produk dikembalikan
4. Data pembelian dihapus

## Validasi

### Form Validation

-   Supplier harus dipilih
-   Tanggal belanja harus diisi
-   Nomor invoice harus unik
-   Minimal satu produk harus ditambahkan
-   Jumlah produk harus > 0
-   Harga beli harus >= 0

### Business Logic

-   Stok produk otomatis terupdate
-   Total amount otomatis terhitung
-   Invoice number otomatis dibuat

## Database Impact

### Tables Affected

-   `supplier_purchases` - Data pembelian utama
-   `supplier_purchase_items` - Detail produk yang dibeli
-   `stocks` - Stok produk (otomatis terupdate)

### Relationships

-   `SupplierPurchase` belongs to `Supplier`
-   `SupplierPurchase` has many `SupplierPurchaseItem`
-   `SupplierPurchaseItem` belongs to `Product`

## Keamanan

### Middleware

-   `auth` - User harus login
-   `role:owner` - Hanya owner yang bisa akses

### CSRF Protection

-   Semua form menggunakan CSRF token
-   AJAX requests include CSRF token

## Responsivitas

### Mobile Friendly

-   Layout responsive untuk berbagai ukuran layar
-   Tombol dan input yang mudah digunakan di mobile
-   DataTables responsive

### Browser Support

-   Modern browsers (Chrome, Firefox, Safari, Edge)
-   JavaScript ES6+ support
-   jQuery dependency

## Error Handling

### User Feedback

-   Toastr notifications untuk success/error
-   Form validation errors
-   Database transaction rollback

### Logging

-   Error logging untuk debugging
-   User action logging

## Performance

### Optimization

-   Eager loading untuk relationships
-   Database transactions untuk data consistency
-   AJAX untuk dynamic content loading
-   DataTables server-side processing

## Testing

### Test Cases

-   CRUD operations
-   Form validation
-   Stock management
-   Error scenarios
-   Edge cases

## Maintenance

### Monitoring

-   Error logs
-   Performance metrics
-   User feedback

### Updates

-   Regular security updates
-   Feature enhancements
-   Bug fixes

## Dependencies

### Backend

-   Laravel 10+
-   PHP 8.1+
-   MySQL/PostgreSQL

### Frontend

-   jQuery
-   DataTables
-   Bootstrap 5
-   Toastr
-   Tabler Icons

## Installation

1. Pastikan semua dependencies terinstall
2. Copy file-file ke direktori yang sesuai
3. Jalankan `php artisan route:cache` untuk cache routes
4. Pastikan database migrations sudah dijalankan
5. Test fitur dengan data dummy

## Troubleshooting

### Common Issues

-   CSRF token mismatch
-   Database connection errors
-   JavaScript errors
-   Permission denied

### Solutions

-   Clear cache: `php artisan cache:clear`
-   Check database connection
-   Verify JavaScript console
-   Check user permissions
