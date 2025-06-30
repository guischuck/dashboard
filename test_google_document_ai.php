<?php

require_once 'vendor/autoload.php';

use App\Services\GoogleDocumentAiService;
use Illuminate\Support\Facades\Log;

// Configura o Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Teste do Google Document AI ===\n\n";

try {
    // Inicializa o serviço
    $service = new GoogleDocumentAiService();
    
    // Arquivo de teste
    $testFile = 'CNIS.pdf';
    
    if (!file_exists($testFile)) {
        echo "❌ Arquivo de teste não encontrado: $testFile\n";
        exit(1);
    }
    
    echo "📄 Processando arquivo: $testFile\n";
    echo "⏳ Aguarde, isso pode levar alguns segundos...\n\n";
    
    // Processa o documento
    $startTime = microtime(true);
    $result = $service->processCNIS($testFile);
    $endTime = microtime(true);
    
    $processingTime = round($endTime - $startTime, 2);
    
    if ($result['success']) {
        echo "✅ Processamento concluído em {$processingTime}s\n\n";
        
        // Exibe dados pessoais
        $dadosPessoais = $result['data']['dados_pessoais'];
        echo "=== DADOS PESSOAIS ===\n";
        if (!empty($dadosPessoais)) {
            foreach ($dadosPessoais as $campo => $valor) {
                echo "📋 {$campo}: {$valor}\n";
            }
        } else {
            echo "❌ Nenhum dado pessoal extraído\n";
        }
        
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
        
        // Exibe estatísticas dos processadores
        if (isset($result['raw_results'])) {
            echo "\n=== ESTATÍSTICAS DOS PROCESSADORES ===\n";
            foreach ($result['raw_results'] as $processor => $data) {
                if (isset($data['error'])) {
                    echo "❌ {$processor}: {$data['error']}\n";
                } else {
                    $count = 0;
                    if (is_array($data)) {
                        if (isset($data['entities'])) {
                            $count = count($data['entities']);
                        } elseif (isset($data['form_fields'])) {
                            $count = count($data['form_fields']);
                        } elseif (isset($data['rows'])) {
                            $count = count($data['rows']);
                        } elseif (isset($data['text'])) {
                            $count = strlen($data['text']);
                        }
                    }
                    echo "✅ {$processor}: {$count} itens processados\n";
                }
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