<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonthlyBookClosing;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MonthlyClosingsImport;

class MonthlyClosingImportController
{
    public function form()
    {
      $data = [
            'title' => 'Import Tutup Buku Bulanan',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'closing_create',
            'breadcrumbs' => [
                [
                    'name' => 'Tutup Buku Bulanan',
                    'link' => '#',
                ],
                [
                    'name' => 'Import Tutup Buku',
                    'link' => route('owner.closing.import.form'),
                ],
            ],
            'monthly_closings' => MonthlyBookClosing::all(),
        ];
        return view('owner.closing.import', compact('data'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:20480', // 20MB
        ]);

        $importer = new MonthlyClosingsImport();
        try {
            Excel::import($importer, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }

        $s = $importer->summary;
        $msg = "Import selesai. Inserted: {$s['inserted']}, Updated: {$s['updated']}, Skipped: {$s['skipped']}.";
        if (!empty($s['errors'])) {
            $msg .= ' Beberapa baris di-skip.';
            // Simpan error detail ke session untuk ditampilkan
            return back()->with('warning', $msg)->with('import_errors', $s['errors']);
        }

        return back()->with('success', $msg);
    }
}
