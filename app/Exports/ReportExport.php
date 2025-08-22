<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Reader\Xml\Style\NumberFormat;

class ReportExport implements FromCollection, WithHeadings, WithColumnFormatting, ShouldAutoSize
{
    public function __construct(private array $filters, private $from, private $to) {}

    public function collection()
    {
        $r = (object) $this->filters;

        $q = DB::table('sales_transactions as st')
            ->leftJoin('purchase_orders as po', 'po.id', '=', 'st.purchase_order_id')
            ->leftJoin('customers as c', 'c.id', '=', 'po.customer_id')
            ->leftJoin('sales_agents as sa', 'sa.id', '=', 'st.sales_agent_id')
            ->when($this->from, fn($q) => $q->whereDate('st.invoice_date', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('st.invoice_date', '<=', $this->to))
            ->when($r->customer_id ?? null, fn($q, $v) => $q->where('po.customer_id', $v))
            ->when($r->sales_id ?? null, fn($q, $v) => $q->where('st.sales_agent_id', $v))
            ->when($r->status ?? null, fn($q, $v) => $q->where('st.transaction_status', strtolower($v)))
            ->selectRaw(
                '
                st.invoice_id as invoice_number,
                st.invoice_date as date,
                COALESCE(c.name,"-")  as customer,
                COALESCE(sa.name,"-") as sales,
                st.initial_total_amount as subtotal,
                (st.initial_total_amount - st.final_total_amount) as discount,
                (SELECT COALESCE(SUM(dr.total_amount),0)
                   FROM delivery_returns dr
                  WHERE dr.sales_transaction_id = st.id) as return_total,
                st.final_total_amount as total,
                st.transaction_status as status
            ',
            )
            ->orderBy('st.invoice_date', 'desc');

        return $q->get();
    }

    public function headings(): array
    {
        return ['Invoice No', 'Tanggal', 'Customer', 'Sales', 'Subtotal', 'Diskon', 'Retur', 'Total', 'Status'];
    }

    public function columnFormats(): array
    {
        return [
            'E' => '"Rp" #,##0_-',
            'F' => '"Rp" #,##0_-',
            'G' => '"Rp" #,##0_-',
            'H' => '"Rp" #,##0_-',
        ];
    }
}
