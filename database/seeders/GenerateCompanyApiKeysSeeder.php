<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenerateCompanyApiKeysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::whereNull('api_key')->get();
        
        $this->command->info("Encontradas {$companies->count()} empresas sem API key.");
        
        foreach ($companies as $company) {
            $company->update([
                'api_key' => Str::random(32),
            ]);
            
            $this->command->info("API key gerada para empresa: {$company->name} (ID: {$company->id})");
            $this->command->line("API Key: {$company->api_key}");
            $this->command->line("");
        }
        
        $this->command->info("Processo concluído!");
    }
}
