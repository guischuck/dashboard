<?php

require_once 'vendor/autoload.php';

// Configura o Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Teste de Processamento CNIS via API ===\n\n";

try {
    // Simula um usuário autenticado
    $user = \App\Models\User::first();
    if (!$user) {
        echo "❌ Nenhum usuário encontrado no banco de dados\n";
        exit(1);
    }
    
    auth()->login($user);
    echo "✅ Usuário autenticado: {$user->name}\n";
    
    // Cria uma requisição simulada
    $request = new \Illuminate\Http\Request();
    $request->setMethod('POST');
    $request->headers->set('Content-Type', 'multipart/form-data');
    
    // Arquivo de teste
    $testFile = 'CNIS.pdf';
    if (!file_exists($testFile)) {
        echo "❌ Arquivo de teste não encontrado: $testFile\n";
        exit(1);
    }
    
    echo "📄 Arquivo de teste: $testFile\n";
    
    // Cria um arquivo temporário para simular upload
    $tempFile = tempnam(sys_get_temp_dir(), 'cnis_test_');
    copy($testFile, $tempFile);
    
    // Cria um UploadedFile simulado
    $uploadedFile = new \Illuminate\Http\UploadedFile(
        $tempFile,
        'CNIS.pdf',
        'application/pdf',
        null,
        true
    );
    
    $request->files->set('cnis_file', $uploadedFile);
    
    echo "⏳ Processando CNIS...\n";
    
    // Chama o controller diretamente
    $controller = new \App\Http\Controllers\DocumentController(
        app(\App\Services\DocumentProcessingService::class)
    );
    
    $startTime = microtime(true);
    $response = $controller->processCnis($request);
    $endTime = microtime(true);
    
    $processingTime = round($endTime - $startTime, 2);
    
    echo "✅ Processamento concluído em {$processingTime}s\n\n";
    
    // Exibe o resultado
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    if ($data['success']) {
        echo "🎉 Sucesso!\n";
        echo "📋 Mensagem: {$data['message']}\n";
        echo "🆔 Document ID: {$data['document_id']}\n\n";
        
        if (isset($data['data'])) {
            echo "=== DADOS EXTRAÍDOS ===\n";
            
            // Dados pessoais
            if (isset($data['data']['dados_pessoais'])) {
                echo "👤 Dados Pessoais:\n";
                foreach ($data['data']['dados_pessoais'] as $campo => $valor) {
                    echo "   📋 {$campo}: {$valor}\n";
                }
            }
            
            // Vínculos empregatícios
            if (isset($data['data']['vinculos_empregaticios'])) {
                echo "\n🏢 Vínculos Empregatícios ({$data['data']['vinculos_empregaticios']}):\n";
                foreach ($data['data']['vinculos_empregaticios'] as $index => $vinculo) {
                    echo "   📋 Vínculo " . ($index + 1) . ": {$vinculo['empregador']}\n";
                }
            }
        }
    } else {
        echo "❌ Erro: {$data['error']}\n";
    }
    
    // Limpa arquivo temporário
    unlink($tempFile);
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fim do teste ===\n"; 