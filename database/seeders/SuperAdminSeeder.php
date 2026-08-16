<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the Super Admin role exists
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // Create the admin user safely (won't duplicate if email exists)
        $admin = User::firstOrCreate(
            ['email' => 'tarun008@gmail.com'],
            [
                'name' => 'tarun',
                'password' => Hash::make('88888888XTg@123'),
            ]
        );

        // Assign the role if they don't already have it
        if (!$admin->hasRole('Super Admin')) {
            $admin->assignRole($superAdminRole);
        }
    }
}
