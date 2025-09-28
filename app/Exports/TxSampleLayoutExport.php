<?php

namespace App\Exports;

use App\Models\SalesTransaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TxSampleLayoutExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    public function __construct(protected ?string $from = null, protected ?string $to = null, protected array $statuses = ['success', 'process']) {}

    public function headings(): array
    {
        return ['txtNo', 'InvoiceID', 'ivoDate', 'prodPurchased', 'Quantity'];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_XLSX15, // d-mmm-yy
            'E' => NumberFormat::FORMAT_NUMBER, // qty integer
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('D:D')->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension('D')->setWidth(60);
        return [];
    }

    public function collection(): Collection
    {
        $txs = SalesTransaction::query()
            ->with(['sales_transaction_items:id,sales_transaction_id,product_id,quantity_sold', 'sales_transaction_items.product:id,name'])
            ->when($this->from, fn($q) => $q->whereDate('invoice_date', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('invoice_date', '<=', $this->to))
            ->when(!empty($this->statuses), fn($q) => $q->whereIn('transaction_status', $this->statuses))
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $rows = collect();
        $no = 1;

        foreach ($txs as $trx) {
            $items = $trx->sales_transaction_items;

            if ($items->isEmpty()) {
                // tulis satu baris transaksi tanpa pemisah
                $rows->push([$no, (string) $trx->invoice_id, $trx->invoice_date ? date_create($trx->invoice_date) : null, null, null]);
                $no++;
                continue;
            }

            foreach ($items as $idx => $item) {
                $rows->push([
                    $idx === 0 ? $no : null, // txtNo hanya di baris pertama transaksi
                    $idx === 0 ? (string) $trx->invoice_id : null,
                    $idx === 0 ? ($trx->invoice_date ? date_create($trx->invoice_date) : null) : null,
                    optional($item->product)->name ?? ($item->product_name ?? '-'),
                    (int) ($item->quantity_sold ?? 0),
                ]);
            }

            // tidak ada baris kosong pemisah di sini
            $no++;
        }

        return $rows;
    }
}
