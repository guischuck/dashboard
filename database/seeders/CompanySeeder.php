<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Criar empresa de exemplo
        $company = Company::create([
            'name' => 'Escritório de Advocacia Exemplo',
            'slug' => 'escritorio-exemplo',
            'email' => 'contato@escritorio-exemplo.com.br',
            'cnpj' => '12.345.678/0001-90',
            'phone' => '(11) 9999-9999',
            'address' => 'Rua das Flores, 123, Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01000-000',
            'plan' => 'premium',
            'max_users' => 10,
            'max_cases' => 500,
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
        ]);

        // Criar super admin
        $superAdmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'superadmin@previdia.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Criar admin da empresa
        $admin = User::create([
            'name' => 'Administrador do Escritório',
            'email' => 'admin@escritorio-exemplo.com.br',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Criar usuário comum da empresa
        $user = User::create([
            'name' => 'Usuário do Escritório',
            'email' => 'usuario@escritorio-exemplo.com.br',
            'password' => Hash::make('password'),
            'company_id' => $company->id,
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Criar segunda empresa de exemplo
        $company2 = Company::create([
            'name' => 'Advocacia Silva & Associados',
            'slug' => 'silva-associados',
            'email' => 'contato@silva-associados.adv.br',
            'cnpj' => '98.765.432/0001-10',
            'phone' => '(21) 8888-8888',
            'address' => 'Av. Copacabana, 456, Copacabana',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
            'zip_code' => '22000-000',
            'plan' => 'basic',
            'max_users' => 5,
            'max_cases' => 100,
            'is_active' => true,
            'trial_ends_at' => now()->addDays(15),
        ]);

        // Criar admin da segunda empresa
        $admin2 = User::create([
            'name' => 'Dr. João Silva',
            'email' => 'joao@silva-associados.adv.br',
            'password' => Hash::make('password'),
            'company_id' => $company2->id,
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Empresas e usuários criados com sucesso!');
        $this->command->info('');
        $this->command->info('🔑 Credenciais de acesso:');
        $this->command->info('');
        $this->command->info('Super Admin:');
        $this->command->info('Email: superadmin@previdia.com');
        $this->command->info('Senha: password');
        $this->command->info('');
        $this->command->info('Admin Escritório Exemplo:');
        $this->command->info('Email: admin@escritorio-exemplo.com.br');
        $this->command->info('Senha: password');
        $this->command->info('');
        $this->command->info('Admin Silva & Associados:');
        $this->command->info('Email: joao@silva-associados.adv.br');
        $this->command->info('Senha: password');
    }
}
