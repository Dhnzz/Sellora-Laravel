<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Models\ProductBrand;
use Illuminate\Http\Request;
use App\Models\ProductBundle;
use App\Services\ML\FpClient;
use App\Models\SalesTransaction;
use App\Models\ProductBundleItem;
use App\Models\ProductAssociation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SalesTransactionItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ProductBundleController
{
    /* ===========================
     *  Helper filter dari session
     * =========================== */
    private function currentBundleFilter(): array
    {
        $f = session('bundle_filter', [
            'range' => 'all',
            'brand_id' => null,
            'from' => null,
            'to' => null,
        ]);

        return [
            'brand_id' => $f['brand_id'] ?? null,
            'from' => $f['from'] ? Carbon::parse($f['from'])->startOfDay() : null,
            'to' => $f['to'] ? Carbon::parse($f['to'])->endOfDay() : null,
        ];
    }

    public function index(Request $request)
    {
        $role = Auth::user()->getRoleNames()->first();
        $data = [
            'title' => 'Bundle Produk',
            'role' => $role,
            'active' => 'bundle_index',
            'breadcrumbs' => [['name' => 'Bundle Produk', 'link' => route('owner.bundle.index')]],
            'brands' => ProductBrand::all(['id', 'name']),
        ];
        return view($role . '.bundle.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $q = ProductBundle::query()->select('id', 'bundle_name', 'start_date', 'end_date', 'original_price', 'special_bundle_price', 'is_active', 'created_at')->latest();

            return DataTables::of($q)
                ->addIndexColumn()
                ->editColumn('start_date', fn($r) => Carbon::parse($r->start_date)->format('d M Y'))
                ->editColumn('end_date', fn($r) => Carbon::parse($r->end_date)->format('d M Y'))
                ->addColumn('period', fn($r) => Carbon::parse($r->start_date)->format('d M Y') . ' - ' . Carbon::parse($r->end_date)->format('d M Y'))
                ->editColumn('original_price', fn($r) => 'Rp ' . number_format($r->original_price, 0, ',', '.'))
                ->editColumn('special_bundle_price', fn($r) => 'Rp ' . number_format($r->special_bundle_price, 0, ',', '.'))
                ->addColumn('active_switch', function ($r) {
                    $checked = $r->is_active ? 'checked' : '';
                    return '<div class="form-check form-switch d-flex justify-content-center">
                    <input class="form-check-input toggle-active" type="checkbox" ' .
                        $checked .
                        ' data-id="' .
                        $r->id .
                        '">
                </div>';
                })
                ->addColumn('options', function ($r) {
                    $btn = '<div class="d-flex justify-content-center gap-1">';
                    $btn .= '<a href="' . route('owner.bundle.show', $r->id) . '" class="btn btn-sm btn-primary"><i class="ti ti-eye"></i></a>';
                    $btn .= '<a href="' . route('owner.bundle.edit', $r->id) . '" class="btn btn-sm btn-warning"><i class="ti ti-pencil"></i></a>';
                    $btn .= '<button type="button" class="btn btn-sm btn-danger delete-bundle" data-id="' . $r->id . '"><i class="ti ti-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['options', 'active_switch'])
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->input('search')['value'])) {
                        $s = $request->input('search')['value'];
                        $query->where('bundle_name', 'like', "%{$s}%");
                    }
                })
                ->order(function ($query) use ($request) {
                    if ($request->has('order')) {
                        $order = $request->input('order')[0];
                        $colIdx = $order['column'];
                        $colName = $request->input('columns')[$colIdx]['data'];
                        $dir = $order['dir'];
                        $sortable = ['id', 'bundle_name', 'start_date', 'end_date', 'original_price', 'special_bundle_price', 'created_at'];
                        if (in_array($colName, $sortable)) {
                            $query->orderBy($colName, $dir);
                        } elseif ($colName === 'DT_RowIndex') {
                            $query->orderBy('created_at', 'desc');
                        }
                    }
                })
                ->make(true);
        }
    }

    public function show(ProductBundle $bundle)
    {
        $bundle->load(['product_bundle_items.product:id,name,selling_price']);
        $role = Auth::user()->getRoleNames()->first();
        $data = [
            'title' => 'Detail Bundle Produk',
            'role' => $role,
            'active' => 'bundle_index',
            'breadcrumbs' => [['name' => 'Bundle Produk', 'link' => route('owner.bundle.index')], ['name' => 'Detail Bundle Produk', 'link' => route('owner.bundle.show', $bundle->id)]],
            'brands' => ProductBrand::all(['id', 'name']),
            'bundle' => $bundle,
        ];
        return view($role . '.bundle.show', compact('data'));
    }

    public function edit(ProductBundle $bundle)
    {
        $role = Auth::user()->getRoleNames()->first();
        $bundle->load('product_bundle_items');
        $products = Product::select('id', 'name', 'selling_price')->orderBy('name')->get();
        $role = Auth::user()->getRoleNames()->first();
        $data = [
            'title' => 'Edit Bundle Produk',
            'role' => $role,
            'active' => 'bundle_index',
            'breadcrumbs' => [['name' => 'Bundle Produk', 'link' => route('owner.bundle.index')], ['name' => 'Edit Bundle Produk', 'link' => route('owner.bundle.edit', $bundle->id)]],
            'brands' => ProductBrand::all(['id', 'name']),
            'bundle' => $bundle,
            'products' => $products,
        ];
        return view($role . '.bundle.edit', compact('data'));
    }

    public function update(Request $request, ProductBundle $bundle)
    {
        $validated = $request->validate([
            'bundle_name' => 'required|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'special_bundle_price' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $productPrices = Product::whereIn('id', collect($validated['items'])->pluck('product_id'))->pluck('selling_price', 'id');
        $original = 0;
        foreach ($validated['items'] as $it) {
            $original += (float) ($productPrices[$it['product_id']] ?? 0) * (int) $it['quantity'];
        }

        // Cek apakah ada file foto yang diupload
        $flyerPath = $bundle->flyer;
        if ($request->hasFile('flyer')) {
            if ($flyerPath && Storage::disk('public')->exists($flyerPath)) {
                Storage::disk('public')->delete($flyerPath);
            }

            $file = $request->file('flyer');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $flyerPath = Storage::disk('public')->putFileAs('uploads/images/product_bundles', $file, $filename);
        }

        DB::transaction(function () use ($bundle, $validated, $original, $flyerPath) {
            $bundle->update([
                'bundle_name' => $validated['bundle_name'],
                'flyer' => $flyerPath,
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'special_bundle_price' => $validated['special_bundle_price'],
                'original_price' => $original,
            ]);

            ProductBundleItem::where('product_bundle_id', $bundle->id)->delete();
            foreach ($validated['items'] as $it) {
                ProductBundleItem::create([
                    'product_bundle_id' => $bundle->id,
                    'product_id' => $it['product_id'],
                    'quantity' => $it['quantity'],
                ]);
            }
        });

        return redirect()->route('owner.bundle.index')->with('success', 'Bundle berhasil diperbarui');
    }

    public function destroy(ProductBundle $bundle)
    {
        if ($bundle->flyer != 'uploads/images/product_bundles/bundle-1.png') {
            if ($bundle->flyer && Storage::disk('public')->exists($bundle->flyer)) {
                Storage::disk('public')->delete(paths: $bundle->flyer);
            } else {
                // Opsional: Log peringatan jika path gambar ada di DB tapi file tidak ditemukan di storage
                // Ini bisa terjadi jika file sudah dihapus secara manual atau ada inkonsistensi data
                if ($bundle->flyer) {
                    \Log::warning('File gambar tidak ditemukan di storage saat mencoba menghapus: ' . $bundle->flyer);
                }
            }
        }
        DB::transaction(function () use ($bundle) {
            ProductBundleItem::where('product_bundle_id', $bundle->id)->delete();
            $bundle->delete();
        });
        return response()->json(['success' => 'Bundle dihapus.']);
    }

    public function deleteImage(ProductBundle $bundle, Request $request)
    {
        if ($request->ajax()) {
            try {
                if ($bundle->flyer && Storage::disk('public')->exists($bundle->flyer)) {
                    Storage::disk('public')->delete($bundle->flyer);
                    $flyerPath = 'uploads/images/product_bundles/bundle-1.png';
                    $bundle->update([
                        'flyer' => $flyerPath,
                    ]);
                    return response()->json(['success' => 'Berhasil menghapus gambar ' . $bundle->name, 'flyer' => $flyerPath]);
                }
            } catch (\Exception $e) {
                // Log error untuk debugging
                \Log::error('Gagal menghapus flyer bundle: ' . $e->getMessage(), ['bundle_id' => $bundle->id]);
                return response()->json(['error' => 'Gagal menghapus flyer bundle. ' . $e->getMessage()], 500);
            }
        }
    }

    public function toggleActive(Request $request, ProductBundle $bundle)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $bundle->is_active = (bool) $request->is_active;
        $bundle->save();
        return response()->json(['success' => true, 'is_active' => $bundle->is_active]);
    }

    /* =====================================================
     *  Simpan filter periode/brand ke session saat analisis
     * ===================================================== */
    public function analyze(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'brand_id' => 'nullable|exists:product_brands,id',
        ]);

        // Simpan ke session → dipakai oleh build() & relatedRank()
        session([
            'bundle_filter' => [
                'from' => $request->filled('from') ? $request->input('from') : null,
                'to' => $request->filled('to') ? $request->input('to') : null,
                'brand_id' => $request->brand_id ? (int) $request->brand_id : null,
                'range' => $request->filled('from') && $request->filled('to') ? 'custom' : 'all',
            ],
        ]);

        // ====== ambil transaksi & jalankan FP-Growth (kode asli kamu) ======
        $txQuery = SalesTransaction::query();
        if ($request->filled('from') && $request->filled('to')) {
            // Inclusive sampai end-of-day
            $start = Carbon::parse($request->input('from'))->startOfDay();
            $end = Carbon::parse($request->input('to'))->endOfDay();
            $txQuery->whereBetween('invoice_date', [$start, $end]);
        }
        // else: semua waktu (tanpa filter tanggal)
        $transactions = $txQuery->with(['sales_transaction_items.product'])->get();

        $payload = [];
        foreach ($transactions as $tx) {
            $items = $tx->sales_transaction_items;
            if ($request->filled('brand_id')) {
                $items = $items->filter(function ($it) use ($request) {
                    return optional($it->product)->product_brand_id == $request->brand_id;
                });
            }
            $productIds = $items
                ->map(function ($it) {
                    return (int) $it->product_id;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            if (!empty($productIds)) {
                $payload[$tx->invoice_id] = $productIds;
            }
        }

        if (empty($payload)) {
            return back()->with('warning', 'Tidak ada data transaksi yang cocok dengan filter.');
        }
        $fp = app(FpClient::class);
        $result = $fp->mineRules($payload, 0.02, 0.3, 50, 'confidence');
        $rules = $result['rules'] ?? [];

        ProductAssociation::query()->delete();
        foreach ($rules as $assoc) {
            ProductAssociation::create([
                'atecedent_product_ids' => json_encode($assoc['antecedent_ids'] ?? []), // (typo dipertahankan)
                'consequent_product_ids' => json_encode($assoc['consequent_ids'] ?? []),
                'support' => $assoc['support'] ?? 0,
                'confidence' => $assoc['confidence'] ?? 0,
                'lift' => $assoc['lift'] ?? 0,
                'analysis_date' => now()->toDateString(),
            ]);
        }

        return redirect()->route('owner.bundle.build')->with('success', 'Analisis berhasil. Silakan pilih produk.');
    }

    /* ==========================================================
     *  Build: listing awal produk + freq sesuai periode & brand
     * ========================================================== */
    public function build(Request $request)
    {
        $role = Auth::user()->getRoleNames()->first();
        $filter = $this->currentBundleFilter();

        // Subquery frekuensi per periode (join sales_transactions)
        $freqQuery = SalesTransactionItem::query()->select('sales_transaction_items.product_id', DB::raw('COUNT(*) as freq'))->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id');

        if ($filter['from'] && $filter['to']) {
            $freqQuery->whereBetween('sales_transactions.invoice_date', [$filter['from'], $filter['to']]);
        }

        if ($filter['brand_id']) {
            $freqQuery->join('products', 'products.id', '=', 'sales_transaction_items.product_id')->where('products.product_brand_id', $filter['brand_id']);
        }

        $freqSub = $freqQuery->groupBy('sales_transaction_items.product_id');

        // LEFT JOIN ke semua produk (biar produk baru freq=0 tetap muncul)
        $products = Product::leftJoinSub($freqSub, 'f', 'f.product_id', '=', 'products.id')->when($filter['brand_id'], fn($q) => $q->where('products.product_brand_id', $filter['brand_id']))->select('products.id', 'products.name', 'products.selling_price', DB::raw('COALESCE(f.freq,0) as freq'))->orderByDesc('freq')->orderBy('products.name')->get()->map(
            fn($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'selling_price' => (float) $row->selling_price,
                'freq' => (int) $row->freq,
            ],
        );

        $role = Auth::user()->getRoleNames()->first();
        $data = [
            'title' => 'Tambah Bundle Produk',
            'role' => $role,
            'active' => 'bundle_index',
            'breadcrumbs' => [['name' => 'Bundle Produk', 'link' => route('owner.bundle.index')], ['name' => 'Tambah Bundle Produk', 'link' => route('owner.bundle.build')]],
            'brands' => ProductBrand::all(['id', 'name']),
            'products' => $products,
        ];
        return view($role . '.bundle.build', compact('data'));
    }

    /* ===================================================
     *  API: ranking dinamis + kirim freq sesuai periode
     * =================================================== */
    public function relatedRank(Request $request)
    {
        $validated = $request->validate([
            'selected_ids' => 'array',
            'selected_ids.*' => 'integer|exists:products,id',
        ]);

        $selected = collect($validated['selected_ids'] ?? [])
            ->unique()
            ->values();
        $filter = $this->currentBundleFilter();

        // Ambil semua produk (batasi brand jika ada)
        $allProductsQ = Product::select('id', 'name', 'selling_price');
        if ($filter['brand_id']) {
            $allProductsQ->where('product_brand_id', $filter['brand_id']);
        }
        $allProducts = $allProductsQ->get()->keyBy('id');

        // Frekuensi per periode
        $freqQuery = SalesTransactionItem::query()->select('sales_transaction_items.product_id', DB::raw('COUNT(*) as freq'))->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id');

        if ($filter['from'] && $filter['to']) {
            $freqQuery->whereBetween('sales_transactions.invoice_date', [$filter['from'], $filter['to']]);
        }
        if ($filter['brand_id']) {
            $freqQuery->join('products', 'products.id', '=', 'sales_transaction_items.product_id')->where('products.product_brand_id', $filter['brand_id']);
        }

        $freq = $freqQuery->groupBy('sales_transaction_items.product_id')->pluck('freq', 'product_id'); // Map product_id => freq (sesuai periode)

        // Awal: belum ada pilihan → urutkan terlaris periode lalu sisanya
        if ($selected->isEmpty()) {
            $terlaris = $freq->sortDesc()->keys();
            $sisanya = $allProducts->keys()->diff($terlaris);
            $ordered = $terlaris->concat($sisanya)->values();

            $list = $ordered
                ->map(function ($id) use ($allProducts, $freq) {
                    $p = $allProducts->get($id);
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'selling_price' => (float) $p->selling_price,
                        'freq' => (int) ($freq[$p->id] ?? 0), // << jumlah terjual di periode
                    ];
                })
                ->values();

            return response()->json(['products' => $list]);
        }

        // Ada pilihan: skor asosiasi + bobot kecil dari freq periode
        $assocs = ProductAssociation::query()
            ->where(function ($q) use ($selected) {
                foreach ($selected as $sid) {
                    $q->orWhereJsonContains('atecedent_product_ids', (int) $sid); // (typo kolom dipertahankan)
                }
            })
            ->get(['consequent_product_ids', 'confidence', 'lift']);

        $score = [];
        foreach ($assocs as $a) {
            $conseq = json_decode($a->consequent_product_ids, true) ?: [];
            foreach ($conseq as $cid) {
                if ($selected->contains($cid)) {
                    continue;
                }
                $score[$cid] = ($score[$cid] ?? 0) + (float) $a->confidence + 0.1 * (float) $a->lift;
            }
        }
        // pastikan semua produk sisa ikut perangkingan + tambah bobot freq periode
        foreach ($allProducts->keys() as $pid) {
            if ($selected->contains($pid)) {
                continue;
            }
            $score[$pid] = ($score[$pid] ?? 0) + 0.001 * (int) ($freq[$pid] ?? 0);
        }

        arsort($score);
        $orderedIds = collect(array_keys($score));

        $list = $orderedIds
            ->map(function ($id) use ($allProducts, $freq) {
                $p = $allProducts->get($id);
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'selling_price' => (float) $p->selling_price,
                    'freq' => (int) ($freq[$p->id] ?? 0), // << jumlah terjual di periode
                ];
            })
            ->values();

        return response()->json(['products' => $list]);
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'bundle_name' => ['required', 'string'],
                'flyer' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
                'description' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'special_bundle_price' => ['required', 'numeric', 'min:0'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'exists:products,id'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
            ],
            [
                'bundle_name.required' => 'Nama bundle wajib diisi.',
                'flyer.image' => 'File flyer harus berupa gambar.',
                'flyer.mimes' => 'Flyer harus berformat jpeg, png, jpg, atau webp.',
                'flyer.max' => 'Ukuran flyer maksimal 2MB.',
                'description.required' => 'Deskripsi wajib diisi.',
                'description.string' => 'Deskripsi harus berupa teks.',
                'start_date.required' => 'Tanggal mulai wajib diisi.',
                'start_date.date' => 'Tanggal mulai tidak valid.',
                'end_date.required' => 'Tanggal berakhir wajib diisi.',
                'end_date.date' => 'Tanggal berakhir tidak valid.',
                'end_date.after_or_equal' => 'Tanggal berakhir harus sama atau setelah tanggal mulai.',
                'special_bundle_price.required' => 'Harga spesial wajib diisi.',
                'special_bundle_price.numeric' => 'Harga spesial harus berupa angka.',
                'special_bundle_price.min' => 'Harga spesial minimal 0.',
                'items.required' => 'Minimal 1 produk harus dipilih.',
                'items.array' => 'Format item tidak valid.',
                'items.min' => 'Minimal 1 produk harus dipilih.',
                'items.*.product_id.required' => 'Produk pada item wajib diisi.',
                'items.*.product_id.exists' => 'Produk yang dipilih tidak ditemukan.',
                'items.*.quantity.required' => 'Jumlah produk pada item wajib diisi.',
                'items.*.quantity.integer' => 'Jumlah produk harus berupa angka bulat.',
                'items.*.quantity.min' => 'Jumlah produk minimal 1.',
            ],
        );

        // Cek apakah ada file foto yang diupload
        $flyerPath = 'uploads/images/product_bundles/bundle-1.png';
        if ($request->hasFile('flyer')) {
            $file = $request->file('flyer');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $flyerPath = Storage::disk('public')->putFileAs('uploads/images/product_bundles', $file, $filename);
        }

        // Hitung original price dari item
        $productPrices = Product::whereIn('id', collect($request->items)->pluck('product_id'))->pluck('selling_price', 'id');
        $originalPrice = 0;
        foreach ($request->items as $it) {
            $originalPrice += ((float) ($productPrices[$it['product_id']] ?? 0)) * (int) $it['quantity'];
        }

        $bundle = ProductBundle::create([
            'bundle_name' => $request->bundle_name,
            'flyer' => $flyerPath,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'special_bundle_price' => $request->special_bundle_price,
            'original_price' => $originalPrice,
            'is_active' => true,
        ]);

        foreach ($request->items as $it) {
            ProductBundleItem::create([
                'product_bundle_id' => $bundle->id,
                'product_id' => $it['product_id'],
                'quantity' => $it['quantity'],
            ]);
        }

        session()->flash('success', 'Berhasil menambahkan bundle');

        return response()->json([
            'success' => true,
            'bundle_id' => $bundle->id,
            'redirect' => route('owner.bundle.index'),
        ]);
    }
}
