<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:api-key 
                           {action : Ação a executar (generate, regenerate, show, list)}
                           {--company= : ID da empresa (obrigatório para generate, regenerate, show)}
                           {--all : Aplicar a todas as empresas (para generate)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerenciar API keys das empresas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $companyId = $this->option('company');
        $all = $this->option('all');

        switch ($action) {
            case 'generate':
                return $this->generateApiKey($companyId, $all);
            
            case 'regenerate':
                return $this->regenerateApiKey($companyId);
            
            case 'show':
                return $this->showApiKey($companyId);
            
            case 'list':
                return $this->listApiKeys();
            
            default:
                $this->error("Ação inválida. Use: generate, regenerate, show, list");
                return 1;
        }
    }

    private function generateApiKey($companyId, $all)
    {
        if ($all) {
            $companies = Company::whereNull('api_key')->get();
            
            if ($companies->isEmpty()) {
                $this->info("Todas as empresas já têm API keys!");
                return 0;
            }
            
            $this->info("Gerando API keys para {$companies->count()} empresas...");
            
            foreach ($companies as $company) {
                $company->update(['api_key' => Str::random(32)]);
                $this->line("✓ {$company->name}: {$company->api_key}");
            }
            
            return 0;
        }
        
        if (!$companyId) {
            $this->error("Especifique o ID da empresa com --company=ID ou use --all");
            return 1;
        }
        
        $company = Company::find($companyId);
        
        if (!$company) {
            $this->error("Empresa não encontrada!");
            return 1;
        }
        
        if ($company->api_key) {
            $this->error("Empresa já possui API key! Use 'regenerate' para gerar uma nova.");
            return 1;
        }
        
        $company->update(['api_key' => Str::random(32)]);
        
        $this->info("API key gerada para {$company->name}:");
        $this->line($company->api_key);
        
        return 0;
    }

    private function regenerateApiKey($companyId)
    {
        if (!$companyId) {
            $this->error("Especifique o ID da empresa com --company=ID");
            return 1;
        }
        
        $company = Company::find($companyId);
        
        if (!$company) {
            $this->error("Empresa não encontrada!");
            return 1;
        }
        
        $oldKey = $company->api_key;
        $company->update(['api_key' => Str::random(32)]);
        
        $this->info("API key regenerada para {$company->name}:");
        $this->line("Antiga: " . ($oldKey ?: 'N/A'));
        $this->line("Nova: {$company->api_key}");
        
        return 0;
    }

    private function showApiKey($companyId)
    {
        if (!$companyId) {
            $this->error("Especifique o ID da empresa com --company=ID");
            return 1;
        }
        
        $company = Company::find($companyId);
        
        if (!$company) {
            $this->error("Empresa não encontrada!");
            return 1;
        }
        
        $this->info("API key da empresa {$company->name}:");
        $this->line($company->api_key ?: 'Nenhuma API key definida');
        
        return 0;
    }

    private function listApiKeys()
    {
        $companies = Company::select('id', 'name', 'api_key')->get();
        
        $this->info("Lista de API keys das empresas:");
        $this->line("");
        
        foreach ($companies as $company) {
            $status = $company->api_key ? '✓' : '✗';
            $key = $company->api_key ?: 'Não definida';
            
            $this->line("{$status} ID: {$company->id} | {$company->name}");
            $this->line("    API Key: {$key}");
            $this->line("");
        }
        
        return 0;
    }
}
