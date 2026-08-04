<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BookingType;

class BookingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Special Entry Darshan', 'waiting_days' => 90],
            ['name' => 'Sarva Darshan', 'waiting_days' => 0],
            ['name' => 'Accommodation', 'waiting_days' => 30],
            ['name' => 'Virtual Seva', 'waiting_days' => 0],
            ['name' => 'VIP Break Darshan', 'waiting_days' => 180],
        ];

        foreach ($types as $type) {
            BookingType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
