<?php

namespace App\Exports;

use App\Models\Devotee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DevoteeExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = Devotee::query();

        // Apply multi-tenant data scoping
        if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            $query->where('user_id', auth()->id());
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('aadhaar', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('state', 'like', "%{$search}%")
                  ->orWhereHas('headFamilyMember', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Full Name',
            'Age',
            'Gender',
            'Aadhaar Number',
            'Email',
            'Phone Number',
            'City',
            'State',
            'Registered At'
        ];
    }

    public function map($devotee): array
    {
        return [
            $devotee->id,
            $devotee->name,
            $devotee->age,
            $devotee->gender,
            $devotee->aadhaar,
            $devotee->email,
            $devotee->phone,
            $devotee->city,
            $devotee->state,
            $devotee->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
