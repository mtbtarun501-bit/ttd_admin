<?php

namespace App\Exports;

use App\Models\Revenue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RevenueExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Revenue::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Source',
            'Amount (INR)',
            'Date Received',
            'Remarks',
            'Recorded At'
        ];
    }

    public function map($revenue): array
    {
        return [
            $revenue->id,
            $revenue->source,
            $revenue->amount,
            $revenue->revenue_date,
            $revenue->remarks,
            $revenue->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
