<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RevenueDataImport implements ToCollection, \Maatwebsite\Excel\Concerns\WithHeadingRow
{
    public function collection(Collection $collection)
    {
        // Handled by controller using Excel::toCollection
    }
}
