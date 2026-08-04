<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SevaType;

class SevaTypeSeeder extends Seeder
{
    public function run(): void
    {
        $sevas = [
            ['name' => 'Arjitha Seva', 'cooldown_months' => 6, 'display_order' => 1],
            ['name' => 'Virtual Seva', 'cooldown_months' => 4, 'display_order' => 2],
            ['name' => 'Angapradakshanam', 'cooldown_months' => 3, 'display_order' => 3],
            ['name' => 'Senior Citizen Darshan', 'cooldown_months' => 3, 'display_order' => 4],
            ['name' => 'Special Entry Darshan (₹300)', 'cooldown_months' => 1, 'display_order' => 5],
            ['name' => 'Accommodation', 'cooldown_months' => 1, 'display_order' => 6],
            ['name' => 'Srinivasa Divyanugraha Homam', 'cooldown_months' => 1, 'display_order' => 7],
        ];

        foreach ($sevas as $seva) {
            SevaType::updateOrCreate(
                ['name' => $seva['name']],
                $seva
            );
        }
    }
}
