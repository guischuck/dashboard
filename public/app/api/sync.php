<?php

/**
 * Proxy para o endpoint sync do Laravel
 * Mantém compatibilidade com a extensão Chrome
 */

// Incluir o autoloader do Laravel
require_once __DIR__ . '/../../../vendor/autoload.php';

// Bootstrapar a aplicação Laravel
$app = require_once __DIR__ . '/../../../bootstrap/app.php';

// Configurar headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

try {
    // Criar kernel HTTP
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Criar request a partir dos dados atuais
    $request = Illuminate\Http\Request::capture();
    
    // Ajustar a URI para o endpoint Laravel correto
    $request->server->set('REQUEST_URI', '/api/extension/sync');
    $request->server->set('PATH_INFO', '/api/extension/sync');
    $request->server->set('REQUEST_METHOD', 'POST');
    
    // Processar request através do Laravel
    $response = $kernel->handle($request);
    
    // Enviar resposta
    $response->send();
    
    // Cleanup
    $kernel->terminate($request, $response);
    
} catch (Exception $e) {
    error_log("Erro em sync.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno']);
} 