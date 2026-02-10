<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call(RoleSeeder::class);

        // Create a test company and branch
        $company = Company::create([
            'name' => 'Test Company',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'address' => '123 Test Street',
            'phone' => '+1234567890',
        ]);

        $branch2 = Branch::create([
            'company_id' => $company->id,
            'name' => 'Second Branch',
            'address' => '456 Second Avenue',
            'phone' => '+0987654321',
        ]);

        // Get roles
        $adminRole = Role::where('role_name', 'Admin')->first();
        $salesRole = Role::where('role_name', 'Sales Person')->first();

        // Create admin user (not tied to any branch)
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'branch_id' => null, // Admin not tied to any branch
        ]);

        // Create sales person user
        User::create([
            'name' => 'Sales Person',
            'email' => 'sales@example.com',
            'password' => Hash::make('password'),
            'role_id' => $salesRole->id,
            'branch_id' => $branch2->id,
        ]);

        // Create suppliers
        Supplier::create([
            'name' => 'ABC Suppliers Ltd',
            'phone' => '+255712345678',
            'email' => 'abc@suppliers.com',
            'address' => 'Dar es Salaam, Tanzania',
        ]);

        Supplier::create([
            'name' => 'XYZ Trading Co',
            'phone' => '+255723456789',
            'email' => 'xyz@trading.co.tz',
            'address' => 'Arusha, Tanzania',
        ]);

        Supplier::create([
            'name' => 'Global Imports',
            'phone' => '+255734567890',
            'email' => 'info@globalimports.com',
            'address' => 'Mwanza, Tanzania',
        ]);
    }
}
