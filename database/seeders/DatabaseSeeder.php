<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\LegalCase;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar super admin se não existir
        if (!User::where('email', 'admin@previdia.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@previdia.com',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        $this->call([
            CompanySeeder::class,
            SubscriptionPlanSeeder::class,
            CompanySubscriptionSeeder::class,
            PetitionTemplateSeeder::class,
            WorkflowTemplateSeeder::class,
        ]);
    }
}
