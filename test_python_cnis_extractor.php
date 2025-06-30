<?php

require_once 'vendor/autoload.php';

use App\Services\PythonCNISExtractorService;
use Illuminate\Support\Facades\Log;

// Configura o Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Teste do Python CNIS Extractor ===\n\n";

try {
    // Inicializa o serviço
    $service = new PythonCNISExtractorService();
    
    // Verifica o ambiente Python
    echo "🔍 Verificando ambiente Python...\n";
    $environment = $service->checkPythonEnvironment();
    
    foreach ($environment as $check => $value) {
        if ($check === 'required_modules') {
            echo "   📦 Módulos Python:\n";
            foreach ($value as $module => $available) {
                echo "      " . ($available ? "✅" : "❌") . " {$module}\n";
            }
        } else {
            echo "   " . ($value ? "✅" : "❌") . " {$check}: " . (is_string($value) ? $value : ($value ? 'OK' : 'FALHOU')) . "\n";
        }
    }
    
    // Se Python não está disponível, tenta instalar dependências
    if (!$environment['python_executable']) {
        echo "\n❌ Python não encontrado. Certifique-se de que o Python está instalado e configurado.\n";
        exit(1);
    }
    
    if (!$environment['python_script']) {
        echo "\n❌ Script Python não encontrado.\n";
        exit(1);
    }
    
    // Verifica se há módulos faltando
    $missingModules = array_filter($environment['required_modules'], function($available) {
        return !$available;
    });
    
    if (!empty($missingModules)) {
        echo "\n📦 Instalando dependências Python...\n";
        $installResult = $service->installDependencies();
        
        if ($installResult['success']) {
            echo "✅ Dependências instaladas com sucesso\n";
        } else {
            echo "❌ Erro na instalação: {$installResult['error']}\n";
            echo "Instale manualmente com: pip install -r requirements.txt\n";
        }
    }
    
    // Arquivo de teste
    $testFile = 'CNIS.pdf';
    
    if (!file_exists($testFile)) {
        echo "❌ Arquivo de teste não encontrado: $testFile\n";
        exit(1);
    }
    
    echo "\n📄 Processando arquivo: $testFile\n";
    echo "⏳ Aguarde, isso pode levar alguns segundos...\n\n";
    
    // Processa o documento
    $startTime = microtime(true);
    $result = $service->processCNIS($testFile);
    $endTime = microtime(true);
    
    $processingTime = round($endTime - $startTime, 2);
    
    // Debug: mostra o resultado bruto
    echo "🔍 Debug - Resultado bruto:\n";
    var_dump($result);
    echo "\n";
    
    if ($result['success']) {
        echo "✅ Processamento concluído em {$processingTime}s\n\n";
        
        // Exibe dados pessoais
        echo "=== DADOS PESSOAIS ===\n";
        echo "📋 Nome: {$result['data']['client_name']}\n";
        echo "📋 CPF: {$result['data']['client_cpf']}\n";
        
        echo "\n=== VÍNCULOS EMPREGATÍCIOS ===\n";
        $vinculos = $result['data']['vinculos_empregaticios'];
        if (!empty($vinculos)) {
            foreach ($vinculos as $index => $vinculo) {
                echo "\n🏢 Vínculo " . ($index + 1) . ":\n";
                foreach ($vinculo as $campo => $valor) {
                    echo "   📋 {$campo}: {$valor}\n";
                }
            }
        } else {
            echo "❌ Nenhum vínculo empregatício extraído\n";
        }
        
        // Exibe metadados
        if (isset($result['metadata'])) {
            echo "\n=== METADADOS ===\n";
            foreach ($result['metadata'] as $campo => $valor) {
                echo "📊 {$campo}: {$valor}\n";
            }
        }
        
    } else {
        echo "❌ Erro no processamento: {$result['error']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fim do teste ===\n"; 