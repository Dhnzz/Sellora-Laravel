<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SalesAgentController;
use App\Http\Controllers\ShopBundleController;
use App\Http\Controllers\ProductUnitController;
use App\Http\Controllers\ProductBrandController;
use App\Http\Controllers\ProductBundleController;
use App\Http\Controllers\UnitConvertionController;
use App\Http\Controllers\SalesTransactionController;
use App\Http\Controllers\WarehouseManagerController;
use App\Http\Controllers\RevenuePredictionController;
use App\Http\Controllers\MonthlyBookClosingController;
use App\Http\Controllers\MonthlyClosingImportController;
use App\Http\Controllers\MonthlyRevenuePredictionController;

Route::get('/', function () {
    if (Auth::check()) {
        // Jika user sudah login, arahkan ke dashboard yang sesuai
        // Anda bisa menambahkan logika role-based redirect di sini
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
                return redirect()->intended('/customer')->with('success', 'Login berhasil');
            default:
                # code...
                break;
        }
        // Tambahkan kondisi untuk role lain jika ada
        // if (Auth::user()->hasRole('admin')) {
        //     return redirect()->route('admin.dashboard'); // Asumsi ada rute admin.dashboard
        // }
        // Default jika login tapi tidak punya role spesifik atau role tidak ditangani
        return redirect()->route('me'); // Atau rute default setelah login
    }
    // Jika user belum login, arahkan ke halaman login
    return redirect()->route('login');
});

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // OWNER
    Route::middleware(['role:owner'])
        ->prefix('owner')
        ->name('owner.')
        ->group(function () {
            Route::get('/profile/{admin}', [OwnerController::class, 'edit'])->name('profile');
            Route::put('/profile/update/{admin}', [OwnerController::class, 'update'])->name('profile.update');
            Route::get('/dashboard', [OwnerController::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard/data', [OwnerController::class, 'filterData'])->name('dashboard.data');
            Route::get('/dashboard/sales-chart', [OwnerController::class, 'salesChartData'])->name('dashboard.sales_chart');
            Route::get('/dashboard/top-sales', [OwnerController::class, 'topSalesData'])->name('dashboard.top_sales');
            Route::get('/dashboard/latest', [OwnerController::class, 'latest'])->name('dashboard.latest');

            // Monthly Closing Book
            Route::prefix('closing')
                ->name('closing.')
                ->group(function () {
                    Route::get('/', [MonthlyBookClosingController::class, 'index'])->name('index');
                    Route::get('/create', [MonthlyBookClosingController::class, 'create'])->name('create');
                    Route::post('/store', [MonthlyBookClosingController::class, 'store'])->name('store');
                    Route::get('/import', [MonthlyClosingImportController::class, 'form'])->name('import.form');
                    Route::post('/import', [MonthlyClosingImportController::class, 'import'])->name('import.do');
                    Route::get('/edit/{closing}', [MonthlyBookClosingController::class, 'edit'])->name('edit');
                    Route::put('/update/{closing}', [MonthlyBookClosingController::class, 'update'])->name('update');
                    Route::delete('/destroy/{closing}', [MonthlyBookClosingController::class, 'destroy'])->name('destroy');
                });

            // Bundle
            Route::prefix('bundle')
                ->name('bundle.')
                ->group(function () {
                    Route::get('/', [ProductBundleController::class, 'index'])->name('index');
                    Route::post('/analyze', [ProductBundleController::class, 'analyze'])->name('analyze');
                    Route::get('/build', [ProductBundleController::class, 'build'])->name('build');
                    Route::get('/owner/bundle/related-rank', [ProductBundleController::class, 'relatedRank'])->name('related-rank');

                    // BARU
                    Route::get('/data', [ProductBundleController::class, 'getAll'])->name('data');
                    Route::post('/store', [ProductBundleController::class, 'store'])->name('store');
                    Route::get('/{bundle}', [ProductBundleController::class, 'show'])->name('show');
                    Route::get('/{bundle}/edit', [ProductBundleController::class, 'edit'])->name('edit');
                    Route::patch('/{bundle}', [ProductBundleController::class, 'update'])->name('update');
                    Route::put('/deleteImage/{bundle}', [ProductBundleController::class, 'deleteImage'])->name('deleteImage');
                    Route::delete('/{bundle}', [ProductBundleController::class, 'destroy'])->name('destroy');
                    Route::patch('/{bundle}/toggle', [ProductBundleController::class, 'toggleActive'])->name('toggle');
                });

            // Prediction
            Route::prefix('predictions')
                ->name('predictions.')
                ->group(function () {
                    Route::get('/', [MonthlyRevenuePredictionController::class, 'index'])->name('index');
                    Route::post('/predict-next', [MonthlyRevenuePredictionController::class, 'predictNext'])->name('predict');
                });

            // Report
            Route::prefix('report')
                ->name('report.')
                ->group(function () {
                    Route::get('/', [ReportController::class, 'index'])->name('index');
                    Route::get('/data', [ReportController::class, 'data'])->name('data'); // AJAX data (cards, table, chart)
                    Route::get('/invoices', [ReportController::class, 'invoicesData'])->name('invoices');
                    Route::get('/export/xlsx', [ReportController::class, 'exportXlsx'])->name('export.xlsx');
                    Route::get('/export/csv', [ReportController::class, 'exportCsv'])->name('export.csv');
                    Route::get('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
                    Route::get('/invoices/{id}', [ReportController::class, 'invoiceShow'])->name('invoice.show'); // 🔍 detail
                    Route::get('/invoice/{id}/pdf', [ReportController::class, 'exportInvoicePdf'])->name('invoice.pdf');
                });

            // User Management
            Route::prefix('user_management')
                ->name('user_management.')
                ->group(function () {
                    // Admin Management
                    Route::prefix('admin')
                        ->name('admin.')
                        ->group(function () {
                            Route::get('/', [AdminController::class, 'index'])->name('index');
                            Route::get('/data', [AdminController::class, 'getAll'])->name('data');
                            Route::get('/create', [AdminController::class, 'create'])->name('create');
                            Route::post('/store', [AdminController::class, 'store'])->name('store');
                            Route::put('/resetPassword/{admin}', [AdminController::class, 'resetPassword'])->name('resetPassword');
                            Route::put('/deletePhoto/{admin}', [AdminController::class, 'deletePhoto'])->name('deletePhoto');
                            Route::get('/{admin}', [AdminController::class, 'getById'])->name('detail');
                            Route::get('/edit/{admin}', [AdminController::class, 'edit'])->name('edit');
                            Route::put('/update/{admin}', [AdminController::class, 'update'])->name('update');
                            Route::delete('/delete/{admin}', [AdminController::class, 'destroy'])->name('destroy');
                        });

                    // Warehouse Manager Management
                    Route::prefix('warehouse_manager')
                        ->name('warehouse_manager.')
                        ->group(function () {
                            Route::get('/', [WarehouseManagerController::class, 'index'])->name('index');
                            Route::get('/data', [WarehouseManagerController::class, 'getAll'])->name('data');
                            Route::get('/create', [WarehouseManagerController::class, 'create'])->name('create');
                            Route::post('/store', [WarehouseManagerController::class, 'store'])->name('store');
                            Route::put('/resetPassword/{warehouse_manager}', [WarehouseManagerController::class, 'resetPassword'])->name('resetPassword');
                            Route::put('/deletePhoto/{warehouse_manager}', [WarehouseManagerController::class, 'deletePhoto'])->name('deletePhoto');
                            Route::get('/{warehouse_manager}', [WarehouseManagerController::class, 'getById'])->name('detail');
                            Route::get('/edit/{warehouse_manager}', [WarehouseManagerController::class, 'edit'])->name('edit');
                            Route::put('/update/{warehouse_manager}', [WarehouseManagerController::class, 'update'])->name('update');
                            Route::delete('/delete/{warehouse_manager}', [WarehouseManagerController::class, 'destroy'])->name('destroy');
                        });

                    // Sales Management
                    Route::prefix('sales')
                        ->name('sales.')
                        ->group(function () {
                            Route::get('/', [SalesAgentController::class, 'index'])->name('index');
                            Route::get('/data', [SalesAgentController::class, 'getAll'])->name('data');
                            Route::get('/create', [SalesAgentController::class, 'create'])->name('create');
                            Route::post('/store', [SalesAgentController::class, 'store'])->name('store');
                            Route::put('/resetPassword/{sales}', [SalesAgentController::class, 'resetPassword'])->name('resetPassword');
                            Route::put('/deletePhoto/{sales}', [SalesAgentController::class, 'deletePhoto'])->name('deletePhoto');
                            Route::get('/{sales}', [SalesAgentController::class, 'getById'])->name('detail');
                            Route::get('/edit/{sales}', [SalesAgentController::class, 'edit'])->name('edit');
                            Route::put('/update/{sales}', [SalesAgentController::class, 'update'])->name('update');
                            Route::delete('/delete/{sales}', [SalesAgentController::class, 'destroy'])->name('destroy');
                        });

                    // Customer Management
                    Route::prefix('customer')
                        ->name('customer.')
                        ->group(function () {
                            Route::get('/', [CustomerController::class, 'index'])->name('index');
                            Route::get('/data', [CustomerController::class, 'getAll'])->name('data');
                            Route::get('/create', [CustomerController::class, 'create'])->name('create');
                            Route::post('/store', [CustomerController::class, 'store'])->name('store');
                            Route::put('/resetPassword/{customer}', [CustomerController::class, 'resetPassword'])->name('resetPassword');
                            Route::put('/deletePhoto/{customer}', [CustomerController::class, 'deletePhoto'])->name('deletePhoto');
                            Route::get('/{customer}', [CustomerController::class, 'getById'])->name('detail');
                            Route::get('/edit/{customer}', [CustomerController::class, 'edit'])->name('edit');
                            Route::put('/update/{customer}', [CustomerController::class, 'update'])->name('update');
                            Route::delete('/delete/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
                        });

                    // Role Management
                    // Route::prefix('role')
                    //     ->name('role.')
                    //     ->group(function () {
                    //         Route::get('/', [RoleController::class, 'index'])->name('index');
                    //         Route::get('/data', [RoleController::class, 'getAll'])->name('data');
                    //     });
                });

            // Master Data
            Route::prefix('master_data')
                ->name('master_data.')
                ->group(function () {
                    // Supplier
                    Route::prefix('supplier')
                        ->name('supplier.')
                        ->group(function () {
                            Route::get('/', [SupplierController::class, 'index'])->name('index');
                            Route::get('/data', [SupplierController::class, 'getAll'])->name('data');
                            Route::post('/store', [SupplierController::class, 'store'])->name('store');
                            Route::get('/edit/{supplier}', [SupplierController::class, 'edit'])->name('edit');
                            Route::put('/update/{supplier}', [SupplierController::class, 'update'])->name('update');
                            Route::delete('/delete/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
                        });

                    // Product Unit
                    Route::prefix('product_unit')
                        ->name('product_unit.')
                        ->group(function () {
                            Route::get('/', [ProductUnitController::class, 'index'])->name('index');
                            Route::get('/data', [ProductUnitController::class, 'getAll'])->name('data');
                            Route::post('/store', [ProductUnitController::class, 'store'])->name('store');
                            Route::get('/edit/{product_unit}', [ProductUnitController::class, 'edit'])->name('edit');
                            Route::put('/update/{product_unit}', [ProductUnitController::class, 'update'])->name('update');
                            Route::delete('/delete/{product_unit}', [ProductUnitController::class, 'destroy'])->name('destroy');
                        });

                    // Product Brand
                    Route::prefix('product_brand')
                        ->name('product_brand.')
                        ->group(function () {
                            Route::get('/', [ProductBrandController::class, 'index'])->name('index');
                            Route::get('/data', [ProductBrandController::class, 'getAll'])->name('data');
                            Route::post('/store', [ProductBrandController::class, 'store'])->name('store');
                            Route::get('/edit/{product_brand}', [ProductBrandController::class, 'edit'])->name('edit');
                            Route::put('/update/{product_brand}', [ProductBrandController::class, 'update'])->name('update');
                            Route::delete('/delete/{product_brand}', [ProductBrandController::class, 'destroy'])->name('destroy');
                        });

                    // Product
                    Route::prefix('product')
                        ->name('product.')
                        ->group(function () {
                            Route::get('/', [ProductController::class, 'index'])->name('index');
                            Route::get('/data', [ProductController::class, 'getAll'])->name('data');
                            Route::get('/test', [ProductController::class, 'test'])->name('test');
                            Route::get('/create', [ProductController::class, 'create'])->name('create');
                            Route::post('/store', [ProductController::class, 'store'])->name('store');
                            Route::put('/deleteImage/{product}', [ProductController::class, 'deleteImage'])->name('deleteImage');
                            Route::get('/{product}', [ProductController::class, 'getById'])->name('detail');
                            Route::get('/edit/{product}', [ProductController::class, 'edit'])->name('edit');
                            Route::put('/update/{product}', [ProductController::class, 'update'])->name('update');
                            Route::delete('/delete/{product}', [ProductController::class, 'destroy'])->name('destroy');

                            Route::prefix('unit_convertion')
                                ->name('unit_convertion.')
                                ->group(function () {
                                    Route::get('/data', [UnitConvertionController::class, 'getAll'])->name('data');
                                    Route::post('/', [UnitConvertionController::class, 'store'])->name('store');
                                    Route::get('/edit/{unit_convertion}', [UnitConvertionController::class, 'edit'])->name('edit');
                                    Route::put('/update/{unit_convertion}', [UnitConvertionController::class, 'update'])->name('update');
                                    Route::delete('/delete/{unit_convertion}', [UnitConvertionController::class, 'destroy'])->name('destroy');
                                });
                        });
                });
        });

    // ADMIN
    Route::middleware(['role:admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/profile/{admin}', [AdminController::class, 'edit'])->name('profile');
            Route::put('/profile/update/{admin}', [AdminController::class, 'update'])->name('profile.update');
            Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

            // Master Data
            Route::prefix('master_data')
                ->name('master_data.')
                ->group(function () {
                    // Supplier
                    Route::prefix('supplier')
                        ->name('supplier.')
                        ->group(function () {
                            Route::get('/', [SupplierController::class, 'index'])->name('index');
                            Route::get('/data', [SupplierController::class, 'getAll'])->name('data');
                            Route::post('/store', [SupplierController::class, 'store'])->name('store');
                            Route::get('/edit/{supplier}', [SupplierController::class, 'edit'])->name('edit');
                            Route::put('/update/{supplier}', [SupplierController::class, 'update'])->name('update');
                            Route::delete('/delete/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
                        });

                    // Product Unit
                    Route::prefix('product_unit')
                        ->name('product_unit.')
                        ->group(function () {
                            Route::get('/', [ProductUnitController::class, 'index'])->name('index');
                            Route::get('/data', [ProductUnitController::class, 'getAll'])->name('data');
                            Route::post('/store', [ProductUnitController::class, 'store'])->name('store');
                            Route::get('/edit/{product_unit}', [ProductUnitController::class, 'edit'])->name('edit');
                            Route::put('/update/{product_unit}', [ProductUnitController::class, 'update'])->name('update');
                            Route::delete('/delete/{product_unit}', [ProductUnitController::class, 'destroy'])->name('destroy');
                        });

                    // Product Brand
                    Route::prefix('product_brand')
                        ->name('product_brand.')
                        ->group(function () {
                            Route::get('/', [ProductBrandController::class, 'index'])->name('index');
                            Route::get('/data', [ProductBrandController::class, 'getAll'])->name('data');
                            Route::post('/store', [ProductBrandController::class, 'store'])->name('store');
                            Route::get('/edit/{product_brand}', [ProductBrandController::class, 'edit'])->name('edit');
                            Route::put('/update/{product_brand}', [ProductBrandController::class, 'update'])->name('update');
                            Route::delete('/delete/{product_brand}', [ProductBrandController::class, 'destroy'])->name('destroy');
                        });

                    // Product
                    Route::prefix('product')
                        ->name('product.')
                        ->group(function () {
                            Route::get('/', [ProductController::class, 'index'])->name('index');
                            Route::get('/data', [ProductController::class, 'getAll'])->name('data');
                            Route::get('/create', [ProductController::class, 'create'])->name('create');
                            Route::post('/store', [ProductController::class, 'store'])->name('store');
                            Route::put('/deleteImage/{product}', [ProductController::class, 'deleteImage'])->name('deleteImage');
                            Route::get('/{product}', [ProductController::class, 'getById'])->name('detail');
                            Route::get('/edit/{product}', [ProductController::class, 'edit'])->name('edit');
                            Route::put('/update/{product}', [ProductController::class, 'update'])->name('update');
                            Route::delete('/delete/{product}', [ProductController::class, 'destroy'])->name('destroy');

                            Route::prefix('unit_convertion')
                                ->name('unit_convertion.')
                                ->group(function () {
                                    Route::get('/data', [UnitConvertionController::class, 'getAll'])->name('data');
                                    Route::post('/', [UnitConvertionController::class, 'store'])->name('store');
                                    Route::get('/edit/{unit_convertion}', [UnitConvertionController::class, 'edit'])->name('edit');
                                    Route::put('/update/{unit_convertion}', [UnitConvertionController::class, 'update'])->name('update');
                                    Route::delete('/delete/{unit_convertion}', [UnitConvertionController::class, 'destroy'])->name('destroy');
                                });
                        });
                });
        });

    Route::middleware(['role:customer'])
        ->prefix('customer')
        ->name('customer.')
        ->group(function () {
            Route::get('/home', [ShopController::class, 'home'])->name('home');
            Route::get('/catalog', [ShopController::class, 'catalog'])->name('catalog');

            Route::prefix('order')
                ->name('order.')
                ->group(function () {
                    Route::get('/', [OrderController::class, 'index'])->name('index');
                    Route::get('/data', [OrderController::class, 'data'])->name('data');
                    Route::get('/{id}', [OrderController::class, 'show'])->name('show');
                });

            Route::prefix('bundle')
                ->name('bundle.')
                ->group(function () {
                    Route::get('/{bundle}', [ShopBundleController::class, 'index'])->name('index');
                });
        });
});
