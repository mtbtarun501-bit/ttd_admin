<?php

namespace App\Exports;

use App\Models\PhoneUsage;
use App\Services\PhoneUsageService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PhoneUsageExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected PhoneUsageService $phoneUsageService;

    public function __construct(PhoneUsageService $phoneUsageService)
    {
        $this->phoneUsageService = $phoneUsageService;
    }

    public function collection()
    {
        return PhoneUsage::with(['serviceStatuses.sevaType'])->get();
    }

    public function headings(): array
    {
        return [
            'Member Name',
            'Mobile Number',
            'Status',
            'Can Book Today',
            'Next Eligible Date'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'B08D3E'],
                ],
            ],
        ];
    }

    public function map($phoneUsage): array
    {
        $eligibleSevas = $this->phoneUsageService->getEligibleSevasToday($phoneUsage);

        $canBookToday = $eligibleSevas->isEmpty()
            ? 'None'
            : $eligibleSevas->pluck('name')->implode(', ');

        $today = Carbon::today();
        $upcoming = $phoneUsage->serviceStatuses->filter(function($status) use ($today) {
            return $status->next_eligible_date && $status->next_eligible_date->greaterThan($today);
        })->sortBy('next_eligible_date')->first();

        $nextEligibleDate = $upcoming
            ? $upcoming->next_eligible_date->format('d M Y')
            : 'N/A';

        return [
            $phoneUsage->member_name,
            $phoneUsage->mobile_number,
            $phoneUsage->status,
            $canBookToday,
            $nextEligibleDate,
        ];
    }
}