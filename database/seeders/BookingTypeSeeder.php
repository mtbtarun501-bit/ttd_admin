<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BookingType;

class BookingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Special Entry Darshan', 'waiting_days' => 90, 'price' => 300, 'commission_rate' => 300],
            ['name' => 'Sarva Darshan', 'waiting_days' => 0, 'price' => 0, 'commission_rate' => 0],
            ['name' => 'Virtual Seva and Arjitha Seva', 'waiting_days' => 0, 'price' => 500, 'commission_rate' => 300],
            ['name' => 'Angapradakshina', 'waiting_days' => 0, 'price' => 0, 'commission_rate' => 300],
            ['name' => 'Senior Citizen', 'waiting_days' => 0, 'price' => 0, 'commission_rate' => 300],
            ['name' => 'Tirumala Accommodation (Rs. 999)', 'waiting_days' => 30, 'price' => 999, 'commission_rate' => 500],
            ['name' => 'Tirumala Accommodation (Rs. 1999)', 'waiting_days' => 30, 'price' => 1999, 'commission_rate' => 500],
            ['name' => 'Homam Tickets', 'waiting_days' => 0, 'price' => 1600, 'commission_rate' => 450],
            ['name' => 'VIP Break Darshan', 'waiting_days' => 180, 'price' => 0, 'commission_rate' => 0],
        ];

        foreach ($types as $type) {
            BookingType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
