<?php

namespace App\Imports;

use App\Models\Devotee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;

class DevoteeImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Require name and phone at least
        if (empty($row['name']) || empty($row['phone'])) {
            return null;
        }

        // Check if Aadhaar or Phone already exists to prevent duplicates
        $existing = null;
        if (!empty($row['aadhaar'])) {
            $existing = Devotee::where('aadhaar', $row['aadhaar'])->first();
        }
        if (!$existing && !empty($row['phone'])) {
            $existing = Devotee::where('phone', $row['phone'])->first();
        }

        if ($existing) {
            // Optional: Update existing record, for now we just return it to not create duplicate
            return null; 
        }

        $headDevoteeId = null;
        if (!empty($row['referred'])) {
            $referredName = trim($row['referred']);
            
            // Try to find an exact matching agent or any devotee by name
            $agent = Devotee::where('name', 'like', $referredName)->first();
            
            if ($agent) {
                // Ensure they are marked as an agent
                if (!$agent->is_head_of_family) {
                    $agent->is_head_of_family = true;
                    $agent->save();
                }
                $headDevoteeId = $agent->id;
            } else {
                // Agent doesn't exist, create a stub agent dynamically!
                $newAgent = Devotee::create([
                    'user_id' => Auth::id() ?? 1,
                    'name' => $referredName,
                    'age' => 30, // Default required field
                    'gender' => 'Unknown', // Default required field
                    'is_head_of_family' => true,
                    'remarks' => 'Auto-created from Excel Import'
                ]);
                $headDevoteeId = $newAgent->id;
            }
        }

        return new Devotee([
            'user_id' => Auth::id() ?? 1,
            'name' => $row['name'],
            'age' => $row['age'] ?? null,
            'gender' => ucfirst(strtolower($row['gender'] ?? '')),
            'aadhaar' => $row['aadhaar'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'city' => $row['city'] ?? null,
            'state' => $row['state'] ?? null,
            'pin_code' => $row['pincode'] ?? null,
            'gothram' => $row['gothram'] ?? null,
            'remarks' => $row['remarks'] ?? null,
            'is_head_of_family' => false,
            'head_devotee_id' => $headDevoteeId,
        ]);
    }
}
