<?php

namespace App\Exports;

use App\Models\IndianStock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class IndianStocksExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return IndianStock::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Stock Symbol',
            'Stock Name',
            'Quantity',
            'Buy Price (INR)',
            'Total Investment (INR)',
            'Buy Date',
        ];
    }

    public function map($stock): array
    {
        return [
            $stock->id,
            $stock->stock_symbol,
            $stock->stock_name,
            $stock->quantity,
            $stock->buy_price,
            number_format($stock->quantity * $stock->buy_price, 2, '.', ''),
            $stock->buy_date->format('Y-m-d'),
        ];
    }
}
