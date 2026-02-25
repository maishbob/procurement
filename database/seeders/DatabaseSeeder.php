<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\FiscalYear;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create fiscal year
        FiscalYear::firstOrCreate(
            ['start_date' => '2024-07-01'],
            [
                'end_date' => '2025-06-30',
                'name' => '2024/2025',
                'is_active' => true,
            ]
        );

        // Seed roles and permissions
        $this->call(RolesAndPermissionsSeeder::class);



        // Create default admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@procurement.local'],
            [
                'name' => 'System Administrator',
                'password' => env('SEED_DEFAULT_PASSWORD', \Illuminate\Support\Str::random(24)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Assign admin role
        if (!$adminUser->hasRole('Super Administrator')) {
            $adminUser->assignRole('Super Administrator');
        }

        // Create finance users
        $financeUser = User::firstOrCreate(
            ['email' => 'finance@procurement.local'],
            [
                'name' => 'Finance Officer',
                'password' => env('SEED_DEFAULT_PASSWORD', \Illuminate\Support\Str::random(24)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (!$financeUser->hasRole('Finance Manager')) {
            $financeUser->assignRole('Finance Manager');
        }

        // Create procurement users
        $procurementUser = User::firstOrCreate(
            ['email' => 'procurement@procurement.local'],
            [
                'name' => 'Procurement Officer',
                'password' => env('SEED_DEFAULT_PASSWORD', \Illuminate\Support\Str::random(24)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (!$procurementUser->hasRole('Procurement Officer')) {
            $procurementUser->assignRole('Procurement Officer');
        }

        // Create department head
        $deptHeadUser = User::firstOrCreate(
            ['email' => 'depthead@procurement.local'],
            [
                'name' => 'Department Head',
                'password' => env('SEED_DEFAULT_PASSWORD', \Illuminate\Support\Str::random(24)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (!$deptHeadUser->hasRole('Head of Department')) {
            $deptHeadUser->assignRole('Head of Department');
        }

        $departments = [
            ['name' => 'Housekeeping', 'code' => 'HSK', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Kitchen', 'code' => 'KIT', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'cafe', 'code' => 'CAFE', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Farm', 'code' => 'FARM', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Garage/Vehicles', 'code' => 'GAR', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Repair & Maintenance', 'code' => 'R&M', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Development', 'code' => 'DEV', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Administration', 'code' => 'ADM', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Human Resource', 'code' => 'HR', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Finance', 'code' => 'FIN', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Academics KG', 'code' => 'AKG', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Academics Prep', 'code' => 'APRP', 'head_of_department_id' => $deptHeadUser->id],
            ['name' => 'Academics SEC', 'code' => 'ASEC', 'head_of_department_id' => $deptHeadUser->id],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                $dept
            );
        }

        // Assign PesaPal gateway roles based on Spatie roles
        $this->call(PaymentGatewayRoleSeeder::class);

        $this->command->info('Database seeding completed successfully!');
    }
}
